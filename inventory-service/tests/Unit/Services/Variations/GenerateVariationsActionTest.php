<?php

namespace Tests\Unit\Services\Variations;

use App\Models\InventoryItem;
use App\Models\InventoryVariation;
use App\Models\StorageLocation;
use App\Services\Variations\BuildVariationSkuAction;
use App\Services\Variations\GenerateVariationsAction;
use Tests\TestCase;

class GenerateVariationsActionTest extends TestCase
{
    public function test_generates_cartesian_product_of_dimensions(): void
    {
        $location = StorageLocation::create([
            'name' => 'Main',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'product_name' => 'T-Shirt',
            'sku' => 'TSHIRT',
            'has_variations' => true,
            'quantity_available' => 0,
            'quantity_reserved' => 0,
            'unit_price' => 10,
        ]);

        $action = new GenerateVariationsAction(new BuildVariationSkuAction());
        $variations = $action->execute($item, [
            ['name' => 'Size', 'values' => ['S', 'M', 'L']],
            ['name' => 'Color', 'values' => ['Red', 'Blue']],
        ], $location->id);

        $this->assertCount(6, $variations);
        $this->assertSame(6, InventoryVariation::where('inventory_item_id', $item->id)->count());
    }

    public function test_skips_duplicate_attribute_hash(): void
    {
        $location = StorageLocation::create([
            'name' => 'Main',
            'code' => 'MAIN2',
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'product_name' => 'Hat',
            'sku' => 'HAT',
            'has_variations' => true,
            'quantity_available' => 0,
            'quantity_reserved' => 0,
            'unit_price' => 5,
        ]);

        $action = new GenerateVariationsAction(new BuildVariationSkuAction());
        $dims = [['name' => 'Size', 'values' => ['S']]];

        $first = $action->execute($item, $dims, $location->id);
        $second = $action->execute($item, $dims, $location->id);

        $this->assertCount(1, $first);
        $this->assertCount(0, $second);
    }
}
