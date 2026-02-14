<?php

namespace App\Workflows;

use App\Activities\ClearOrderCacheActivity;
use App\Activities\DispatchEventBusActivity;
use App\Activities\UpdateOrderStatusActivity;
use App\Events\OrderPaid;
use App\Models\Order;
use Workflow\ActivityStub;
use Workflow\Workflow;

/**
 * CompleteOrderWorkflow - Triggered when payment is confirmed.
 *
 * Business status change:
 *   submitted → paid
 *
 * Note: 'state' (event-processing lifecycle) is already 'completed' from
 * the initial NewOrderWorkflow. We only update the business 'status' here.
 *
 * After marking as paid, publishes OrderPaid to Inventory service
 * to RESERVE stock.
 */
class CompleteOrderWorkflow extends Workflow
{
    public function execute(string $correlationId)
    {
        $order = Order::findByCorrelation($correlationId);

        // Activity 1: Update business status to 'paid'
        yield ActivityStub::make(
            UpdateOrderStatusActivity::class,
            $correlationId,
            Order::STATUS_PAID
        );

        // Activity 2: Clear caches
        yield ActivityStub::make(
            ClearOrderCacheActivity::class,
            $correlationId,
            $order->id
        );

        // Activity 3: Publish OrderPaid to Inventory service to reserve stock
        // Wrapped in Activity so it only fires ONCE (not on every workflow replay)
        $eventData = [
            'correlationId' => $correlationId,
            'orderId' => $order->id,
            'customerName' => $order->customer_name,
            'totalAmount' => (float) $order->total_amount,
            'items' => $order->items,
        ];

        yield ActivityStub::make(
            DispatchEventBusActivity::class,
            OrderPaid::class,
            $eventData,
            'eventbus',
            'inventory'
        );

        return $order->id;
    }
}
