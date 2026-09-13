<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;background:#eef2f8;font-family:Arial,Helvetica,sans-serif;color:#18273f">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 10px"><tr><td align="center">
<table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;width:100%;background:#fff;border-radius:16px;overflow:hidden">
<tr><td style="background:#0d2948;padding:26px 30px"><img src="{{ asset('assets/images/pattern-bilingual-logo.png') }}" width="210" alt="PATTERN Vacation Homes Rental" style="display:block;max-width:75%;height:auto;background:#fff;border-radius:7px;padding:7px"><p style="color:#dcbf83;font-size:12px;letter-spacing:2px;margin:24px 0 7px">{{ $event === 'created' ? 'WELCOME TO PATTERN' : ($event === 'invoice_created' ? 'NEW INVOICE' : 'PAYMENT UPDATE') }}</p><h1 style="color:#fff;font-size:25px;line-height:1.25;margin:0">{{ $event === 'created' ? 'Your stay and invoices are ready' : ($event === 'invoice_created' ? 'Your next invoice is ready' : ($invoice?->status === 'paid' ? 'Your period is confirmed' : 'We received your payment')) }}</h1></td></tr>
<tr><td style="padding:28px 30px">
<p style="margin:0 0 16px">Hello {{ $booking->guest_name }},</p>
@if($event === 'created')
<p style="line-height:1.6">Welcome to <strong>{{ $booking->property?->name ?? 'your PATTERN unit' }}</strong>{{ $booking->property?->building?->building_name ? ' — '.$booking->property->building->building_name : '' }}. Your booking has been created and its invoice periods are listed below.</p>
<p style="padding:13px 16px;background:#fff7e7;border-radius:9px;line-height:1.5"><strong>Booking confirmation is not issued yet.</strong> Each period’s confirmation becomes available only after its own invoice is fully paid. Future invoices do not have to be paid now to confirm a paid period.</p>
<div style="padding:16px;background:#edf4ff;border-radius:9px;margin:20px 0;line-height:1.7"><strong>Your guest app access</strong><br>Username: <strong>{{ $booking->guest_email }}</strong><br>@if($temporaryPassword)Temporary password: <strong>{{ $temporaryPassword }}</strong><br><span style="color:#526078;font-size:12px">Please sign in and change this password immediately. This password is shown only in this first email.</span>@elseUse your existing password. If you cannot remember it, choose “Forgot password” on the login screen.@endif<br><a href="{{ route('login') }}" style="color:#5646cc">Open guest app login</a></div>
<table role="presentation" width="100%" cellspacing="0" cellpadding="9" style="border-collapse:collapse;font-size:13px;margin:22px 0"><tr style="background:#f0f3fa;color:#40506a"><th align="left">Period</th><th align="left">Due</th><th align="right">Amount</th></tr>
@foreach($schedule as $scheduledInvoice)
<tr><td style="border-bottom:1px solid #e8edf5">{{ $scheduledInvoice->period_from?->format('d M Y') }} – {{ $scheduledInvoice->period_to?->format('d M Y') }}<br><span style="color:#718096">{{ $scheduledInvoice->invoice_number }}</span></td><td style="border-bottom:1px solid #e8edf5">{{ $scheduledInvoice->due_date?->format('d M Y') }}</td><td align="right" style="border-bottom:1px solid #e8edf5;white-space:nowrap">AED {{ number_format((float) $scheduledInvoice->total_amount, 2) }}</td></tr>
@endforeach
</table>
@elseif($event === 'invoice_created')
<p style="line-height:1.6">A new invoice <strong>{{ $invoice->invoice_number }}</strong> has been issued for your stay at <strong>{{ $booking->property?->name ?? 'your unit' }}</strong>.</p>
<div style="padding:16px;background:#f2f5fb;border-radius:9px;margin:20px 0;line-height:1.7">Stay period: <strong>{{ $invoice->period_from?->format('d M Y') }} – {{ $invoice->period_to?->format('d M Y') }}</strong><br>Amount due: <strong>AED {{ number_format((float) $invoice->total_amount, 2) }}</strong>@if($invoice->due_date)<br>Due date: <strong>{{ $invoice->due_date->format('d M Y') }}</strong>@endif</div>
<p>Booking confirmation for this period is available only after its invoice is fully paid.</p>
<p><a href="{{ route('guest.booking.invoice-document', [$booking->booking_reference, $invoice]) }}" style="color:#5646cc">View invoice PDF</a></p>
@else
<p style="line-height:1.6">We recorded <strong>AED {{ number_format((float) $paymentAmount, 2) }}</strong> against invoice <strong>{{ $invoice->invoice_number }}</strong> for {{ $invoice->period_from?->format('d M Y') }} – {{ $invoice->period_to?->format('d M Y') }}.</p>
<div style="padding:16px;background:{{ $invoice->status === 'paid' ? '#e9f8ef' : '#f2f5fb' }};border-radius:9px;margin:20px 0"><strong>{{ $invoice->status === 'paid' ? 'Invoice fully paid' : 'Invoice partly paid' }}</strong><br>Remaining balance: AED {{ number_format((float) $invoice->balance_due, 2) }}</div>
<p style="line-height:1.6">@if($invoice->status === 'paid')Your confirmation for <strong>this invoice period</strong> is now available. Other periods still require their own payments.@else Your confirmation for this period will become available when its invoice is fully paid. @endif</p>
<p style="margin:22px 0"><a href="{{ route('guest.booking.invoice-document', [$booking->booking_reference, $invoice]) }}" style="color:#5646cc">View invoice</a> &nbsp;·&nbsp; <a href="{{ route('guest.booking.invoice-receipt', [$booking->booking_reference, $invoice]) }}" style="color:#5646cc">Payment receipt</a>@if($invoice->status === 'paid') &nbsp;·&nbsp; <a href="{{ route('guest.booking.invoice-confirmation', [$booking->booking_reference, $invoice]) }}" style="color:#5646cc">Period confirmation</a>@endif</p>
@endif
<p style="margin:26px 0 12px"><a href="{{ route('guest.booking.show', $booking->booking_reference) }}" style="display:inline-block;background:#5b4bd5;color:#fff;text-decoration:none;padding:13px 20px;border-radius:8px">View booking and invoices</a></p>
<p style="color:#66758b;font-size:13px;line-height:1.6">Saving an invoice does not record a payment. If you have paid but do not see it here, please contact our team with your transfer reference.</p>
</td></tr><tr><td style="background:#f5f7fb;padding:16px 30px;color:#718096;font-size:12px">PATTERN Vacation Homes Rental · Automated guest update</td></tr>
</table></td></tr></table></body></html>
