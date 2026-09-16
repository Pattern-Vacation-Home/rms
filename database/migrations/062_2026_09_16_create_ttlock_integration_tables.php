<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('smartlocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('remote_id')->unique();
            $table->string('name');
            $table->string('alias')->nullable();
            $table->string('mac')->nullable();
            $table->unsignedTinyInteger('battery')->nullable();
            $table->unsignedTinyInteger('passcode_version')->nullable();
            $table->boolean('has_gateway')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_lock_accesses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_id')->index();
            $table->uuid('booking_invoice_id')->unique();
            $table->uuid('smartlock_id');
            $table->unsignedBigInteger('remote_passcode_id');
            $table->text('passcode');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('revoked_at')->nullable();
            $table->uuid('issued_by')->nullable();
            $table->timestamps();
            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $table->foreign('booking_invoice_id')->references('id')->on('booking_invoices')->cascadeOnDelete();
            $table->foreign('smartlock_id')->references('id')->on('smartlocks');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_lock_accesses');
        Schema::dropIfExists('smartlocks');
    }
};
