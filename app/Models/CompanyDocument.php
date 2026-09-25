<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyDocument extends BaseModel
{
    public const TYPES = [
        'trade_licence' => 'Trade Licence',
        'establishment_card' => 'Establishment Card',
        'vat_certificate' => 'VAT Registration Certificate',
        'dtcm_licence' => 'DTCM Licence',
        'insurance' => 'Company Insurance',
        'custom' => 'Custom Document',
    ];

    protected $fillable = [
        'type', 'custom_title', 'reference_no', 'issue_date', 'expires_at',
        'file_path', 'original_name', 'notes', 'uploaded_by',
    ];

    protected $casts = ['issue_date' => 'date', 'expires_at' => 'date'];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getTitleAttribute(): string
    {
        return $this->type === 'custom'
            ? ($this->custom_title ?: 'Custom Document')
            : (self::TYPES[$this->type] ?? (string) str($this->type)->headline());
    }

    public function getExpiryStatusAttribute(): string
    {
        if (! $this->expires_at) return 'no_expiry';
        if ($this->expires_at->isPast()) return 'expired';
        if ($this->expires_at->lte(now()->addDays(30))) return 'expiring';

        return 'valid';
    }
}
