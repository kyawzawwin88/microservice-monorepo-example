<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event dispatched when invoice creation fails.
 * Published to Sales service via event bus so the order
 * can be marked as 'failed'.
 */
class InvoiceCreationFailed
{
    use Dispatchable;

    public function __construct(
        public readonly string $correlationId,
        public readonly string $reason,
    ) {}
}
