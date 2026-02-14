<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Workflows\CreateInvoiceWorkflow;
use Workflow\WorkflowStub;

/**
 * Listener: When order is created in Sales service,
 * trigger CreateInvoiceWorkflow to generate an invoice.
 */
class HandleOrderCreated
{
    public function handle(OrderCreated $event): void
    {
        $orderData = [
            'order_id' => $event->orderId,
            'customer_name' => $event->customerName,
            'customer_email' => $event->customerEmail,
            'total_amount' => $event->totalAmount,
            'items' => $event->items,
            'simulate_failure' => $event->simulateFailure ?? false,
        ];

        $workflow = WorkflowStub::make(CreateInvoiceWorkflow::class);
        $workflow->start($event->correlationId, $orderData);
    }
}
