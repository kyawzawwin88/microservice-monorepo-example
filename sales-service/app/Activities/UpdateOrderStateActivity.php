<?php

namespace App\Activities;

use App\Models\Order;
use App\States\CompletedState;
use App\States\FailedState;
use App\States\RequestedState;
use Workflow\Activity;

/**
 * Activity: Create or update an order record and manage its microservice STATE.
 *
 * 'state' tracks the event-processing lifecycle: requested → completed | failed.
 * Business status (submitted/paid/delivered) is handled by UpdateOrderStatusActivity.
 */
class UpdateOrderStateActivity extends Activity
{
    public function execute(
        string $correlationId,
        array $orderData,
        string $targetState = 'requested',
        string $initialStatus = 'submitted'
    ): int {
        $order = Order::where('correlation_id', $correlationId)->first();

        if (!$order) {
            // Create new order — state defaults to 'requested', status to the given initial value
            $order = Order::create([
                'correlation_id' => $correlationId,
                'customer_name' => $orderData['customer_name'],
                'customer_email' => $orderData['customer_email'] ?? '',
                'total_amount' => $orderData['total_amount'],
                'items' => $orderData['items'],
                'status' => $initialStatus,
                // 'state' defaults to 'requested' via OrderState config
            ]);
        }

        // Transition event-processing state if needed
        $stateMap = [
            'requested' => RequestedState::class,
            'completed' => CompletedState::class,
            'failed' => FailedState::class,
        ];

        if (isset($stateMap[$targetState]) && $order->state::$name !== $targetState) {
            $order->state->transitionTo($stateMap[$targetState]);
        }

        // Clear failure description when transitioning away from failed
        if ($targetState !== 'failed') {
            $order->update(['state_failure_description' => null]);
        }

        return $order->id;
    }
}
