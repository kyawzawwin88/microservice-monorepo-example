<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event dispatched when inventory has been reserved for an order.
 */
class InventoryReserved
{
    use Dispatchable;

    public function __construct(
        public readonly string $correlationId,
        public readonly int $orderId,
        public readonly array $reservedItems,
    ) {}
}
