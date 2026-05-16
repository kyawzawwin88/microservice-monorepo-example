<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VariationDimension extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'name',
        'sort_order',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(VariationDimensionValue::class)->orderBy('id');
    }
}
