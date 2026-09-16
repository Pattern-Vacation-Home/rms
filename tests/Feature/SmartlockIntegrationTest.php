<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\BookingLockAccess;
use App\Models\Property;
use App\Models\Smartlock;
use App\Models\User;
use App\Support\AppSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmartlockIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_and_unit_assignment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $unit = Property::create(['landlord_id' => $owner->id, 'name' => '502', 'status' => 'vacant']);
        AppSettings::setMany(['ttlock_client_id' => 'client', 'ttlock_client_secret' => 'secret', 'ttlock_access_token' => 'token']);
        Http::fake(['api.sciener.com/*' => Http::response(['errcode' => 0, 'list' => [[
            'lockId' => 1234, 'lockName' => 'Front Door', 'keyboardPwdVersion' => 4,
            'hasGateway' => 1, 'electricQuantity' => 88,
        ]]])]);

        $this->actingAs($admin)->post(route('admin.smartlocks.sync'))->assertRedirect()->assertSessionHasNoErrors();
        $lock = Smartlock::firstOrFail();
        $this->assertSame(1234, (int) $lock->remote_id);
        $this->assertTrue($lock->has_gateway);
        $this->actingAs($admin)->put(route('admin.smartlocks.assign', $unit), ['smartlock_id' => $lock->id])->assertRedirect();
        $this->assertSame($lock->id, $unit->fresh()->smartlock_id);
    }

    public function test_guest_code_requires_paid_invoice_and_is_visible_only_during_the_stay(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 16)->setTime(8, 0));
        $admin = User::factory()->create(['role' => 'admin']);
        $tenant = User::factory()->create(['role' => 'tenant']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $lock = Smartlock::create(['remote_id' => 1234, 'name' => 'Front Door', 'has_gateway' => true, 'passcode_version' => 4]);
        $unit = Property::create(['landlord_id' => $owner->id, 'name' => '502', 'status' => 'rented', 'smartlock_id' => $lock->id]);
        $booking = Booking::create([
            'booking_reference' => 'BK-TTLOCK-1', 'invoice_number' => 'INV-TTLOCK-1',
            'property_id' => $unit->id, 'tenant_id' => $tenant->id, 'guest_name' => 'Test Guest',
            'guest_email' => 'guest@example.com', 'guest_phone' => '0500000000', 'guest_passport_id_no' => 'P123',
            'check_in' => '2026-09-15', 'check_out' => '2026-10-15', 'check_in_time' => '15:00', 'check_out_time' => '11:00',
            'status' => 'checked_in', 'invoice_status' => 'unpaid',
        ]);
        $invoice = BookingInvoice::create([
            'booking_id' => $booking->id, 'invoice_number' => 'INV-TTLOCK-1', 'invoice_type' => 'original',
            'issue_date' => '2026-09-15', 'period_from' => '2026-09-15', 'period_to' => '2026-10-15',
            'rent_amount' => 1000, 'total_amount' => 1000, 'status' => 'unpaid',
        ]);
        AppSettings::setMany(['ttlock_client_id' => 'client', 'ttlock_client_secret' => 'secret', 'ttlock_access_token' => 'token']);
        Http::fake(['api.sciener.com/*' => Http::response(['errcode' => 0, 'keyboardPwdId' => 9876])]);

        $this->actingAs($admin)->post(route('admin.smartlocks.issue', $booking), ['invoice_id' => $invoice->id])
            ->assertSessionHasErrors('invoice_id');
        $this->assertDatabaseCount('booking_lock_accesses', 0);

        $invoice->update(['status' => 'paid']);
        $this->actingAs($admin)->post(route('admin.smartlocks.issue', $booking), ['invoice_id' => $invoice->id])
            ->assertRedirect()->assertSessionHasNoErrors();
        $access = BookingLockAccess::firstOrFail();
        $this->assertSame(9876, (int) $access->remote_passcode_id);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $access->passcode);
        $this->assertDatabaseMissing('booking_lock_accesses', ['passcode' => $access->passcode]);
        $this->actingAs($tenant)->get(route('tenant.booking.show', $booking))
            ->assertOk()->assertSee($access->passcode)->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($admin)->post(route('admin.smartlocks.revoke', $access))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($access->fresh()->revoked_at);
        $this->actingAs($tenant)->get(route('tenant.booking.show', $booking))
            ->assertOk()->assertDontSee($access->passcode);
    }
}
