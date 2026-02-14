<?php

namespace App\Activities;

use App\Exceptions\InventoryProcessingException;
use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use Illuminate\Support\Facades\DB;
use Workflow\Activity;

/**
 * Activity: Check and reserve stock for order items.
 *
 * Ensures idempotency by creating the InventoryReservation record
 * atomically with the stock modification inside a DB transaction.
 * On retry, the reservation already exists → skip.
 */
class CheckStockActivity extends Activity
{
    public function execute(string $correlationId, array $orderData): array
    {
        // Idempotency: skip if reservation already exists for this correlation_id
        $existingReservation = InventoryReservation::where('correlation_id', $correlationId)->first();
        if ($existingReservation) {
            return [
                'already_exists' => true,
                'reserved_items' => [],
            ];
        }

        $items = $orderData['items'] ?? [];
        $reservedItems = [];

        // Use a DB transaction to ensure stock modification + reservation
        // creation happen atomically. On retry, the reservation will exist
        // and the idempotency check above will prevent double-reservation.
        DB::transaction(function () use ($correlationId, $orderData, $items, &$reservedItems) {
            $totalQuantity = 0;
            $productNames = [];

            foreach ($items as $item) {
                $productName = $item['product_name'] ?? 'Unknown';
                $quantity = (int) ($item['quantity'] ?? 0);

                if ($quantity <= 0) {
                    continue;
                }

                // Lock the row to prevent concurrent modifications
                $inventoryItem = InventoryItem::where('product_name', $productName)
                    ->lockForUpdate()
                    ->first();

                if (!$inventoryItem) {
                    throw new InventoryProcessingException(
                        "Inventory item not found: {$productName}",
                        $correlationId
                    );
                }

                if ($inventoryItem->quantity_available < $quantity) {
                    throw new InventoryProcessingException(
                        "Insufficient stock for product: {$productName}. Available: {$inventoryItem->quantity_available}, Requested: {$quantity}",
                        $correlationId
                    );
                }

                // Reserve stock
                $inventoryItem->decrement('quantity_available', $quantity);
                $inventoryItem->increment('quantity_reserved', $quantity);

                $totalQuantity += $quantity;
                $productNames[] = $productName;

                $reservedItems[] = [
                    'product_name' => $productName,
                    'quantity' => $quantity,
                    'inventory_item_id' => $inventoryItem->id,
                ];
            }

            // Create the reservation record IN THE SAME TRANSACTION
            // so that on retry, the idempotency check will find it.
            InventoryReservation::create([
                'correlation_id' => $correlationId,
                'order_id' => $orderData['order_id'],
                'product_name' => implode(', ', $productNames),
                'quantity' => $totalQuantity,
                'reserved_quantity' => $totalQuantity,
            ]);
        });

        return [
            'already_exists' => false,
            'reserved_items' => $reservedItems,
        ];
    }
}
