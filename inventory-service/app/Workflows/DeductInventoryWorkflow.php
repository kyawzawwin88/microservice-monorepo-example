<?php

namespace App\Workflows;

use App\Activities\ClearInventoryCacheActivity;
use App\Activities\DeductStockActivity;
use Workflow\ActivityStub;
use Workflow\Workflow;

/**
 * DeductInventoryWorkflow - Deducts reserved stock after order delivery.
 *
 * Triggered by OrderDelivered event from Sales service.
 * 1. Deduct reserved stock (goods left warehouse)
 * 2. Clear inventory caches
 */
class DeductInventoryWorkflow extends Workflow
{
    public function execute(string $correlationId, array $orderItems)
    {
        // Activity 1: Deduct reserved stock
        $result = yield ActivityStub::make(
            DeductStockActivity::class,
            $correlationId,
            $orderItems
        );

        // Activity 2: Clear inventory caches
        yield ActivityStub::make(
            ClearInventoryCacheActivity::class,
            $correlationId
        );

        return $result;
    }
}
