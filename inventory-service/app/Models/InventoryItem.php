<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'quantity_available',
        'quantity_reserved',
        'unit_price',
    ];

    protected $casts = [
        'quantity_available' => 'integer',
        'quantity_reserved' => 'integer',
        'unit_price' => 'decimal:2',
    ];

    /**
     * Reserve stock for an order item
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
     * Release reserved stock (e.g., on order cancellation)
     */
    public function releaseStock(int $quantity): void
    {
        $this->increment('quantity_available', $quantity);
        $this->decrement('quantity_reserved', $quantity);
    }
}
