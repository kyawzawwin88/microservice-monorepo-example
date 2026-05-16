<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    public const TYPE_STOCK_IN = 'stock_in';

    public const TYPE_STOCK_OUT = 'stock_out';

    public const TYPE_TRANSFER = 'transfer';

    protected $fillable = [
        'movement_type',
        'balanceable_type',
        'balanceable_id',
        'storage_location_id',
        'source_location_id',
        'destination_location_id',
        'quantity',
        'reference',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function balanceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class);
    }

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class, 'source_location_id');
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class, 'destination_location_id');
    }
}
