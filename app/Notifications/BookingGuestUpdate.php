<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\BookingInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingGuestUpdate extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Booking $booking,
        private readonly string $event,
        private readonly ?BookingInvoice $invoice = null,
        private readonly ?float $paymentAmount = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        if ($this->event === 'created') {
            return [
                'title' => 'Welcome — invoice schedule ready',
                'message' => 'Your stay at '.($this->booking->property?->name ?? 'your unit').' has been added. Review the invoice schedule; confirmation for each period is available once that period is fully paid.',
                'booking_id' => $this->booking->id,
                'url' => route('tenant.booking.show', $this->booking),
            ];
        }

        if ($this->event === 'invoice_created') {
            return [
                'title' => 'New invoice available',
                'message' => $this->invoice?->invoice_number.' for '.($this->invoice?->period_from?->format('d M Y') ?? 'your next period').' is ready. Confirmation becomes available after this invoice is fully paid.',
                'booking_id' => $this->booking->id,
                'invoice_id' => $this->invoice?->id,
                'url' => route('tenant.booking.show', $this->booking),
            ];
        }

        return [
            'title' => $this->invoice?->status === 'paid' ? 'Invoice paid — confirmation ready' : 'Payment received',
            'message' => 'AED '.number_format((float) $this->paymentAmount, 2).' was recorded against '.$this->invoice?->invoice_number.'. '.($this->invoice?->status === 'paid' ? 'Your confirmation for this period is now available.' : 'Remaining balance: AED '.number_format((float) $this->invoice?->balance_due, 2).'.'),
            'booking_id' => $this->booking->id,
            'invoice_id' => $this->invoice?->id,
            'url' => route('tenant.booking.show', $this->booking),
        ];
    }
}
