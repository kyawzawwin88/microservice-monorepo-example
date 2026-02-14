<?php

namespace App\Activities;

use App\Models\Invoice;
use App\States\CompletedState;
use App\States\FailedState;
use App\States\RequestedState;
use Workflow\Activity;

/**
 * Activity: Update invoice state.
 */
class UpdateInvoiceStateActivity extends Activity
{
    public function execute(string $correlationId, string $targetState = 'requested'): int
    {
        $invoice = Invoice::findByCorrelation($correlationId);

        $stateMap = [
            'requested' => RequestedState::class,
            'completed' => CompletedState::class,
            'failed' => FailedState::class,
        ];

        if (isset($stateMap[$targetState]) && $invoice->state::$name !== $targetState) {
            $invoice->state->transitionTo($stateMap[$targetState]);
        }

        // Clear failure description when transitioning away from failed
        if ($targetState !== 'failed') {
            $invoice->update(['state_failure_description' => null]);
        }

        return $invoice->id;
    }
}
