<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event received from Sales service when an order is deleted.
 * Inventory service listens to this to release reserved stock.
 */
class OrderDeleted
{
    use Dispatchable;

    public function __construct(
        public readonly string $correlationId,
        public readonly int $orderId,
        public readonly string $customerName,
        public readonly array $items,
    ) {}
}
