<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingCheckoutReminder extends Notification
{
    use Queueable;

    public function __construct(private readonly Booking $booking, private readonly float $balanceDue, private readonly int $daysBeforeCheckout) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Checkout in '.$this->daysBeforeCheckout.' days',
            'message' => 'Your stay at '.($this->booking->property?->name ?? 'your unit').' ends on '.$this->booking->check_out?->format('d M Y').'. Request any extension before checkout; late requests cannot be guaranteed. Smart-lock access, if provided, may end at checkout. Unpaid invoice balance: AED '.number_format($this->balanceDue, 2).'.',
            'booking_id' => $this->booking->id,
            'check_out' => $this->booking->check_out?->toDateString(),
            'balance_due' => $this->balanceDue,
            'url' => route('tenant.booking.show', $this->booking),
        ];
    }
}
