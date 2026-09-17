@extends('layouts.app')

@section('content')
<div class="mx-auto" style="max-width:760px">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div><h3 class="mb-1">Delete booking</h3><p class="text-muted mb-0">Review the booking and confirm this permanent action.</p></div>
        <a href="{{ route('admin.booking.show', $booking) }}" class="btn btn-light">Back to booking</a>
    </div>

    <div class="card border-danger-subtle shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                <div><div class="text-muted small">Booking number</div><strong class="fs-5">{{ $booking->booking_reference }}</strong></div>
                <div><div class="text-muted small">Guest</div><strong>{{ $booking->guest_name }}</strong></div>
                <div><div class="text-muted small">Unit</div><strong>{{ $booking->property?->name ?? '—' }}</strong></div>
            </div>
            <div class="alert alert-danger mb-4">
                This permanently removes the booking, its {{ $booking->invoices_count }} invoice(s), payments, deposits, owner statement and ledger entries, linked expenses, {{ $booking->tasks_count }} task(s), and {{ $booking->inspections_count }} inspection(s). Bank and owner balances are recalculated. This cannot be undone.
            </div>
            @if($errors->any())<div class="alert alert-warning">{{ $errors->first() }}</div>@endif
            <form action="{{ route('admin.booking.destroy', $booking) }}" method="POST">
                @csrf @method('DELETE')
                <div class="mb-3">
                    <label for="booking_reference" class="form-label">Type <strong>{{ $booking->booking_reference }}</strong> to confirm</label>
                    <input id="booking_reference" name="booking_reference" class="form-control @error('booking_reference') is-invalid @enderror" value="{{ old('booking_reference') }}" autocomplete="off" required>
                    @error('booking_reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="reason" class="form-label">Reason for deletion</label>
                    <textarea id="reason" name="reason" class="form-control @error('reason') is-invalid @enderror" rows="2" minlength="10" maxlength="1000" required>{{ old('reason') }}</textarea>
                    @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label for="current_password" class="form-label">Your password</label>
                    <input id="current_password" name="current_password" type="password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password" required>
                    @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="d-flex flex-wrap justify-content-end gap-2">
                    <a href="{{ route('admin.booking.show', $booking) }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-danger">Delete booking and linked records</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
