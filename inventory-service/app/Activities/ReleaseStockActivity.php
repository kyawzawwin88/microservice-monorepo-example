<?php

namespace App\Activities;

use App\Exceptions\InventoryProcessingException;
use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use Workflow\Activity;

/**
 * Activity: Release reserved stock for a deleted order.
 * Finds the reservation by correlation_id, releases each item's reserved
 * quantity back to available, and marks the reservation as cancelled.
 * Idempotent — if no reservation exists or already released, succeeds silently.
 */
class ReleaseStockActivity extends Activity
{
    public function execute(string $correlationId, array $items): array
    {
        // Find reservation for this order
        $reservation = InventoryReservation::where('correlation_id', $correlationId)->first();

        $releasedItems = [];

        // Release stock for each item in the order
        foreach ($items as $item) {
            $productName = $item['product_name'] ?? 'Unknown';
            $quantity = $item['quantity'] ?? 0;

            if ($quantity <= 0) {
                continue;
            }

            $inventoryItem = InventoryItem::where('product_name', $productName)->first();

            if ($inventoryItem && $inventoryItem->quantity_reserved >= $quantity) {
                $inventoryItem->releaseStock($quantity);

                $releasedItems[] = [
                    'product_name' => $productName,
                    'quantity' => $quantity,
                    'inventory_item_id' => $inventoryItem->id,
                ];
            }
        }

        // Mark reservation as failed/cancelled if it exists
        if ($reservation) {
            $reservation->state->transitionTo(\App\States\FailedState::class);
            $reservation->update([
                'state_failure_description' => 'Order deleted — stock released',
            ]);
        }

        return [
            'released_items' => $releasedItems,
            'reservation_cancelled' => $reservation !== null,
        ];
    }
}
