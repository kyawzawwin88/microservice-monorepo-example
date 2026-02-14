<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event dispatched when payment is completed.
 * Sales service listens to this to finalize the order.
 */
class PaymentCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly string $correlationId,
        public readonly int $paymentId,
        public readonly float $amount,
        public readonly string $paymentMethod,
    ) {}
}
