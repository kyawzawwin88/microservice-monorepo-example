<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represents a product in the inventory with available stock.
 */
class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_name',
        'sku',
        'has_variations',
        'quantity_available',
        'quantity_reserved',
        'unit_price',
    ];

    protected $casts = [
        'has_variations' => 'boolean',
        'quantity_available' => 'integer',
        'quantity_reserved' => 'integer',
        'unit_price' => 'decimal:2',
    ];

    public function variationDimensions(): HasMany
    {
        return $this->hasMany(VariationDimension::class)->orderBy('sort_order');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(InventoryVariation::class);
    }

    public function stockBalances(): MorphMany
    {
        return $this->morphMany(StockBalance::class, 'balanceable');
    }

    /**
     * Reserve stock for an order item (legacy column sync).
     */
    public function reserveStock(int $quantity): bool
    {
        if ($this->quantity_available < $quantity) {
            return false;
        }

        $this->decrement('quantity_available', $quantity);
        $this->increment('quantity_reserved', $quantity);

        return true;
    }

    /**
     * Release reserved stock (e.g., on order cancellation).
     */
    public function releaseStock(int $quantity): void
    {
        $this->increment('quantity_available', $quantity);
        $this->decrement('quantity_reserved', $quantity);
    }
}
