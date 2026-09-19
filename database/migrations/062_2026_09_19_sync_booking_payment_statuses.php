<?php

use App\Models\Booking;
use App\Support\BookingPaymentSummary;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Booking::with(['invoices' => fn ($query) => $query->withSum('payments', 'amount')->withCount('allPayments')])
            ->chunkById(200, function ($bookings) {
                foreach ($bookings as $booking) {
                    if ($booking->invoices->isEmpty()) continue;
                    $status = BookingPaymentSummary::for($booking)['status'];
                    if ($booking->invoice_status !== $status) {
                        DB::table('bookings')->where('id', $booking->id)->update(['invoice_status' => $status]);
                    }
                }
            });
    }

    public function down(): void {}
};
