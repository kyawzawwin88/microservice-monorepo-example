<?php

namespace App\Services\Stock;

use App\Models\InventoryItem;
use App\Models\StockBalance;

class SyncLegacyItemStockFromBalanceAction
{
    public function execute(InventoryItem $item, StockBalance $balance): void
    {
        if ($item->has_variations) {
            return;
        }

        $item->update([
            'quantity_available' => $balance->quantity_available,
            'quantity_reserved' => $balance->quantity_reserved,
        ]);
    }
}
