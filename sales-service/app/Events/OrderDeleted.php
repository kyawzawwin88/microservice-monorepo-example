<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event dispatched when an order is soft-deleted.
 * Sent to Inventory service to release reserved stock.
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
