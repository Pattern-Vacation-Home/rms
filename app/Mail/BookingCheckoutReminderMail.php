<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingCheckoutReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking, public float $balanceDue) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your PATTERN stay ends in 3 days · '.$this->booking->booking_reference);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-checkout-reminder');
    }
}
