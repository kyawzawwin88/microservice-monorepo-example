<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event received from Sales service when an order is created.
 * Inventory service listens to this to reserve stock.
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
