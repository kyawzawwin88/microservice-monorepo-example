<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\InventoryReservation;
use App\Workflows\ReserveInventoryWorkflow;
use Workflow\WorkflowStub;

/**
 * Listener: When order is paid, trigger ReserveInventoryWorkflow
 * to reserve stock for the order items.
 *
 * Stock reservation only happens after payment is confirmed,
 * not at order creation time.
 *
 * Idempotency: If a reservation already exists for this correlation ID
 * (e.g. eventbus re-delivered the message), we skip workflow creation.
 */
class HandleOrderPaid
{
    public function handle(OrderPaid $event): void
    {
        // Idempotency guard: prevent duplicate workflow starts
        if (InventoryReservation::where('correlation_id', $event->correlationId)->exists()) {
            return;
        }

        $orderData = [
            'order_id' => $event->orderId,
            'customer_name' => $event->customerName,
            'total_amount' => $event->totalAmount,
            'items' => $event->items,
        ];

        $workflow = WorkflowStub::make(ReserveInventoryWorkflow::class);
        $workflow->start($event->correlationId, $orderData);
    }
}
