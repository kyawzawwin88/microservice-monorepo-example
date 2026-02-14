<?php

namespace App\Activities;

use App\Models\Payment;
use App\States\CompletedState;
use App\States\FailedState;
use App\States\RequestedState;
use Workflow\Activity;

/**
 * Activity: Update payment state.
 */
class UpdatePaymentStateActivity extends Activity
{
    public function execute(string $correlationId, string $targetState = 'requested'): int
    {
        $payment = Payment::findByCorrelation($correlationId);

        $stateMap = [
            'requested' => RequestedState::class,
            'completed' => CompletedState::class,
            'failed' => FailedState::class,
        ];

        if (isset($stateMap[$targetState]) && $payment->state::$name !== $targetState) {
            $payment->state->transitionTo($stateMap[$targetState]);
        }

        // Clear failure description when transitioning away from failed
        if ($targetState !== 'failed') {
            $payment->update(['state_failure_description' => null]);
        }

        return $payment->id;
    }
}
