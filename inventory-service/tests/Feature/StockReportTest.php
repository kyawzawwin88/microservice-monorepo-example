<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryVariation;
use App\Models\StockBalance;
use App\Models\StorageLocation;
use Tests\TestCase;

class StockReportTest extends TestCase
{
    public function test_stock_report_aggregated_totals_match_variations(): void
    {
        $location = StorageLocation::create(['name' => 'Main', 'code' => 'DEFAULT', 'is_active' => true]);
        $item = InventoryItem::create([
            'product_name' => 'Report Shirt',
            'sku' => 'RPT-'.uniqid(),
            'has_variations' => true,
            'quantity_available' => 0,
            'quantity_reserved' => 0,
            'unit_price' => 10,
        ]);

        $v1 = InventoryVariation::create([
            'inventory_item_id' => $item->id,
            'sku' => 'RPT-S',
            'attribute_hash' => 'h1',
            'label' => 'S',
        ]);

        StockBalance::create([
            'balanceable_type' => InventoryVariation::class,
            'balanceable_id' => $v1->id,
            'storage_location_id' => $location->id,
            'quantity_available' => 12,
            'quantity_reserved' => 3,
        ]);

        $response = $this->getJson("/api/inventory/{$item->id}/stock-report");

        $response->assertOk();
        $response->assertJsonPath('aggregated.quantity_available', 12);
        $response->assertJsonPath('aggregated.quantity_reserved', 3);
    }
}
