<?php

namespace App\Activities;

use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use App\Services\Stock\ResolveStockBalanceAction;
use App\Services\Stock\SyncLegacyItemStockFromBalanceAction;
use Illuminate\Support\Facades\DB;
use Workflow\Activity;

/**
 * Activity: Deduct reserved stock after order delivery.
 *
 * When an order is delivered, reserved quantity is removed from the balance
 * (goods have left the warehouse). Available quantity is not increased.
 */
class DeductStockActivity extends Activity
{
    public function execute(string $correlationId, array $items): array
    {
        $resolveBalance = app(ResolveStockBalanceAction::class);
        $syncLegacy = app(SyncLegacyItemStockFromBalanceAction::class);

        return DB::transaction(function () use ($correlationId, $items, $resolveBalance, $syncLegacy) {
            $reservation = InventoryReservation::where('correlation_id', $correlationId)
                ->lockForUpdate()
                ->first();

            $deductedItems = [];

            if ($reservation?->inventory_variation_id && $reservation->storage_location_id) {
                $quantity = $reservation->reserved_quantity;
                $balance = $resolveBalance->execute(
                    $reservation->inventory_variation_id,
                    null,
                    $reservation->storage_location_id,
                    true
                );

                if ($balance->quantity_reserved >= $quantity) {
                    $balance->decrement('quantity_reserved', $quantity);
                }

                $deductedItems[] = [
                    'product_name' => $reservation->product_name,
                    'quantity' => $quantity,
                    'variation_id' => $reservation->inventory_variation_id,
                ];
            } else {
                foreach ($items as $item) {
                    $productName = $item['product_name'] ?? 'Unknown';
                    $quantity = (int) ($item['quantity'] ?? 0);

                    if ($quantity <= 0) {
                        continue;
                    }

                    $inventoryItem = InventoryItem::where('product_name', $productName)
                        ->lockForUpdate()
                        ->first();

                    if (! $inventoryItem || $inventoryItem->has_variations) {
                        continue;
                    }

                    $locationId = $reservation?->storage_location_id
                        ?? $resolveBalance->resolveLocationId($item['location_id'] ?? null);

                    $balance = $resolveBalance->execute(null, $inventoryItem->id, $locationId, true);

                    if ($balance->quantity_reserved >= $quantity) {
                        $balance->decrement('quantity_reserved', $quantity);
                        $syncLegacy->execute($inventoryItem, $balance->fresh());
                    }

                    $deductedItems[] = [
                        'product_name' => $productName,
                        'quantity' => $quantity,
                        'inventory_item_id' => $inventoryItem->id,
                    ];
                }
            }

            if ($reservation && $reservation->state::$name !== 'failed') {
                $reservation->update([
                    'state_failure_description' => null,
                ]);
            }

            return [
                'deducted_items' => $deductedItems,
            ];
        });
    }
}
