<?php

namespace App\Services\Stock;

use App\Exceptions\InsufficientStockException;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransferStockAction
{
    public function __construct(
        private readonly ResolveStockBalanceAction $resolveBalance,
        private readonly SyncLegacyItemStockFromBalanceAction $syncLegacy,
    ) {}

    /**
     * @return array{movement: StockMovement, source_balance: \App\Models\StockBalance, destination_balance: \App\Models\StockBalance}
     */
    public function execute(
        ?int $inventoryVariationId,
        ?int $inventoryItemId,
        int $sourceLocationId,
        int $destinationLocationId,
        int $quantity,
    ): array {
        if ($sourceLocationId === $destinationLocationId) {
            throw new InvalidArgumentException('Source and destination locations must differ.');
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Transfer quantity must be positive.');
        }

        return DB::transaction(function () use ($inventoryVariationId, $inventoryItemId, $sourceLocationId, $destinationLocationId, $quantity) {
            $source = $this->resolveBalance->execute($inventoryVariationId, $inventoryItemId, $sourceLocationId, true);
            $transferable = $source->transferableQuantity();

            if ($transferable < $quantity) {
                throw new InsufficientStockException(
                    "Insufficient transferable stock at source. Available: {$transferable}, requested: {$quantity}"
                );
            }

            $destination = $this->resolveBalance->execute($inventoryVariationId, $inventoryItemId, $destinationLocationId, true);

            $source->decrement('quantity_available', $quantity);
            $destination->increment('quantity_available', $quantity);

            $movement = StockMovement::create([
                'movement_type' => StockMovement::TYPE_TRANSFER,
                'balanceable_type' => $source->balanceable_type,
                'balanceable_id' => $source->balanceable_id,
                'source_location_id' => $sourceLocationId,
                'destination_location_id' => $destinationLocationId,
                'quantity' => $quantity,
            ]);

            $sourceFresh = $source->fresh();
            $destinationFresh = $destination->fresh();

            if ($source->balanceable_type === \App\Models\InventoryItem::class) {
                $this->syncLegacy->execute($source->balanceable, $sourceFresh);
            }

            return [
                'movement' => $movement,
                'source_balance' => $sourceFresh,
                'destination_balance' => $destinationFresh,
            ];
        });
    }
}
