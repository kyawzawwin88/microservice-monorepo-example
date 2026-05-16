<?php

namespace App\Activities;

use App\Exceptions\InventoryProcessingException;
use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use App\Services\Stock\ResolveStockBalanceAction;
use App\Services\Stock\SyncLegacyItemStockFromBalanceAction;
use Illuminate\Support\Facades\DB;
use Workflow\Activity;

/**
 * Activity: Check and reserve stock for order items.
 */
class CheckStockActivity extends Activity
{
    public function execute(string $correlationId, array $orderData): array
    {
        $existingReservation = InventoryReservation::where('correlation_id', $correlationId)->first();
        if ($existingReservation) {
            return [
                'already_exists' => true,
                'reserved_items' => [],
            ];
        }

        $items = $orderData['items'] ?? [];
        $reservedItems = [];
        $resolveBalance = app(ResolveStockBalanceAction::class);
        $syncLegacy = app(SyncLegacyItemStockFromBalanceAction::class);

        DB::transaction(function () use ($correlationId, $orderData, $items, &$reservedItems, $resolveBalance, $syncLegacy) {
            $totalQuantity = 0;
            $productNames = [];
            $variationId = null;
            $locationId = null;

            foreach ($items as $item) {
                $productName = $item['product_name'] ?? 'Unknown';
                $quantity = (int) ($item['quantity'] ?? 0);

                if ($quantity <= 0) {
                    continue;
                }

                $itemVariationId = $item['variation_id'] ?? null;
                $itemLocationId = $resolveBalance->resolveLocationId($item['location_id'] ?? null);

                if ($itemVariationId) {
                    $balance = $resolveBalance->execute($itemVariationId, null, $itemLocationId, true);
                    if ($balance->quantity_available < $quantity) {
                        throw new InventoryProcessingException(
                            "Insufficient stock for variation {$itemVariationId}. Available: {$balance->quantity_available}, Requested: {$quantity}",
                            $correlationId
                        );
                    }
                    $balance->decrement('quantity_available', $quantity);
                    $balance->increment('quantity_reserved', $quantity);
                    $variationId = $itemVariationId;
                    $locationId = $itemLocationId;
                } else {
                    $inventoryItem = InventoryItem::where('product_name', $productName)
                        ->lockForUpdate()
                        ->first();

                    if (! $inventoryItem) {
                        throw new InventoryProcessingException(
                            "Inventory item not found: {$productName}",
                            $correlationId
                        );
                    }

                    if ($inventoryItem->has_variations) {
                        throw new InventoryProcessingException(
                            "Variation required for product: {$productName}",
                            $correlationId
                        );
                    }

                    $balance = $resolveBalance->execute(null, $inventoryItem->id, $itemLocationId, true);
                    if ($balance->quantity_available < $quantity) {
                        throw new InventoryProcessingException(
                            "Insufficient stock for product: {$productName}. Available: {$balance->quantity_available}, Requested: {$quantity}",
                            $correlationId
                        );
                    }

                    $balance->decrement('quantity_available', $quantity);
                    $balance->increment('quantity_reserved', $quantity);
                    $syncLegacy->execute($inventoryItem, $balance->fresh());
                }

                $totalQuantity += $quantity;
                $productNames[] = $productName;

                $reservedItems[] = [
                    'product_name' => $productName,
                    'quantity' => $quantity,
                    'variation_id' => $itemVariationId,
                ];
            }

            InventoryReservation::create([
                'correlation_id' => $correlationId,
                'order_id' => $orderData['order_id'],
                'product_name' => implode(', ', $productNames),
                'quantity' => $totalQuantity,
                'reserved_quantity' => $totalQuantity,
                'inventory_variation_id' => $variationId,
                'storage_location_id' => $locationId,
            ]);
        });

        return [
            'already_exists' => false,
            'reserved_items' => $reservedItems,
        ];
    }
}
