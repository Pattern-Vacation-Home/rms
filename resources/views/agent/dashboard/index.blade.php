@extends('layouts.portal', [
    'portalTitle' => 'Agent Portal',
    'portalEyebrow' => 'Agent',
    'portalHeading' => 'My Bookings & Commission',
])

@section('content')
<div class="portal-stat-grid">
    <div class="portal-card portal-stat"><div><span>Total Bookings</span><strong>{{ $bookings->count() }}</strong></div><i class="ri-calendar-check-line fs-28 text-primary"></i></div>
    <div class="portal-card portal-stat"><div><span>Paid</span><strong>{{ $paidBookings->count() }}</strong></div><i class="ri-bill-line fs-28 text-success"></i></div>
    <div class="portal-card portal-stat"><div><span>Agency Fees Collected</span><strong>AED {{ number_format($collectedAgencyFees, 2) }}</strong></div><i class="ri-hand-coin-line fs-28 text-info"></i></div>
    <div class="portal-card portal-stat"><div><span>Commission Earned</span><strong>AED {{ number_format($earnedCommission, 2) }}</strong></div><i class="ri-money-dirham-circle-line fs-28 text-warning"></i></div>
</div>

<section class="portal-card mb-3" id="commission-report">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h4 class="mb-1">My Agency Fee Report</h4><p class="text-muted mb-0">Commission is calculated from agency fees actually collected, not from rent.</p></div><span class="badge bg-info-subtle text-info border">Default {{ number_format($commissionPercent,2) }}%</span></div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>Date / Invoice</th><th>Booking &amp; Unit</th><th class="text-end">Agency Fee</th><th class="text-center">Rate</th><th class="text-end">My Commission</th></tr></thead>
        <tbody>
        @forelse($commissionRows as $row)
            <tr><td>{{ $row['payment']->payment_date?->format('d M Y') }}<br><small class="text-muted">{{ $row['invoice']?->invoice_number ?? '-' }}</small></td><td>{{ $row['booking']?->booking_reference ?? '-' }}<br><small class="text-muted">{{ $row['booking']?->property?->name ?? 'Unit' }}</small></td><td class="text-end">AED {{ number_format((float)$row['agency_fee'],2) }}</td><td class="text-center">{{ number_format((float)$row['rate'],2) }}%</td><td class="text-end fw-bold text-success">AED {{ number_format((float)$row['commission'],2) }}</td></tr>
        @empty<tr><td colspan="5" class="text-center text-muted py-4">No agency-fee commission has been collected yet.</td></tr>@endforelse
        </tbody>
        <tfoot class="table-light fw-bold"><tr><td colspan="4">Total Commission Earned</td><td class="text-end">AED {{ number_format($earnedCommission,2) }}</td></tr></tfoot>
    </table></div>
</section>

<section class="portal-card" id="bookings">
    <h4>Assigned Bookings</h4>
    <div class="portal-list">
        @forelse($bookings as $booking)
            <div class="portal-list-item">
                <div>
                    <strong>{{ $booking->booking_reference }} - {{ $booking->guest_name }}</strong>
                    <p>{{ $booking->property?->name ?? 'Unit' }} | {{ $booking->check_in?->format('d M Y') }} to {{ $booking->check_out?->format('d M Y') }}</p>
                </div>
                <span class="badge {{ $booking->workflow_status_class }} text-white">{{ $booking->workflow_status_label }}</span>
            </div>
        @empty
            <p class="text-muted mb-0">No bookings assigned.</p>
        @endforelse
    </div>
</section>
@endsection
