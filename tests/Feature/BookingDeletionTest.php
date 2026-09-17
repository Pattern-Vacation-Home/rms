<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\LandlordAccountEntry;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function booking(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Deletion Unit', 'status' => 'rented']);
        $booking = Booking::create([
            'property_id' => $property->id, 'booking_reference' => 'BK-REMOVE', 'invoice_number' => 'INV-REMOVE',
            'guest_name' => 'Delete Guest', 'guest_email' => 'delete@example.com', 'guest_phone' => '0500000000',
            'guest_passport_id_no' => 'DEL-123',
            'check_in' => '2026-09-01', 'check_out' => '2026-09-30', 'rent_amount' => 1000,
            'security_deposit' => 100, 'total_amount' => 1100, 'status' => 'confirmed',
        ]);
        $invoice = BookingInvoice::create([
            'booking_id' => $booking->id, 'invoice_number' => 'INV-REMOVE', 'invoice_type' => 'original',
            'issue_date' => today(), 'rent_amount' => 1000, 'fees' => ['Security Deposit' => 100],
            'total_amount' => 1100, 'status' => 'unpaid',
        ]);
        $bank = BankAccount::create(['name' => 'Delete Test Bank', 'type' => 'bank', 'currency' => 'AED', 'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true]);
        $this->actingAs($admin);

        return compact('admin', 'owner', 'property', 'booking', 'invoice', 'bank');
    }

    public function test_deletion_removes_booking_finances_and_recalculates_balances(): void
    {
        extract($this->booking());
        $this->post(route('admin.booking-invoice.payment', $invoice), [
            'payment_date' => today()->toDateString(), 'amount' => 1100,
            'payment_method' => 'Bank Transfer', 'bank_account_id' => $bank->id,
        ])->assertSessionHasNoErrors();
        $this->assertGreaterThan(0, DB::table('accounting_entries')->where('booking_id', $booking->id)->count());
        $this->assertGreaterThan(0, (float) $bank->fresh()->current_balance);

        $this->delete(route('admin.booking.destroy', $booking), [
            'booking_reference' => $booking->booking_reference,
            'current_password' => 'password',
            'reason' => 'Duplicate booking entered by mistake',
        ])->assertRedirect(route('admin.booking.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        $this->assertDatabaseMissing('booking_invoices', ['id' => $invoice->id]);
        $this->assertDatabaseMissing('accounting_entries', ['booking_id' => $booking->id]);
        $this->assertDatabaseMissing('booking_deposit_entries', ['booking_id' => $booking->id]);
        $this->assertDatabaseMissing('landlord_account_entries', ['booking_invoice_id' => $invoice->id]);
        $this->assertEquals(0, (float) $bank->fresh()->current_balance);
        $this->assertSame('vacant', $property->fresh()->status);
        $this->assertSame(0, LandlordAccountEntry::where('landlord_id', $owner->id)->count());
    }

    public function test_wrong_password_cannot_delete_booking(): void
    {
        ['booking' => $booking] = $this->booking();
        $this->delete(route('admin.booking.destroy', $booking), [
            'booking_reference' => $booking->booking_reference,
            'current_password' => 'wrong password',
            'reason' => 'Duplicate booking entered by mistake',
        ])->assertSessionHasErrors('current_password');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
    }

    public function test_booking_with_deposit_carried_to_another_booking_cannot_be_deleted(): void
    {
        ['booking' => $booking] = $this->booking();
        $other = $booking->replicate();
        $other->fill(['booking_reference' => 'BK-KEEP', 'invoice_number' => 'INV-KEEP']);
        $other->save();
        DB::table('booking_deposit_entries')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'booking_id' => $booking->id, 'related_booking_id' => $other->id,
            'kind' => 'carry_out', 'amount' => 100, 'entry_date' => today()->toDateString(),
            'submission_id' => (string) \Illuminate\Support\Str::uuid(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->delete(route('admin.booking.destroy', $booking), [
            'booking_reference' => $booking->booking_reference,
            'current_password' => 'password',
            'reason' => 'Duplicate booking entered by mistake',
        ])->assertSessionHasErrors('deletion');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
        $this->assertDatabaseHas('bookings', ['id' => $other->id]);
    }
}
