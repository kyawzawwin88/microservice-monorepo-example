<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class InventoryVariation extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'sku',
        'attribute_hash',
        'label',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function dimensionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            VariationDimensionValue::class,
            'inventory_variation_dimension_value',
            'inventory_variation_id',
            'variation_dimension_value_id'
        );
    }

    public function stockBalances(): MorphMany
    {
        return $this->morphMany(StockBalance::class, 'balanceable');
    }
}
