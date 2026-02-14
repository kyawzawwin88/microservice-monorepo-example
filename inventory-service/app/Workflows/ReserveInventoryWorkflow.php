<?php

namespace App\Workflows;

use App\Activities\CheckStockActivity;
use App\Activities\ClearInventoryCacheActivity;
use App\Activities\UpdateInventoryStateActivity;
use App\Events\InventoryReserved;
use App\Exceptions\InventoryProcessingException;
use App\Jobs\EventBusMessage;
use App\Models\InventoryReservation;
use Workflow\ActivityStub;
use Workflow\Workflow;

/**
 * ReserveInventoryWorkflow - Reserves stock for an order.
 *
 * Triggered by OrderCreated event from Sales service.
 * 1. Check stock availability and reserve (idempotent)
 * 2. Create reservation record with 'completed' state
 * 3. Clear inventory caches
 * 4. Publish InventoryReserved event (for audit / future use)
 */
class ReserveInventoryWorkflow extends Workflow
{
    public function execute(string $correlationId, array $orderData)
    {
        try {
            // Activity 1: Check and reserve stock
            $stockResult = yield ActivityStub::make(
                CheckStockActivity::class,
                $correlationId,
                $orderData
            );

            // Activity 2: Create/update reservation record
            $reservationId = yield ActivityStub::make(
                UpdateInventoryStateActivity::class,
                $correlationId,
                $orderData,
                $stockResult['reserved_items'],
                'completed'
            );

            // Activity 3: Clear inventory caches
            yield ActivityStub::make(
                ClearInventoryCacheActivity::class,
                $correlationId
            );

            // Fire InventoryReserved locally for logging
            InventoryReserved::dispatch(
                $correlationId,
                $orderData['order_id'],
                $stockResult['reserved_items'],
            );

            return $reservationId;

        } catch (InventoryProcessingException $e) {
            $reservation = InventoryReservation::where('correlation_id', $correlationId)->first();
            if ($reservation) {
                $reservation->markAsFailed($e->getMessage());
                $reservation->state->transitionTo(\App\States\FailedState::class);
            }
            throw $e;
        }
    }
}
