<?php

namespace Tests\Unit\Activities;

use App\Activities\CheckStockActivity;
use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use App\Models\InventoryVariation;
use App\Models\StockBalance;
use App\Models\StorageLocation;
use Tests\TestCase;

class CheckStockActivityVariationTest extends TestCase
{
    public function test_reserves_stock_for_specific_variation_at_location(): void
    {
        $location = StorageLocation::create(['name' => 'Main', 'code' => 'DEFAULT', 'is_active' => true]);
        $item = InventoryItem::create([
            'product_name' => 'Shirt',
            'sku' => 'SHIRT-CHK',
            'has_variations' => true,
            'quantity_available' => 0,
            'quantity_reserved' => 0,
            'unit_price' => 10,
        ]);

        $variation = InventoryVariation::create([
            'inventory_item_id' => $item->id,
            'sku' => 'SHIRT-M',
            'attribute_hash' => 'hash-chk',
            'label' => 'M',
        ]);

        StockBalance::create([
            'balanceable_type' => InventoryVariation::class,
            'balanceable_id' => $variation->id,
            'storage_location_id' => $location->id,
            'quantity_available' => 25,
            'quantity_reserved' => 0,
        ]);

        $activity = (new \ReflectionClass(CheckStockActivity::class))->newInstanceWithoutConstructor();
        $result = $activity->execute('corr-var-1', [
            'order_id' => 99,
            'items' => [
                [
                    'product_name' => 'Shirt',
                    'quantity' => 5,
                    'variation_id' => $variation->id,
                    'location_id' => $location->id,
                ],
            ],
        ]);

        $balance = StockBalance::where('balanceable_id', $variation->id)->first();
        $reservation = InventoryReservation::where('correlation_id', 'corr-var-1')->first();

        $this->assertFalse($result['already_exists']);
        $this->assertSame(20, $balance->quantity_available);
        $this->assertSame(5, $balance->quantity_reserved);
        $this->assertSame($variation->id, $reservation->inventory_variation_id);
        $this->assertSame($location->id, $reservation->storage_location_id);
    }
}
