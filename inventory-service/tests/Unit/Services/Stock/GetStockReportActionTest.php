<?php

namespace Tests\Unit\Services\Stock;

use App\Models\InventoryItem;
use App\Models\InventoryVariation;
use App\Models\StockBalance;
use App\Models\StorageLocation;
use App\Services\Stock\GetStockReportAction;
use Tests\TestCase;

class GetStockReportActionTest extends TestCase
{
    public function test_aggregation_equals_sum_of_variation_balances(): void
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
            'sku' => 'SHIRT-S',
            'attribute_hash' => 'h1',
            'label' => 'S',
        ]);
        $v2 = InventoryVariation::create([
            'inventory_item_id' => $item->id,
            'sku' => 'SHIRT-M',
            'attribute_hash' => 'h2',
            'label' => 'M',
        ]);

        StockBalance::create([
            'balanceable_type' => InventoryVariation::class,
            'balanceable_id' => $v1->id,
            'storage_location_id' => $location->id,
            'quantity_available' => 10,
            'quantity_reserved' => 2,
        ]);
        StockBalance::create([
            'balanceable_type' => InventoryVariation::class,
            'balanceable_id' => $v2->id,
            'storage_location_id' => $location->id,
            'quantity_available' => 20,
            'quantity_reserved' => 1,
        ]);

        $report = (new GetStockReportAction)->execute($item->fresh());

        $this->assertTrue($report['has_variations']);
        $this->assertSame(30, $report['aggregated']['quantity_available']);
        $this->assertSame(3, $report['aggregated']['quantity_reserved']);
        $this->assertCount(2, $report['variations']);
    }

    public function test_low_stock_variation_visible_in_report(): void
    {
        $location = StorageLocation::create(['name' => 'Main', 'code' => 'M2', 'is_active' => true]);
        $item = InventoryItem::create([
            'product_name' => 'Hat',
            'sku' => 'HAT',
            'has_variations' => true,
            'quantity_available' => 0,
            'quantity_reserved' => 0,
            'unit_price' => 5,
        ]);

        $low = InventoryVariation::create([
            'inventory_item_id' => $item->id,
            'sku' => 'HAT-S',
            'attribute_hash' => 'h1',
            'label' => 'S',
        ]);

        StockBalance::create([
            'balanceable_type' => InventoryVariation::class,
            'balanceable_id' => $low->id,
            'storage_location_id' => $location->id,
            'quantity_available' => 2,
            'quantity_reserved' => 0,
        ]);

        $report = (new GetStockReportAction)->execute($item->fresh(), $location->id);
        $row = $report['variations'][0]['by_location'][0];

        $this->assertSame(2, $row['quantity_available']);
    }
}
