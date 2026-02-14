<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Workflows\CompleteOrderWorkflow;
use Workflow\WorkflowStub;

/**
 * Listener: When payment is completed, trigger CompleteOrderWorkflow
 * to finalize the order state.
 */
class HandlePaymentCompleted
{
    public function handle(PaymentCompleted $event): void
    {
        // Trigger the CompleteOrderWorkflow via durable workflow engine
        $workflow = WorkflowStub::make(CompleteOrderWorkflow::class);
        $workflow->start($event->correlationId);
    }
}
