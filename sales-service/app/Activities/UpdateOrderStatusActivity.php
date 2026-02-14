<?php

namespace App\Activities;

use App\Models\Order;
use Workflow\Activity;

/**
 * Activity: Update the business STATUS of an order.
 *
 * 'status' tracks the business lifecycle: submitted → paid → delivered.
 * This is separate from 'state' which tracks microservice event processing.
 *
 * Idempotent — if the order already has the target status, no change is made.
 */
class UpdateOrderStatusActivity extends Activity
{
    public function execute(string $correlationId, string $targetStatus): int
    {
        $order = Order::where('correlation_id', $correlationId)->firstOrFail();

        if ($order->status !== $targetStatus) {
            $order->update(['status' => $targetStatus]);
        }

        return $order->id;
    }
}
