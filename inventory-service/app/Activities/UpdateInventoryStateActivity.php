<?php

namespace App\Activities;

use App\Models\InventoryReservation;
use App\States\CompletedState;
use App\States\FailedState;
use App\States\RequestedState;
use Workflow\Activity;

/**
 * Activity: Transition the state of an existing inventory reservation.
 *
 * The reservation record is created atomically by CheckStockActivity.
 * This activity only transitions its state (e.g. requested → completed).
 */
class UpdateInventoryStateActivity extends Activity
{
    public function execute(
        string $correlationId,
        array $orderData,
        array $reservedItems,
        string $targetState = 'completed'
    ): int {
        $reservation = InventoryReservation::where('correlation_id', $correlationId)->first();

        if (!$reservation) {
            // Reservation should already exist (created by CheckStockActivity).
            // If missing, the workflow likely skipped stock reservation (already_exists),
            // so there's nothing to transition.
            return 0;
        }

        $stateMap = [
            'requested' => RequestedState::class,
            'completed' => CompletedState::class,
            'failed' => FailedState::class,
        ];

        if (isset($stateMap[$targetState]) && $reservation->state::$name !== $targetState) {
            $reservation->state->transitionTo($stateMap[$targetState]);
        }

        // Clear failure description when transitioning away from failed
        if ($targetState !== 'failed') {
            $reservation->update(['state_failure_description' => null]);
        }

        return $reservation->id;
    }
}
