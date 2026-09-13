<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->date('checkout_reminder_7_day_sent_for')->nullable()->after('checkout_reminder_sent_at');
            $table->timestamp('checkout_reminder_7_day_sent_at')->nullable()->after('checkout_reminder_7_day_sent_for');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn(['checkout_reminder_7_day_sent_for', 'checkout_reminder_7_day_sent_at']));
    }
};
