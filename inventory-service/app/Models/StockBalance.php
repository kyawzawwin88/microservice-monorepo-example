<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockBalance extends Model
{
    protected $fillable = [
        'balanceable_type',
        'balanceable_id',
        'storage_location_id',
        'quantity_available',
        'quantity_reserved',
    ];

    protected $casts = [
        'quantity_available' => 'integer',
        'quantity_reserved' => 'integer',
    ];

    public function balanceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class);
    }

    public function transferableQuantity(): int
    {
        return max(0, $this->quantity_available - $this->quantity_reserved);
    }
}
