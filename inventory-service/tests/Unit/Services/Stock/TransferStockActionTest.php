<?php

namespace Tests\Unit\Services\Stock;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryItem;
use App\Models\InventoryVariation;
use App\Models\StockBalance;
use App\Models\StorageLocation;
use App\Services\Stock\ResolveStockBalanceAction;
use App\Services\Stock\SyncLegacyItemStockFromBalanceAction;
use App\Services\Stock\TransferStockAction;
use InvalidArgumentException;
use Tests\TestCase;

class TransferStockActionTest extends TestCase
{
    private TransferStockAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $resolve = new ResolveStockBalanceAction();
        $this->action = new TransferStockAction($resolve, new SyncLegacyItemStockFromBalanceAction());
    }

    public function test_transfers_stock_atomically_between_locations(): void
    {
        $locA = StorageLocation::create(['name' => 'A', 'code' => 'A', 'is_active' => true]);
        $locB = StorageLocation::create(['name' => 'B', 'code' => 'B', 'is_active' => true]);

        $item = InventoryItem::create([
            'product_name' => 'Legacy',
            'sku' => 'LEG-1',
            'has_variations' => false,
            'quantity_available' => 40,
            'quantity_reserved' => 0,
            'unit_price' => 1,
        ]);

        StockBalance::create([
            'balanceable_type' => InventoryItem::class,
            'balanceable_id' => $item->id,
            'storage_location_id' => $locA->id,
            'quantity_available' => 40,
            'quantity_reserved' => 0,
        ]);
        StockBalance::create([
            'balanceable_type' => InventoryItem::class,
            'balanceable_id' => $item->id,
            'storage_location_id' => $locB->id,
            'quantity_available' => 5,
            'quantity_reserved' => 0,
        ]);

        $result = $this->action->execute(null, $item->id, $locA->id, $locB->id, 20);

        $this->assertSame(20, $result['source_balance']->quantity_available);
        $this->assertSame(25, $result['destination_balance']->quantity_available);
    }

    public function test_rejects_same_location_transfer(): void
    {
        $loc = StorageLocation::create(['name' => 'A', 'code' => 'SAME', 'is_active' => true]);
        $item = InventoryItem::create([
            'product_name' => 'X',
            'sku' => 'X-1',
            'has_variations' => false,
            'quantity_available' => 10,
            'quantity_reserved' => 0,
            'unit_price' => 1,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->action->execute(null, $item->id, $loc->id, $loc->id, 5);
    }

    public function test_rejects_insufficient_transferable_stock(): void
    {
        $locA = StorageLocation::create(['name' => 'A', 'code' => 'A2', 'is_active' => true]);
        $locB = StorageLocation::create(['name' => 'B', 'code' => 'B2', 'is_active' => true]);

        $variation = InventoryVariation::create([
            'inventory_item_id' => InventoryItem::create([
                'product_name' => 'Var',
                'sku' => 'VAR',
                'has_variations' => true,
                'quantity_available' => 0,
                'quantity_reserved' => 0,
                'unit_price' => 1,
            ])->id,
            'sku' => 'VAR-M-BLUE',
            'attribute_hash' => 'hash1',
            'label' => 'M / Blue',
        ]);

        StockBalance::create([
            'balanceable_type' => InventoryVariation::class,
            'balanceable_id' => $variation->id,
            'storage_location_id' => $locA->id,
            'quantity_available' => 5,
            'quantity_reserved' => 3,
        ]);

        $this->expectException(InsufficientStockException::class);
        $this->action->execute($variation->id, null, $locA->id, $locB->id, 5);
    }
}
