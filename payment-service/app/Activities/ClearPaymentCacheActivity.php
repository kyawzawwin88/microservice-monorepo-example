<?php

namespace App\Activities;

use Illuminate\Support\Facades\Cache;
use Workflow\Activity;

/**
 * Activity: Clear payment-related cache entries.
 */
class ClearPaymentCacheActivity extends Activity
{
    public function execute(string $correlationId, int $paymentId): bool
    {
        Cache::forget("payment:{$paymentId}");
        Cache::forget("payment:correlation:{$correlationId}");
        Cache::forget('payments:list');
        Cache::forget('payments:recent');

        return true;
    }
}
