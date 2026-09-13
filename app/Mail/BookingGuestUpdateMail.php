<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\BookingInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingGuestUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $event,
        public ?BookingInvoice $invoice = null,
        public ?float $paymentAmount = null,
        public array $schedule = [],
        public ?string $temporaryPassword = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->event === 'created'
            ? 'Welcome to PATTERN · your booking and invoice schedule'
            : ($this->event === 'invoice_created'
                ? 'New invoice for your PATTERN stay · '.$this->invoice?->invoice_number
                : ($this->invoice?->status === 'paid'
                ? 'Payment complete · confirmation available for '.$this->invoice->invoice_number
                : 'Payment received · '.$this->invoice?->invoice_number)));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-guest-update');
    }
}
