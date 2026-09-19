<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bank_statement_imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bank_account_id')->index();
            $table->string('bank', 20);
            $table->string('filename');
            $table->char('file_hash', 64);
            $table->uuid('uploaded_by')->nullable();
            $table->timestamps();
            $table->unique(['bank_account_id', 'file_hash']);
        });

        Schema::create('bank_statement_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bank_statement_import_id')->index();
            $table->uuid('bank_account_id')->index();
            $table->unsignedInteger('row_number');
            $table->date('transaction_date');
            $table->string('reference')->nullable()->index();
            $table->text('description')->nullable();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->string('status', 20)->default('unmatched')->index();
            $table->uuid('accounting_entry_id')->nullable()->unique();
            $table->uuid('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->unique(['bank_statement_import_id', 'row_number'], 'bank_statement_import_row_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_transactions');
        Schema::dropIfExists('bank_statement_imports');
    }
};
