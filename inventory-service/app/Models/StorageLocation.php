<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageLocation extends Model
{
    protected $fillable = [
        'name',
        'code',
        'address_street',
        'country_code',
        'postal_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public static function defaultLocation(): ?self
    {
        return static::where('code', 'DEFAULT')->first();
    }
}
