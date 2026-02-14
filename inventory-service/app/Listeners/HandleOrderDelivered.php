<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Workflows\DeductInventoryWorkflow;
use Workflow\WorkflowStub;

/**
 * Listener: When order is delivered, trigger DeductInventoryWorkflow
 * to deduct reserved stock (goods have left the warehouse).
 */
class HandleOrderDelivered
{
    public function handle(OrderDelivered $event): void
    {
        $workflow = WorkflowStub::make(DeductInventoryWorkflow::class);
        $workflow->start($event->correlationId, $event->items);
    }
}
