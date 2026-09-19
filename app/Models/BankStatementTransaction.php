<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatementTransaction extends BaseModel
{
    protected $fillable = ['bank_statement_import_id', 'bank_account_id', 'row_number', 'transaction_date', 'reference', 'description', 'debit', 'credit', 'status', 'accounting_entry_id', 'confirmed_by', 'confirmed_at'];
    protected $casts = ['transaction_date' => 'date', 'confirmed_at' => 'datetime', 'debit' => 'decimal:2', 'credit' => 'decimal:2'];

    public function import(): BelongsTo { return $this->belongsTo(BankStatementImport::class, 'bank_statement_import_id'); }
    public function entry(): BelongsTo { return $this->belongsTo(AccountingEntry::class, 'accounting_entry_id'); }
}
