<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event received from Sales service when an order has been paid.
 * Inventory service listens to this to RESERVE stock for the order items.
 */
class OrderPaid
{
    use Dispatchable;

    public function __construct(
        public readonly string $correlationId,
        public readonly int $orderId,
        public readonly string $customerName,
        public readonly float $totalAmount,
        public readonly array $items,
    ) {}
}
