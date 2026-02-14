<?php

namespace App\Listeners;

use App\Events\OrderCreated;

/**
 * Listener: When order is created, log the event.
 *
 * NOTE: Stock reservation no longer happens at order creation.
 * It happens when the order is PAID (see HandleOrderPaid).
 * This listener is kept for backward compatibility / logging.
 */
class HandleOrderCreated
{
    public function handle(OrderCreated $event): void
    {
        // No stock reservation at order creation time.
        // Reservation is triggered by OrderPaid event after payment is confirmed.
        // Logging is handled by LogIncomingEvent listener.
    }
}
