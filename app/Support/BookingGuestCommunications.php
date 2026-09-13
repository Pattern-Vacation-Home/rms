<?php

namespace App\Support;

use App\Mail\BookingGuestUpdateMail;
use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Notifications\BookingGuestUpdate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingGuestCommunications
{
    public static function created(Collection $bookings, ?string $temporaryPassword = null): void
    {
        $booking = $bookings->first()?->fresh();
        if (! $booking) return;

        $booking->loadMissing('property.building', 'tenant');
        $schedule = $bookings->flatMap(fn (Booking $contract) => $contract->invoices()->orderBy('period_from')->get())->all();
        self::deliver($booking, new BookingGuestUpdate($booking, 'created'), new BookingGuestUpdateMail($booking, 'created', schedule: $schedule, temporaryPassword: $temporaryPassword));
    }

    public static function paymentRecorded(BookingInvoice $invoice, float $amount): void
    {
        $invoice->refresh()->load('booking.property.building', 'booking.tenant');
        $booking = $invoice->booking;
        if (! $booking) return;

        self::deliver($booking, new BookingGuestUpdate($booking, 'payment', $invoice, $amount), new BookingGuestUpdateMail($booking, 'payment', $invoice, $amount));
    }

    public static function invoiceCreated(BookingInvoice $invoice): void
    {
        $invoice->load('booking.property.building', 'booking.tenant');
        $booking = $invoice->booking;
        if (! $booking) return;

        self::deliver($booking, new BookingGuestUpdate($booking, 'invoice_created', $invoice), new BookingGuestUpdateMail($booking, 'invoice_created', $invoice));
    }

    private static function deliver(Booking $booking, BookingGuestUpdate $notification, BookingGuestUpdateMail $mail): void
    {
        if ($booking->tenant) {
            try { $booking->tenant->notify($notification); }
            catch (\Throwable $exception) { Log::warning('Guest in-app notification failed', ['booking_id' => $booking->id, 'error' => $exception->getMessage()]); }
        }

        if (filter_var($booking->guest_email, FILTER_VALIDATE_EMAIL)) {
            try { Mail::to($booking->guest_email)->send($mail); }
            catch (\Throwable $exception) {
                Log::warning('Guest booking email failed', ['booking_id' => $booking->id, 'error' => $exception->getMessage()]);
                $booking->histories()->create(['title' => 'Guest Email Failed', 'description' => 'Automated guest email could not be sent. Check mail settings and contact the guest manually.']);
            }
        }
    }
}
