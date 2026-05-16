<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryVariation;
use App\Models\StockBalance;
use App\Models\StorageLocation;
use Tests\TestCase;

class StockTransferTest extends TestCase
{
    public function test_transfer_updates_source_and_destination_atomically(): void
    {
        $source = StorageLocation::create(['name' => 'A', 'code' => 'A', 'is_active' => true]);
        $dest = StorageLocation::create(['name' => 'B', 'code' => 'B', 'is_active' => true]);

        $item = InventoryItem::create([
            'product_name' => 'Transfer Shirt',
            'sku' => 'TS',
            'has_variations' => true,
            'quantity_available' => 0,
            'quantity_reserved' => 0,
            'unit_price' => 10,
        ]);

        $variation = InventoryVariation::create([
            'inventory_item_id' => $item->id,
            'sku' => 'TS-M',
            'attribute_hash' => 'h1',
            'label' => 'M',
        ]);

        StockBalance::create([
            'balanceable_type' => InventoryVariation::class,
            'balanceable_id' => $variation->id,
            'storage_location_id' => $source->id,
            'quantity_available' => 40,
            'quantity_reserved' => 0,
        ]);
        StockBalance::create([
            'balanceable_type' => InventoryVariation::class,
            'balanceable_id' => $variation->id,
            'storage_location_id' => $dest->id,
            'quantity_available' => 5,
            'quantity_reserved' => 0,
        ]);

        $response = $this->postJson('/api/inventory/transfers', [
            'inventory_variation_id' => $variation->id,
            'source_location_id' => $source->id,
            'destination_location_id' => $dest->id,
            'quantity' => 20,
        ]);

        $response->assertCreated();
        $this->assertSame(20, StockBalance::where('storage_location_id', $source->id)->value('quantity_available'));
        $this->assertSame(25, StockBalance::where('storage_location_id', $dest->id)->value('quantity_available'));
    }
}
