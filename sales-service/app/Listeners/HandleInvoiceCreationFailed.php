<?php

namespace App\Listeners;

use App\Events\InvoiceCreationFailed;
use App\Models\Order;
use App\States\FailedState;

/**
 * Listener: When invoice creation fails in the Invoice service,
 * mark the originating order's state as 'failed' with the reason.
 *
 * This demonstrates downstream failure propagation in an
 * event-driven microservice architecture.
 */
class HandleInvoiceCreationFailed
{
    public function handle(InvoiceCreationFailed $event): void
    {
        $order = Order::where('correlation_id', $event->correlationId)->first();

        if ($order) {
            $order->markAsFailed("Invoice service failed: {$event->reason}");
            if ($order->state::$name !== 'failed') {
                $order->state->transitionTo(FailedState::class);
            }
        }
    }
}
