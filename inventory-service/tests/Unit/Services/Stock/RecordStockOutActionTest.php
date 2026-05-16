<?php

namespace Tests\Unit\Services\Stock;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryItem;
use App\Models\InventoryVariation;
use App\Models\StockBalance;
use App\Models\StorageLocation;
use App\Services\Stock\RecordStockOutAction;
use App\Services\Stock\ResolveStockBalanceAction;
use App\Services\Stock\SyncLegacyItemStockFromBalanceAction;
use Tests\TestCase;

class RecordStockOutActionTest extends TestCase
{
    public function test_stock_out_rejects_insufficient_stock(): void
    {
        $location = StorageLocation::create(['name' => 'Main', 'code' => 'M', 'is_active' => true]);
        $item = InventoryItem::create([
            'product_name' => 'Shirt',
            'sku' => 'SHIRT',
            'has_variations' => true,
            'quantity_available' => 0,
            'quantity_reserved' => 0,
            'unit_price' => 10,
        ]);

        $variation = InventoryVariation::create([
            'inventory_item_id' => $item->id,
            'sku' => 'SHIRT-M',
            'attribute_hash' => 'h1',
            'label' => 'M',
        ]);

        StockBalance::create([
            'balanceable_type' => InventoryVariation::class,
            'balanceable_id' => $variation->id,
            'storage_location_id' => $location->id,
            'quantity_available' => 5,
            'quantity_reserved' => 0,
        ]);

        $action = new RecordStockOutAction(
            new ResolveStockBalanceAction(),
            new SyncLegacyItemStockFromBalanceAction()
        );

        $this->expectException(InsufficientStockException::class);
        $action->execute($variation->id, null, $location->id, 10);
    }
}
