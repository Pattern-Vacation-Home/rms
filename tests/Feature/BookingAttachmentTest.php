<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_a_booking_attachment_from_the_media_disk(): void
    {
        Storage::fake('public');
        config()->set('hhms.media_disk', 'public');

        $admin = User::factory()->create(['role' => 'admin']);
        $landlord = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['name' => 'Unit 502', 'landlord_id' => $landlord->id]);
        $path = 'booking_documents/2026/09/18/guest-passport.jpeg';
        Storage::disk('public')->put($path, 'booking-document-content');
        $booking = Booking::create([
            'booking_reference' => 'BK-ATTACHMENT-01',
            'property_id' => $property->id,
            'guest_name' => 'Test Guest',
            'guest_email' => 'guest@example.test',
            'guest_phone' => '+971500000000',
            'guest_passport_id_no' => 'P1234567',
            'guest_document' => $path,
            'check_in' => '2026-09-18',
            'check_out' => '2026-10-18',
            'invoice_number' => 'INV-ATTACHMENT-01',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.booking.attachment', $booking));

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'inline; filename="guest-passport.jpeg"');
        $this->assertSame('booking-document-content', $response->streamedContent());
    }

    public function test_booking_attachment_requires_an_authenticated_admin(): void
    {
        $landlord = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['name' => 'Unit 503', 'landlord_id' => $landlord->id]);
        $booking = Booking::create([
            'booking_reference' => 'BK-ATTACHMENT-02',
            'property_id' => $property->id,
            'guest_name' => 'Private Guest',
            'guest_email' => 'private@example.test',
            'guest_phone' => '+971500000001',
            'guest_passport_id_no' => 'P7654321',
            'guest_document' => 'booking_documents/private.pdf',
            'check_in' => '2026-09-18',
            'check_out' => '2026-10-18',
            'invoice_number' => 'INV-ATTACHMENT-02',
        ]);

        $this->get(route('admin.booking.attachment', $booking))
            ->assertRedirect(route('login'));
    }
}
