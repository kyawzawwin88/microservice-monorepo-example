<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryVariation;
use App\Models\StockBalance;
use App\Models\StorageLocation;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    public function test_stock_in_isolates_variation_balances(): void
    {
        $location = StorageLocation::create(['name' => 'Main', 'code' => 'DEFAULT', 'is_active' => true]);
        $item = InventoryItem::create([
            'product_name' => 'Shirt',
            'sku' => 'SHIRT-API',
            'has_variations' => true,
            'quantity_available' => 0,
            'quantity_reserved' => 0,
            'unit_price' => 10,
        ]);

        $v1 = InventoryVariation::create([
            'inventory_item_id' => $item->id,
            'sku' => 'SHIRT-API-M',
            'attribute_hash' => 'h1',
            'label' => 'M',
        ]);
        $v2 = InventoryVariation::create([
            'inventory_item_id' => $item->id,
            'sku' => 'SHIRT-API-L',
            'attribute_hash' => 'h2',
            'label' => 'L',
        ]);

        StockBalance::create([
            'balanceable_type' => InventoryVariation::class,
            'balanceable_id' => $v1->id,
            'storage_location_id' => $location->id,
            'quantity_available' => 10,
            'quantity_reserved' => 0,
        ]);
        StockBalance::create([
            'balanceable_type' => InventoryVariation::class,
            'balanceable_id' => $v2->id,
            'storage_location_id' => $location->id,
            'quantity_available' => 5,
            'quantity_reserved' => 0,
        ]);

        $response = $this->postJson('/api/inventory/stock-in', [
            'inventory_variation_id' => $v1->id,
            'storage_location_id' => $location->id,
            'quantity' => 15,
        ]);

        $response->assertCreated();
        $this->assertSame(25, StockBalance::where('balanceable_id', $v1->id)->value('quantity_available'));
        $this->assertSame(5, StockBalance::where('balanceable_id', $v2->id)->value('quantity_available'));
    }
}
