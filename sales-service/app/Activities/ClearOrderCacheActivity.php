<?php

namespace App\Activities;

use Illuminate\Support\Facades\Cache;
use Workflow\Activity;

/**
 * Activity: Clear order-related cache entries.
 * After order state changes, clear relevant cache to ensure
 * fresh data is served on subsequent requests.
 */
class ClearOrderCacheActivity extends Activity
{
    public function execute(string $correlationId, int $orderId): bool
    {
        // Clear order-specific cache
        Cache::forget("order:{$orderId}");
        Cache::forget("order:correlation:{$correlationId}");

        // Clear order listing cache
        Cache::forget('orders:list');
        Cache::forget('orders:recent');

        return true;
    }
}
