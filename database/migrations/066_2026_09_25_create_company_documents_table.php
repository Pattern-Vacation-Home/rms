<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('custom_title')->nullable();
            $table->string('reference_no')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_documents');
    }
};
