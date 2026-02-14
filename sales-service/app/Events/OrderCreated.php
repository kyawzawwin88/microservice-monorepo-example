<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event dispatched when a new order is created.
 *
 * This is a simple data object. Cross-service delivery is handled
 * by EventBusMessage jobs pushed to the shared eventbus database.
 * Local listeners (e.g. LogIncomingEvent) fire synchronously.
 */
class OrderCreated
{
    use Dispatchable;

    public function __construct(
        public readonly string $correlationId,
        public readonly int $orderId,
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly float $totalAmount,
        public readonly array $items,
        public readonly bool $simulateFailure = false,
    ) {}
}
