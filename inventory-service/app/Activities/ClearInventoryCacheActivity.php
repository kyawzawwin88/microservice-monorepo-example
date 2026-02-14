<?php

namespace App\Activities;

use Illuminate\Support\Facades\Cache;
use Workflow\Activity;

/**
 * Activity: Clear inventory-related cache entries.
 */
class ClearInventoryCacheActivity extends Activity
{
    public function execute(string $correlationId): bool
    {
        Cache::forget("inventory:correlation:{$correlationId}");
        Cache::forget('inventory:items:list');
        Cache::forget('inventory:reservations:list');

        return true;
    }
}
