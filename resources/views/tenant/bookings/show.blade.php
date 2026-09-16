@extends('layouts.tenant-pwa', ['title' => 'Booking Details'])

@section('content')
<div class="tenant-screen">
    @include('tenant.partials.header', ['title' => 'Booking Details'])
    <main class="tenant-content">
        <section class="tenant-booking-card">
            <div class="tenant-card-head">
                <strong>{{ $booking->booking_reference }}</strong>
                <span>{{ $booking->workflow_status_label }}</span>
            </div>
            <h2>{{ $booking->property?->building?->name ?? 'Property' }} - {{ $booking->property?->name ?? 'Unit' }}</h2>
            <p>{{ $booking->property?->community ?? 'Dubai' }}</p>
            <div class="tenant-unit-photo"></div>
            <div class="tenant-date-grid">
                <div><small>Check-in</small><b>{{ $booking->check_in?->format('d M Y') }}</b></div>
                <div><small>Check-out</small><b>{{ $booking->check_out?->format('d M Y') }}</b></div>
            </div>
            <div class="tenant-info-list">
                <div><span>Guest</span><strong>{{ $booking->guest_name }}</strong></div>
                <div><span>Security Deposit</span><strong>AED {{ number_format((float) $booking->security_deposit, 2) }}</strong></div>
                <div><span>Unit Type</span><strong>{{ (int) ($booking->property?->bedrooms ?? 0) === 0 ? 'Studio' : (int) $booking->property->bedrooms . ' BHK' }}</strong></div>
            </div>
        </section>

        @if($booking->property?->smartlock)
        @php
            $activeDoorAccess = $booking->status !== 'checked_out' ? $booking->lockAccesses->first(fn($access) => !$access->revoked_at && $access->starts_at <= now() && $access->ends_at > now() && !$access->invoice?->legacy_owner_settled && $access->invoice?->balance_due <= 0) : null;
        @endphp
        <section class="tenant-section" style="background:linear-gradient(135deg,#102b45,#303aa4);border-radius:18px;padding:20px;color:#fff;box-shadow:0 12px 25px #172a5b20"><div style="display:flex;align-items:center;gap:12px"><span style="width:42px;height:42px;border-radius:12px;background:#ffffff25;display:grid;place-items:center;font-size:21px"><i class="ri-door-lock-line"></i></span><div><h3 style="color:white;margin:0">Door Access</h3><small style="color:#d5e3fb">{{ $booking->property?->building?->building_name ?? 'Your unit' }} · {{ $booking->property?->name }}</small></div></div>
            @if($activeDoorAccess)<p style="color:#d8e4f8;margin:18px 0 5px">Your current door passcode</p><div style="font-size:31px;font-weight:800;letter-spacing:.28em">{{ $activeDoorAccess->passcode }}</div><small style="color:#d8e4f8">Valid until {{ $activeDoorAccess->ends_at->copy()->timezone('Asia/Dubai')->format('d M Y, H:i') }} (Dubai time)</small>
            @else<p style="color:#d8e4f8;margin:18px 0 0">Your door code will appear here when your paid stay period starts and access is issued. Contact reception if you need help.</p>@endif
        </section>
        @endif

        <section class="tenant-section">
            <h3>Inspections</h3>
            @php
                $checkIn = $booking->inspections->firstWhere('type', 'check_in');
                $checkOut = $booking->inspections->firstWhere('type', 'check_out');
            @endphp
            <form action="{{ route('tenant.inspection.start', [$booking->id, 'check_in']) }}" method="POST">
                @csrf
                <button class="tenant-primary" type="submit">{{ $checkIn ? 'Open Check-In Inspection' : 'Start Check-In Inspection' }}</button>
            </form>
            <form action="{{ route('tenant.inspection.start', [$booking->id, 'check_out']) }}" method="POST">
                @csrf
                <button class="tenant-secondary" type="submit">{{ $checkOut ? 'Open Check-Out Inspection' : 'Start Check-Out Inspection' }}</button>
            </form>
        </section>

        <section class="tenant-section">
            <h3>Invoices & Payments</h3>
            @include('guest.partials.invoice-payment-status')
        </section>
    </main>
    @include('tenant.partials.bottom-nav')
</div>
@endsection
