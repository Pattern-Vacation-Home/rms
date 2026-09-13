<?php

namespace App\Http\Controllers\Tenants;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingTask;
use App\Models\User;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::with('property.building')->where('tenant_id', $request->user()->id)
            ->whereHas('property')->whereIn('status', ['confirmed', 'checked_in'])->latest()->get();
        $tasks = BookingTask::with('property.building')->where('created_by', $request->user()->id)
            ->where(fn ($q) => $q->where('type', 'maintenance')->orWhere('category', 'like', 'complaint:%'))
            ->whereHas('booking', fn ($q) => $q->where('tenant_id', $request->user()->id))
            ->latest()->paginate(10);

        return view('tenant.maintenance', compact('bookings', 'tasks'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'booking_id' => 'required|uuid', 'title' => 'required|string|max:150',
            'description' => 'required|string|max:3000', 'priority' => 'required|in:low,medium,high,urgent',
            'pictures' => 'nullable|array|max:5', 'pictures.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);
        $pictures = [];
        try {
            $task = DB::transaction(function () use ($request, $data, &$pictures) {
                $booking = Booking::where('tenant_id', $request->user()->id)->whereHas('property')
                    ->whereKey($data['booking_id'])->lockForUpdate()->firstOrFail();
                abort_unless(in_array($booking->status, ['confirmed', 'checked_in'], true), 422, 'This booking is no longer active. Please contact management.');
                foreach ($request->file('pictures', []) as $file) {
                    $pictures[] = MediaStorage::store($file, 'booking_task_attachments');
                }
                $task = BookingTask::create([
                    'task_number' => 'TSK-'.strtoupper((string) Str::ulid()),
                    'booking_id' => $booking->id, 'property_id' => $booking->property_id,
                    'created_by' => $request->user()->id, 'type' => 'maintenance', 'category' => 'maintenance',
                    'priority' => $data['priority'], 'title' => $data['title'], 'description' => $data['description'],
                    'pictures' => $pictures, 'status' => 'open', 'progress' => 0,
                ]);
                $task->activities()->create(['user_id' => $request->user()->id, 'action' => 'Created', 'comment' => 'Maintenance request submitted through the guest app.']);

                return $task;
            });
        } catch (\Throwable $exception) {
            foreach ($pictures as $path) {
                Storage::disk(MediaStorage::disk())->delete(MediaStorage::path($path));
            }
            throw $exception;
        }

        return to_route('tenant.maintenance.index')->with('success', $task->task_display_number.' submitted to management.');
    }

    public function storeComplaint(Request $request)
    {
        $types = [
            'air_conditioning' => 'Air conditioning', 'plumbing' => 'Plumbing',
            'electrical' => 'Electrical', 'internet' => 'Internet', 'appliance' => 'Appliance',
            'billing' => 'Billing or invoice', 'service' => 'Service', 'other' => 'Other',
        ];
        $data = $request->validate([
            'booking_id' => 'required|uuid',
            'complaint_type' => 'required|in:'.implode(',', array_keys($types)),
            'description' => 'required|string|min:10|max:3000',
        ]);
        $task = DB::transaction(function () use ($request, $data, $types) {
            $booking = Booking::where('tenant_id', $request->user()->id)->whereHas('property')
                ->whereKey($data['booking_id'])->lockForUpdate()->firstOrFail();
            abort_unless(in_array($booking->status, ['confirmed', 'checked_in'], true), 422, 'This booking is no longer active. Please contact management.');

            $technical = in_array($data['complaint_type'], ['air_conditioning', 'plumbing', 'electrical', 'internet', 'appliance'], true);
            $assignee = $technical
                ? User::where('role', 'maintainer')->where('is_active', true)->orderBy('name')->first()
                : User::where('role', 'admin')->where('is_active', true)->orderBy('name')->first();
            $task = BookingTask::create([
                'task_number' => 'TSK-'.strtoupper((string) Str::ulid()),
                'booking_id' => $booking->id, 'property_id' => $booking->property_id,
                'created_by' => $request->user()->id, 'assigned_to' => $assignee?->id,
                'type' => $technical ? 'maintenance' : 'other',
                'category' => 'complaint:'.$data['complaint_type'],
                'priority' => 'medium', 'title' => $types[$data['complaint_type']].' complaint',
                'description' => $data['description'], 'status' => $assignee ? 'assigned' : 'open',
                'progress' => 0,
            ]);
            $task->activities()->create(['user_id' => $request->user()->id, 'action' => 'Created',
                'comment' => 'Guest complaint submitted. Routed to '.($technical ? 'technical operations' : 'administration').($assignee ? ' ('.$assignee->name.').' : '; assignment pending.')]);

            return $task;
        });

        return to_route('tenant.maintenance.index')->with('success', $task->task_display_number.' complaint sent to our team.');
    }
}
