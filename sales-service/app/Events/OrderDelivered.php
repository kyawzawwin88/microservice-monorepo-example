<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event dispatched when an order has been delivered.
 * This triggers inventory deduction in the Inventory service:
 * reserved stock is removed (goods left the warehouse).
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
