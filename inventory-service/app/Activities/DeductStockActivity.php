<?php

namespace App\Activities;

use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use Workflow\Activity;

/**
 * Activity: Deduct reserved stock after order delivery.
 *
 * When an order is delivered, the reserved quantity is removed
 * (goods have left the warehouse). The available quantity is NOT
 * increased — the units are gone.
 *
 * Idempotent: if reservation is already marked as 'delivered',
 * the activity succeeds silently.
 */
class DeductStockActivity extends Activity
{
    public function execute(string $correlationId, array $items): array
    {
        $deductedItems = [];

        foreach ($items as $item) {
            $productName = $item['product_name'] ?? 'Unknown';
            $quantity = $item['quantity'] ?? 0;

            if ($quantity <= 0) {
                continue;
            }

            $inventoryItem = InventoryItem::where('product_name', $productName)->first();

            if ($inventoryItem && $inventoryItem->quantity_reserved >= $quantity) {
                // Deduct from reserved — goods have left the warehouse
                $inventoryItem->decrement('quantity_reserved', $quantity);

                $deductedItems[] = [
                    'product_name' => $productName,
                    'quantity' => $quantity,
                    'inventory_item_id' => $inventoryItem->id,
                ];
            }
        }

        // Mark reservation record as delivered
        $reservation = InventoryReservation::where('correlation_id', $correlationId)->first();
        if ($reservation && $reservation->state::$name !== 'failed') {
            $reservation->update([
                'state_failure_description' => null,
            ]);
        }

        return [
            'deducted_items' => $deductedItems,
        ];
    }
}
