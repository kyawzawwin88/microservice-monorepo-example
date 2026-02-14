<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event received from Sales service when an order has been delivered.
 * Inventory service listens to this to DEDUCT reserved stock
 * (goods have left the warehouse).
 */
class OrderDelivered
{
    use Dispatchable;

    public function __construct(
        public readonly string $correlationId,
        public readonly int $orderId,
        public readonly string $customerName,
        public readonly array $items,
    ) {}
}
