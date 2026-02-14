<?php

namespace App\Listeners;

use App\Events\OrderDeleted;
use App\Workflows\ReleaseInventoryWorkflow;
use Workflow\WorkflowStub;

/**
 * Listener: When an order is deleted, trigger ReleaseInventoryWorkflow
 * to release previously reserved stock for the order items.
 */
class HandleOrderDeleted
{
    public function handle(OrderDeleted $event): void
    {
        $orderData = [
            'order_id' => $event->orderId,
            'customer_name' => $event->customerName,
            'items' => $event->items,
        ];

        $workflow = WorkflowStub::make(ReleaseInventoryWorkflow::class);
        $workflow->start($event->correlationId, $orderData);
    }
}
