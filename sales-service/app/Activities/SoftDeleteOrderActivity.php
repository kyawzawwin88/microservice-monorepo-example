<?php

namespace App\Activities;

use App\Exceptions\OrderProcessingException;
use App\Models\Order;
use Workflow\Activity;

/**
 * Activity: Soft-delete the order record.
 * Ensures idempotency — if already soft-deleted, returns success.
 */
class SoftDeleteOrderActivity extends Activity
{
    public function execute(string $correlationId, int $orderId): array
    {
        $order = Order::withTrashed()->find($orderId);

        if (!$order) {
            throw new OrderProcessingException(
                "Order #{$orderId} not found",
                $correlationId
            );
        }

        // Idempotency: already deleted
        if ($order->trashed()) {
            return [
                'already_deleted' => true,
                'order_id' => $order->id,
                'items' => $order->items ?? [],
            ];
        }

        $items = $order->items ?? [];

        $order->delete(); // soft-delete

        return [
            'already_deleted' => false,
            'order_id' => $order->id,
            'items' => $items,
        ];
    }
}
