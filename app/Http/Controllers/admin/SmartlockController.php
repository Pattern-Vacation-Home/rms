<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingLockAccess;
use App\Models\Property;
use App\Models\Smartlock;
use App\Support\AppSettings;
use App\Support\TtlockClient;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class SmartlockController extends Controller
{
    public function index(TtlockClient $client)
    {
        return view('admin.smartlocks.index', [
            'locks' => Smartlock::with('property.building')->orderBy('alias')->orderBy('name')->get(),
            'units' => Property::with('building')->orderBy('name')->get(),
            'configured' => $client->configured(),
            'clientId' => AppSettings::get('ttlock_client_id'),
        ]);
    }

    public function connection(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|string|max:100',
            'client_secret' => 'nullable|string|max:500',
            'access_token' => 'nullable|string|max:2000',
        ]);
        if (blank($data['client_secret'] ?? null) && blank(AppSettings::get('ttlock_client_secret'))
            || blank($data['access_token'] ?? null) && blank(AppSettings::get('ttlock_access_token'))) {
            throw ValidationException::withMessages(['connection' => 'Client secret and access token are required for the first connection.']);
        }
        $settings = ['ttlock_client_id' => $data['client_id']];
        foreach (['client_secret', 'access_token'] as $field) {
            if (filled($data[$field] ?? null)) $settings['ttlock_'.$field] = $data[$field];
        }
        AppSettings::setMany($settings);

        return back()->with('success', 'TTLock connection saved. Sync the locks to verify it.');
    }

    public function sync(TtlockClient $client)
    {
        try {
            $records = $client->locks();
            foreach ($records as $record) {
                if (! isset($record['lockId']) || ! is_numeric($record['lockId'])) continue;
                Smartlock::updateOrCreate(['remote_id' => (int) $record['lockId']], [
                    'name' => (string) ($record['lockName'] ?? 'TTLock '. $record['lockId']),
                    'alias' => $record['lockAlias'] ?? null,
                    'mac' => $record['lockMac'] ?? null,
                    'battery' => isset($record['electricQuantity']) ? max(0, min(100, (int) $record['electricQuantity'])) : null,
                    'passcode_version' => $record['keyboardPwdVersion'] ?? null,
                    'has_gateway' => ($record['hasGateway'] ?? 0) == 1,
                    'last_synced_at' => now(),
                ]);
            }

            return back()->with('success', count($records).' TTLock device(s) synced.');
        } catch (Throwable $exception) {
            report($exception);
            return back()->with('error', $exception->getMessage());
        }
    }

    public function assign(Request $request, Property $property)
    {
        $data = $request->validate(['smartlock_id' => 'nullable|exists:smartlocks,id']);
        if (filled($data['smartlock_id'] ?? null) && Property::where('smartlock_id', $data['smartlock_id'])->whereKeyNot($property->id)->exists()) {
            throw ValidationException::withMessages(['smartlock_id' => 'This lock is already attached to another unit.']);
        }
        $property->update(['smartlock_id' => $data['smartlock_id'] ?? null]);

        return back()->with('success', 'Unit smart lock updated.');
    }

    public function issue(Request $request, Booking $booking, TtlockClient $client)
    {
        $data = $request->validate(['invoice_id' => 'required|uuid']);
        $booking->load('property.smartlock');
        $invoice = $booking->invoices()->whereKey($data['invoice_id'])->firstOrFail();
        $lock = $booking->property?->smartlock;
        if (! $lock || ! $lock->has_gateway || (int) $lock->passcode_version !== 4) {
            throw ValidationException::withMessages(['lock' => 'Assign a synced V4 lock with an online gateway before issuing access.']);
        }
        if ($booking->status === 'checked_out' || $invoice->legacy_owner_settled || $invoice->balance_due > 0) {
            throw ValidationException::withMessages(['invoice_id' => 'Access requires an active booking and a fully paid invoice period.']);
        }
        if (BookingLockAccess::where('booking_invoice_id', $invoice->id)->exists()) {
            throw ValidationException::withMessages(['invoice_id' => 'This invoice period already has a passcode.']);
        }
        $start = $invoice->period_from ? CarbonImmutable::parse($invoice->period_from->toDateString().' '.($booking->check_in_time ?: '15:00'), 'Asia/Dubai')->utc() : null;
        $isLastPeriod = $invoice->period_to?->isSameDay($booking->check_out);
        $end = $invoice->period_to ? CarbonImmutable::parse($invoice->period_to->toDateString().' '.($isLastPeriod ? ($booking->check_out_time ?: '11:00') : ($booking->check_in_time ?: '15:00')), 'Asia/Dubai')->utc() : null;
        if (! $start || ! $end || $end->lessThanOrEqualTo($start) || $end->isPast()) {
            throw ValidationException::withMessages(['invoice_id' => 'This invoice does not have a valid future access period.']);
        }
        $code = (string) random_int(100000, 999999);
        try {
            $remoteId = $client->addPasscode((int) $lock->remote_id, $code, $start->getTimestampMs(), $end->getTimestampMs(), $booking->booking_reference.' / '.$invoice->invoice_number);
            BookingLockAccess::create([
                'booking_id' => $booking->id, 'booking_invoice_id' => $invoice->id, 'smartlock_id' => $lock->id,
                'remote_passcode_id' => $remoteId, 'passcode' => $code,
                'starts_at' => $start, 'ends_at' => $end, 'issued_by' => $request->user()->id,
            ]);
        } catch (Throwable $exception) {
            if (isset($remoteId)) {
                try { $client->deletePasscode((int) $lock->remote_id, $remoteId); } catch (Throwable $cleanupError) { report($cleanupError); }
            }
            report($exception);
            return back()->with('error', 'Could not issue TTLock access: '.$exception->getMessage());
        }
        $booking->histories()->create(['title' => 'Door access issued', 'description' => 'Smart-lock passcode issued for invoice '.$invoice->invoice_number.'.']);
        return back()->with('success', 'Guest passcode issued for the paid invoice period.');
    }

    public function revoke(BookingLockAccess $access, TtlockClient $client)
    {
        if ($access->revoked_at) return back();
        try {
            $client->deletePasscode((int) $access->smartlock->remote_id, (int) $access->remote_passcode_id);
            $access->update(['revoked_at' => now()]);
            return back()->with('success', 'Guest passcode revoked.');
        } catch (Throwable $exception) {
            report($exception);
            return back()->with('error', 'TTLock could not confirm revocation. The code may still work: '.$exception->getMessage());
        }
    }
}
