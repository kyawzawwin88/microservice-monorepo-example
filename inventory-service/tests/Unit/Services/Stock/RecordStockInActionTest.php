<?php

namespace Tests\Unit\Services\Stock;

use App\Models\InventoryItem;
use App\Models\InventoryVariation;
use App\Models\StockBalance;
use App\Models\StorageLocation;
use App\Services\Stock\RecordStockInAction;
use App\Services\Stock\ResolveStockBalanceAction;
use App\Services\Stock\SyncLegacyItemStockFromBalanceAction;
use Tests\TestCase;

class RecordStockInActionTest extends TestCase
{
    public function test_stock_in_only_affects_target_variation(): void
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

        $v1 = InventoryVariation::create([
            'inventory_item_id' => $item->id,
            'sku' => 'SHIRT-M',
            'attribute_hash' => 'h1',
            'label' => 'M',
        ]);
        $v2 = InventoryVariation::create([
            'inventory_item_id' => $item->id,
            'sku' => 'SHIRT-L',
            'attribute_hash' => 'h2',
            'label' => 'L',
        ]);

        StockBalance::create([
            'balanceable_type' => InventoryVariation::class,
            'balanceable_id' => $v1->id,
            'storage_location_id' => $location->id,
            'quantity_available' => 20,
            'quantity_reserved' => 0,
        ]);
        StockBalance::create([
            'balanceable_type' => InventoryVariation::class,
            'balanceable_id' => $v2->id,
            'storage_location_id' => $location->id,
            'quantity_available' => 10,
            'quantity_reserved' => 0,
        ]);

        $action = new RecordStockInAction(
            new ResolveStockBalanceAction(),
            new SyncLegacyItemStockFromBalanceAction()
        );

        $result = $action->execute($v1->id, null, $location->id, 30);

        $this->assertSame(50, $result['balance']->quantity_available);
        $this->assertSame(10, StockBalance::where('balanceable_id', $v2->id)->first()->quantity_available);
    }
}
