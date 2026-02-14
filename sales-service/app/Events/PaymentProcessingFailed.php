<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event received from Payment service when payment processing fails.
 * Sales service listens to this to mark the order state as 'failed'.
 */
class PaymentProcessingFailed
{
    use Dispatchable;

    public function __construct(
        public readonly string $correlationId,
        public readonly string $reason,
    ) {}
}
