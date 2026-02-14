<?php

namespace App\Workflows;

use App\Activities\ClearOrderCacheActivity;
use App\Activities\DispatchEventBusActivity;
use App\Activities\SoftDeleteOrderActivity;
use App\Events\OrderDeleted;
use App\Exceptions\OrderProcessingException;
use Workflow\ActivityStub;
use Workflow\Workflow;

/**
 * DeleteOrderWorkflow - Orchestrates order deletion.
 *
 * This workflow:
 * 1. Soft-deletes the order record (idempotent)
 * 2. Clears order caches
 * 3. Publishes OrderDeleted event to Inventory service via event bus
 *    so reserved stock is released
 *
 * On failure the order stays intact (delete is rolled back by the activity).
 */
class DeleteOrderWorkflow extends Workflow
{
    public function execute(string $correlationId, int $orderId, string $customerName)
    {
        try {
            // Activity 1: Soft-delete the order
            $deleteResult = yield ActivityStub::make(
                SoftDeleteOrderActivity::class,
                $correlationId,
                $orderId
            );

            // Activity 2: Clear order caches
            yield ActivityStub::make(
                ClearOrderCacheActivity::class,
                $correlationId,
                $orderId
            );

            $items = $deleteResult['items'] ?? [];

            // Activity 3: Publish OrderDeleted to Inventory service to release reserved stock
            // Wrapped in Activity so it only fires ONCE (not on every workflow replay)
            $eventData = [
                'correlationId' => $correlationId,
                'orderId'       => $orderId,
                'customerName'  => $customerName,
                'items'         => $items,
            ];

            yield ActivityStub::make(
                DispatchEventBusActivity::class,
                OrderDeleted::class,
                $eventData,
                'eventbus',
                'inventory'
            );

            return $orderId;

        } catch (OrderProcessingException $e) {
            throw $e;
        }
    }
}
