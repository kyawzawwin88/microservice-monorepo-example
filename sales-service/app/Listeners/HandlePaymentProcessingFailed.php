<?php

namespace App\Listeners;

use App\Events\PaymentProcessingFailed;
use App\Models\Order;
use App\States\FailedState;

/**
 * Listener: When payment processing fails in the Payment service,
 * mark the originating order's state as 'failed' with the reason.
 *
 * This demonstrates downstream failure propagation in an
 * event-driven microservice architecture.
 */
class HandlePaymentProcessingFailed
{
    public function handle(PaymentProcessingFailed $event): void
    {
        $order = Order::where('correlation_id', $event->correlationId)->first();

        if ($order) {
            $order->markAsFailed("Payment service failed: {$event->reason}");
            if ($order->state::$name !== 'failed') {
                $order->state->transitionTo(FailedState::class);
            }
        }
    }
}
