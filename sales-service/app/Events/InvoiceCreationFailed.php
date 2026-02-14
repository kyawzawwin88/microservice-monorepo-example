<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event received from Invoice service when invoice creation fails.
 * Sales service listens to this to mark the order state as 'failed'.
 */
class InvoiceCreationFailed
{
    use Dispatchable;

    public function __construct(
        public readonly string $correlationId,
        public readonly string $reason,
    ) {}
}
