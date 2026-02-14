<?php

namespace App\Activities;

use Illuminate\Support\Facades\Cache;
use Workflow\Activity;

/**
 * Activity: Clear invoice-related cache entries.
 */
class ClearInvoiceCacheActivity extends Activity
{
    public function execute(string $correlationId, int $invoiceId): bool
    {
        Cache::forget("invoice:{$invoiceId}");
        Cache::forget("invoice:correlation:{$correlationId}");
        Cache::forget('invoices:list');
        Cache::forget('invoices:recent');

        return true;
    }
}
