<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCheckInPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function bookingWithTwoPeriods(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $unit = Property::create(['landlord_id' => $owner->id, 'name' => '502', 'status' => 'rented']);
        $booking = Booking::create([
            'property_id' => $unit->id, 'booking_reference' => 'BK-CHECKIN-1', 'invoice_number' => 'INV-CHECKIN-1',
            'guest_name' => 'Test Guest', 'guest_email' => 'guest@example.com', 'guest_phone' => '0500000000',
            'guest_passport_id_no' => 'P123', 'check_in' => '2026-09-01', 'check_out' => '2026-10-31',
            'status' => 'confirmed', 'invoice_status' => 'unpaid',
        ]);
        $first = BookingInvoice::create([
            'booking_id' => $booking->id, 'invoice_number' => 'INV-CHECKIN-1', 'invoice_type' => 'original',
            'issue_date' => '2026-09-01', 'period_from' => '2026-09-01', 'period_to' => '2026-10-01',
            'rent_amount' => 1000, 'total_amount' => 1000, 'status' => 'unpaid',
        ]);
        $future = BookingInvoice::create([
            'booking_id' => $booking->id, 'invoice_number' => 'INV-CHECKIN-2', 'invoice_type' => 'extension',
            'issue_date' => '2026-10-01', 'period_from' => '2026-10-01', 'period_to' => '2026-10-31',
            'rent_amount' => 1000, 'total_amount' => 1000, 'status' => 'unpaid',
        ]);

        return compact('admin', 'booking', 'first', 'future');
    }

    public function test_first_period_payment_allows_check_in_while_future_invoice_is_unpaid(): void
    {
        ['admin' => $admin, 'booking' => $booking, 'first' => $first, 'future' => $future] = $this->bookingWithTwoPeriods();
        $first->payments()->create(['payment_date' => '2026-09-01', 'amount' => 1000, 'payment_method' => 'Bank Transfer']);
        $first->update(['status' => 'paid']);

        $this->actingAs($admin)->get(route('admin.booking.show', $booking))
            ->assertOk()->assertSee('Later invoice periods may remain unpaid.');
        $this->post(route('admin.booking.check-in', $booking))->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame('checked_in', $booking->fresh()->status);
        $this->assertSame('Checked In', $booking->fresh()->workflow_status_label);
        $this->assertSame('unpaid', $future->fresh()->status);
    }

    public function test_future_payment_does_not_replace_unpaid_first_period(): void
    {
        ['admin' => $admin, 'booking' => $booking, 'future' => $future] = $this->bookingWithTwoPeriods();
        $future->payments()->create(['payment_date' => '2026-09-01', 'amount' => 1000, 'payment_method' => 'Bank Transfer']);
        $future->update(['status' => 'paid']);

        $this->actingAs($admin)->post(route('admin.booking.check-in', $booking))->assertSessionHasErrors('workflow');
        $this->assertSame('confirmed', $booking->fresh()->status);
    }
}
