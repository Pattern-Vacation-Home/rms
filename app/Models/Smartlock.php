<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;

class Smartlock extends BaseModel
{
    protected $fillable = ['remote_id', 'name', 'alias', 'mac', 'battery', 'passcode_version', 'has_gateway', 'last_synced_at'];

    protected $casts = ['has_gateway' => 'boolean', 'last_synced_at' => 'datetime'];

    public function property(): HasOne
    {
        return $this->hasOne(Property::class, 'smartlock_id');
    }
}
