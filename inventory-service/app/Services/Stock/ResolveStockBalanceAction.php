<?php

namespace App\Services\Stock;

use App\Models\InventoryItem;
use App\Models\InventoryVariation;
use App\Models\StockBalance;
use App\Models\StorageLocation;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ResolveStockBalanceAction
{
    public function execute(
        ?int $inventoryVariationId,
        ?int $inventoryItemId,
        int $storageLocationId,
        bool $lock = false,
    ): StockBalance {
        if ($inventoryVariationId) {
            $variation = InventoryVariation::findOrFail($inventoryVariationId);
            $query = StockBalance::query()
                ->where('balanceable_type', InventoryVariation::class)
                ->where('balanceable_id', $variation->id)
                ->where('storage_location_id', $storageLocationId);
        } elseif ($inventoryItemId) {
            $item = InventoryItem::findOrFail($inventoryItemId);
            if ($item->has_variations) {
                throw new ModelNotFoundException('Variation required for variation-based item.');
            }
            $query = StockBalance::query()
                ->where('balanceable_type', InventoryItem::class)
                ->where('balanceable_id', $item->id)
                ->where('storage_location_id', $storageLocationId);
        } else {
            throw new ModelNotFoundException('Product identifier required.');
        }

        if ($lock) {
            $query->lockForUpdate();
        }

        $balanceableType = $inventoryVariationId
            ? InventoryVariation::class
            : InventoryItem::class;
        $balanceableId = $inventoryVariationId ?? $inventoryItemId;

        return $query->firstOrCreate(
            [
                'balanceable_type' => $balanceableType,
                'balanceable_id' => $balanceableId,
                'storage_location_id' => $storageLocationId,
            ],
            [
                'quantity_available' => 0,
                'quantity_reserved' => 0,
            ]
        );
    }

    public function resolveLocationId(?int $locationId): int
    {
        if ($locationId) {
            return $locationId;
        }

        $default = StorageLocation::defaultLocation();
        if (! $default) {
            throw new ModelNotFoundException('No default storage location configured.');
        }

        return $default->id;
    }
}
