<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event dispatched when payment processing fails.
 * Published to Sales service via event bus so the order
 * can be marked as 'failed'.
 */
class PaymentProcessingFailed
{
    use Dispatchable;

    public function __construct(
        public readonly string $correlationId,
        public readonly string $reason,
    ) {}
}
