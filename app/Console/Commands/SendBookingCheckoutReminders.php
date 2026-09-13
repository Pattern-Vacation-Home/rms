<?php

namespace App\Console\Commands;

use App\Mail\BookingCheckoutReminderMail;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingCheckoutReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBookingCheckoutReminders extends Command
{
    protected $signature = 'bookings:send-checkout-reminders';
    protected $description = 'Email guests seven and three days before checkout and notify linked tenant accounts';

    public function handle(): int
    {
        $sent = 0;
        foreach ([7, 3] as $daysBeforeCheckout) {
            $checkoutDate = today()->addDays($daysBeforeCheckout)->toDateString();
            $dateColumn = $daysBeforeCheckout === 7 ? 'checkout_reminder_7_day_sent_for' : 'checkout_reminder_sent_for';
            $timeColumn = $daysBeforeCheckout === 7 ? 'checkout_reminder_7_day_sent_at' : 'checkout_reminder_sent_at';
            Booking::query()->with(['property.building', 'invoices.payments'])
                ->whereDate('check_out', $checkoutDate)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->where(fn ($query) => $query->whereNull($dateColumn)
                    ->orWhereDate($dateColumn, '!=', $checkoutDate))
                ->chunkById(100, function ($bookings) use (&$sent, $checkoutDate, $daysBeforeCheckout, $dateColumn, $timeColumn) {
                foreach ($bookings as $booking) {
                    $email = trim((string) $booking->guest_email);
                    $tenant = $booking->tenant_id ? User::whereKey($booking->tenant_id)->where('role', 'tenant')->first() : null;
                    if (! filter_var($email, FILTER_VALIDATE_EMAIL) && $tenant && filter_var($tenant->email, FILTER_VALIDATE_EMAIL)) {
                        $email = $tenant->email;
                    }
                    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        Log::warning('Checkout reminder skipped: no valid guest email', ['booking_id' => $booking->id]);
                        continue;
                    }
                    $balance = round($booking->invoices->sum(fn ($invoice) => $invoice->balance_due), 2);
                    try {
                        Mail::to($email)->send(new BookingCheckoutReminderMail($booking, $balance, $daysBeforeCheckout));
                        if ($tenant) $tenant->notify(new BookingCheckoutReminder($booking, $balance, $daysBeforeCheckout));
                        $booking->forceFill([$dateColumn => $checkoutDate, $timeColumn => now()])->save();
                        $sent++;
                    } catch (\Throwable $exception) {
                        Log::error('Checkout reminder failed', ['booking_id' => $booking->id, 'error' => $exception->getMessage()]);
                    }
                }
                });
        }
        $this->components->info("Sent {$sent} checkout reminder(s).");
        return self::SUCCESS;
    }
}
