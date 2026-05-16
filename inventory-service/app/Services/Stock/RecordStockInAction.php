<?php

namespace App\Services\Stock;

use App\Models\InventoryItem;
use App\Models\InventoryVariation;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class RecordStockInAction
{
    public function __construct(
        private readonly ResolveStockBalanceAction $resolveBalance,
        private readonly SyncLegacyItemStockFromBalanceAction $syncLegacy,
    ) {}

    /**
     * @return array{balance: \App\Models\StockBalance, movement: StockMovement}
     */
    public function execute(
        ?int $inventoryVariationId,
        ?int $inventoryItemId,
        int $storageLocationId,
        int $quantity,
        ?string $reference = null,
    ): array {
        return DB::transaction(function () use ($inventoryVariationId, $inventoryItemId, $storageLocationId, $quantity, $reference) {
            $locationId = $this->resolveBalance->resolveLocationId($storageLocationId);
            $balance = $this->resolveBalance->execute($inventoryVariationId, $inventoryItemId, $locationId, true);
            $balance->increment('quantity_available', $quantity);

            $movement = StockMovement::create([
                'movement_type' => StockMovement::TYPE_STOCK_IN,
                'balanceable_type' => $balance->balanceable_type,
                'balanceable_id' => $balance->balanceable_id,
                'storage_location_id' => $locationId,
                'quantity' => $quantity,
                'reference' => $reference,
            ]);

            if ($balance->balanceable_type === InventoryItem::class) {
                $this->syncLegacy->execute($balance->balanceable, $balance->fresh());
            }

            return ['balance' => $balance->fresh(), 'movement' => $movement];
        });
    }
}
