<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event dispatched when an order has been paid.
 * This triggers inventory reservation in the Inventory service.
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
