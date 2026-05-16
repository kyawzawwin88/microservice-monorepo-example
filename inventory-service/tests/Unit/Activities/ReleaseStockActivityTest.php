<?php

namespace Tests\Unit\Activities;

use App\Activities\ReleaseStockActivity;
use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use App\Models\StockBalance;
use App\Models\StorageLocation;
use App\States\RequestedState;
use Tests\TestCase;

class ReleaseStockActivityTest extends TestCase
{
    public function test_release_legacy_item_restores_balance_available(): void
    {
        $location = StorageLocation::create(['name' => 'Main', 'code' => 'DEFAULT', 'is_active' => true]);
        $item = InventoryItem::create([
            'product_name' => 'Gadget',
            'sku' => 'G1',
            'has_variations' => false,
            'quantity_available' => 2,
            'quantity_reserved' => 8,
            'unit_price' => 1,
        ]);

        StockBalance::create([
            'balanceable_type' => InventoryItem::class,
            'balanceable_id' => $item->id,
            'storage_location_id' => $location->id,
            'quantity_available' => 2,
            'quantity_reserved' => 8,
        ]);

        InventoryReservation::create([
            'correlation_id' => 'corr-release-1',
            'order_id' => 2,
            'product_name' => 'Gadget',
            'quantity' => 4,
            'reserved_quantity' => 4,
            'storage_location_id' => $location->id,
            'state' => RequestedState::class,
        ]);

        $activity = (new \ReflectionClass(ReleaseStockActivity::class))->newInstanceWithoutConstructor();
        $activity->execute('corr-release-1', [
            ['product_name' => 'Gadget', 'quantity' => 4],
        ]);

        $balance = StockBalance::where('balanceable_id', $item->id)->first();
        $item->refresh();

        $this->assertSame(6, $balance->quantity_available);
        $this->assertSame(4, $balance->quantity_reserved);
        $this->assertSame(6, $item->quantity_available);
        $this->assertSame(4, $item->quantity_reserved);
    }
}
