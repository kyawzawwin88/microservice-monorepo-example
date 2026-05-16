<?php

namespace Tests\Unit\Activities;

use App\Activities\DeductStockActivity;
use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use App\Models\StockBalance;
use App\Models\StorageLocation;
use App\States\RequestedState;
use Tests\TestCase;

class DeductStockActivityTest extends TestCase
{
    public function test_deduct_legacy_item_reduces_balance_reserved_only(): void
    {
        $location = StorageLocation::create(['name' => 'Main', 'code' => 'DEFAULT', 'is_active' => true]);
        $item = InventoryItem::create([
            'product_name' => 'Widget',
            'sku' => 'W1',
            'has_variations' => false,
            'quantity_available' => 5,
            'quantity_reserved' => 10,
            'unit_price' => 1,
        ]);

        StockBalance::create([
            'balanceable_type' => InventoryItem::class,
            'balanceable_id' => $item->id,
            'storage_location_id' => $location->id,
            'quantity_available' => 5,
            'quantity_reserved' => 10,
        ]);

        InventoryReservation::create([
            'correlation_id' => 'corr-deduct-1',
            'order_id' => 1,
            'product_name' => 'Widget',
            'quantity' => 3,
            'reserved_quantity' => 3,
            'storage_location_id' => $location->id,
            'state' => RequestedState::class,
        ]);

        $activity = (new \ReflectionClass(DeductStockActivity::class))->newInstanceWithoutConstructor();
        $result = $activity->execute('corr-deduct-1', [
            ['product_name' => 'Widget', 'quantity' => 3],
        ]);

        $balance = StockBalance::where('balanceable_id', $item->id)->first();
        $item->refresh();

        $this->assertCount(1, $result['deducted_items']);
        $this->assertSame(7, $balance->quantity_reserved);
        $this->assertSame(5, $balance->quantity_available);
        $this->assertSame(5, $item->quantity_available);
        $this->assertSame(7, $item->quantity_reserved);
    }
}
