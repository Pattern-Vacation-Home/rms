<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class BookingLockAccess extends BaseModel
{
    protected $fillable = ['booking_id', 'booking_invoice_id', 'smartlock_id', 'remote_passcode_id', 'passcode', 'starts_at', 'ends_at', 'issued_by', 'revoked_at'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'revoked_at' => 'datetime'];

    protected function passcode(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    public function invoice(): BelongsTo { return $this->belongsTo(BookingInvoice::class, 'booking_invoice_id'); }
    public function smartlock(): BelongsTo { return $this->belongsTo(Smartlock::class); }
}
