<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Workflows\ProcessPaymentWorkflow;
use Workflow\WorkflowStub;

/**
 * Listener: When invoice is created, trigger ProcessPaymentWorkflow.
 */
class HandleInvoiceCreated
{
    public function handle(InvoiceCreated $event): void
    {
        $invoiceData = [
            'invoice_id' => $event->invoiceId,
            'invoice_number' => $event->invoiceNumber,
            'order_id' => $event->orderId,
            'customer_name' => $event->customerName,
            'customer_email' => $event->customerEmail,
            'amount' => $event->amount,
            'line_items' => $event->lineItems,
            'simulate_failure' => $event->simulateFailure ?? false,
        ];

        $workflow = WorkflowStub::make(ProcessPaymentWorkflow::class);
        $workflow->start($event->correlationId, $invoiceData);
    }
}
