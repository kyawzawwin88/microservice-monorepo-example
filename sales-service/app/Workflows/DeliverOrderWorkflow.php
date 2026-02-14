<?php

namespace App\Workflows;

use App\Activities\ClearOrderCacheActivity;
use App\Activities\DispatchEventBusActivity;
use App\Activities\UpdateOrderStatusActivity;
use App\Events\OrderDelivered;
use App\Models\Order;
use Workflow\ActivityStub;
use Workflow\Workflow;

/**
 * DeliverOrderWorkflow - Triggered manually when order is marked as delivered.
 *
 * Business status change:
 *   paid → delivered
 *
 * Note: 'state' (event-processing lifecycle) is already 'completed'.
 * We only update the business 'status' here.
 *
 * After marking as delivered, publishes OrderDelivered to Inventory service
 * to DEDUCT reserved stock (goods have left the warehouse).
 */
class DeliverOrderWorkflow extends Workflow
{
    public function execute(string $correlationId, int $orderId)
    {
        $order = Order::findOrFail($orderId);

        // Activity 1: Update business status to 'delivered'
        yield ActivityStub::make(
            UpdateOrderStatusActivity::class,
            $correlationId,
            Order::STATUS_DELIVERED
        );

        // Activity 2: Clear caches
        yield ActivityStub::make(
            ClearOrderCacheActivity::class,
            $correlationId,
            $orderId
        );

        // Activity 3: Publish OrderDelivered to Inventory service to deduct reserved stock
        // Wrapped in Activity so it only fires ONCE (not on every workflow replay)
        $eventData = [
            'correlationId' => $correlationId,
            'orderId' => $orderId,
            'customerName' => $order->customer_name,
            'items' => $order->items,
        ];

        yield ActivityStub::make(
            DispatchEventBusActivity::class,
            OrderDelivered::class,
            $eventData,
            'eventbus',
            'inventory'
        );

        return $orderId;
    }
}
