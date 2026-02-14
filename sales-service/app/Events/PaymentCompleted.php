<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event received from Payment service when payment is completed.
 * Sales service listens to this to mark the order as completed.
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
