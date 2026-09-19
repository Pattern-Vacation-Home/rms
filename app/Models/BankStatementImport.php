<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatementImport extends BaseModel
{
    protected $fillable = ['bank_account_id', 'bank', 'filename', 'file_hash', 'uploaded_by'];

    public function account(): BelongsTo { return $this->belongsTo(BankAccount::class, 'bank_account_id'); }
    public function transactions(): HasMany { return $this->hasMany(BankStatementTransaction::class); }
}
