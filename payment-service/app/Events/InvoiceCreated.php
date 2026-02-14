<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event received from Invoice service when invoice is generated.
 * Payment service listens to this to initiate payment processing.
 */
class InvoiceCreated
{
    use Dispatchable;

    public function __construct(
        public readonly string $correlationId,
        public readonly int $invoiceId,
        public readonly string $invoiceNumber,
        public readonly int $orderId,
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly float $amount,
        public readonly array $lineItems,
        public readonly bool $simulateFailure = false,
    ) {}
}
