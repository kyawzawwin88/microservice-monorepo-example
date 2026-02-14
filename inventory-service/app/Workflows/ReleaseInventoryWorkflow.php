<?php

namespace App\Workflows;

use App\Activities\ClearInventoryCacheActivity;
use App\Activities\ReleaseStockActivity;
use App\Exceptions\InventoryProcessingException;
use App\Models\InventoryReservation;
use Workflow\ActivityStub;
use Workflow\Workflow;

/**
 * ReleaseInventoryWorkflow - Releases reserved stock when an order is deleted.
 *
 * Triggered by OrderDeleted event from Sales service.
 * 1. Release reserved stock on each inventory item
 * 2. Clear inventory caches
 */
class ReleaseInventoryWorkflow extends Workflow
{
    public function execute(string $correlationId, array $orderData)
    {
        try {
            $items = $orderData['items'] ?? [];

            // Activity 1: Release reserved stock
            $releaseResult = yield ActivityStub::make(
                ReleaseStockActivity::class,
                $correlationId,
                $items
            );

            // Activity 2: Clear inventory caches
            yield ActivityStub::make(
                ClearInventoryCacheActivity::class,
                $correlationId
            );

            return $releaseResult;

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
