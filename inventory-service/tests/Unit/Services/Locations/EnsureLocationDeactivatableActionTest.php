<?php

namespace Tests\Unit\Services\Locations;

use App\Models\InventoryItem;
use App\Models\InventoryVariation;
use App\Models\StockBalance;
use App\Models\StorageLocation;
use App\Services\Locations\EnsureLocationDeactivatableAction;
use InvalidArgumentException;
use Tests\TestCase;

class EnsureLocationDeactivatableActionTest extends TestCase
{
    public function test_allows_deactivation_when_no_stock(): void
    {
        $location = StorageLocation::create(['name' => 'Empty', 'code' => 'EMPTY', 'is_active' => true]);

        (new EnsureLocationDeactivatableAction)->execute($location);

        $this->assertTrue($location->is_active);
    }

    public function test_blocks_when_available_stock_exists(): void
    {
        $location = StorageLocation::create(['name' => 'Stocked', 'code' => 'STK', 'is_active' => true]);
        $item = InventoryItem::create([
            'product_name' => 'P',
            'sku' => 'P',
            'has_variations' => false,
            'quantity_available' => 0,
            'quantity_reserved' => 0,
            'unit_price' => 1,
        ]);

        StockBalance::create([
            'balanceable_type' => InventoryItem::class,
            'balanceable_id' => $item->id,
            'storage_location_id' => $location->id,
            'quantity_available' => 5,
            'quantity_reserved' => 0,
        ]);

        $this->expectException(InvalidArgumentException::class);
        (new EnsureLocationDeactivatableAction)->execute($location);
    }

    public function test_blocks_when_reserved_stock_exists(): void
    {
        $location = StorageLocation::create(['name' => 'Reserved', 'code' => 'RSV', 'is_active' => true]);
        $variation = InventoryVariation::create([
            'inventory_item_id' => InventoryItem::create([
                'product_name' => 'V',
                'sku' => 'V',
                'has_variations' => true,
                'quantity_available' => 0,
                'quantity_reserved' => 0,
                'unit_price' => 1,
            ])->id,
            'sku' => 'V-1',
            'attribute_hash' => 'h',
            'label' => '1',
        ]);

        StockBalance::create([
            'balanceable_type' => InventoryVariation::class,
            'balanceable_id' => $variation->id,
            'storage_location_id' => $location->id,
            'quantity_available' => 0,
            'quantity_reserved' => 2,
        ]);

        $this->expectException(InvalidArgumentException::class);
        (new EnsureLocationDeactivatableAction)->execute($location);
    }

    public function test_blocks_already_inactive_location(): void
    {
        $location = StorageLocation::create(['name' => 'Off', 'code' => 'OFF', 'is_active' => false]);

        $this->expectException(InvalidArgumentException::class);
        (new EnsureLocationDeactivatableAction)->execute($location);
    }
}
