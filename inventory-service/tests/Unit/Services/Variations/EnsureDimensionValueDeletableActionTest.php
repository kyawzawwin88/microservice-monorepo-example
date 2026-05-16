<?php

namespace Tests\Unit\Services\Variations;

use App\Models\InventoryItem;
use App\Models\InventoryVariation;
use App\Models\StockBalance;
use App\Models\StorageLocation;
use App\Models\VariationDimension;
use App\Models\VariationDimensionValue;
use App\Services\Variations\EnsureDimensionValueDeletableAction;
use InvalidArgumentException;
use Tests\TestCase;

class EnsureDimensionValueDeletableActionTest extends TestCase
{
    public function test_rejects_delete_when_variation_has_stock(): void
    {
        $location = StorageLocation::create(['name' => 'Main', 'code' => 'M', 'is_active' => true]);
        $item = InventoryItem::create([
            'product_name' => 'Hat',
            'sku' => 'HAT',
            'has_variations' => true,
            'quantity_available' => 0,
            'quantity_reserved' => 0,
            'unit_price' => 5,
        ]);

        $dimension = VariationDimension::create([
            'inventory_item_id' => $item->id,
            'name' => 'Size',
            'sort_order' => 0,
        ]);

        $value = VariationDimensionValue::create([
            'variation_dimension_id' => $dimension->id,
            'value' => 'S',
        ]);

        $variation = InventoryVariation::create([
            'inventory_item_id' => $item->id,
            'sku' => 'HAT-S',
            'attribute_hash' => 'h1',
            'label' => 'S',
        ]);
        $variation->dimensionValues()->sync([$value->id]);

        StockBalance::create([
            'balanceable_type' => InventoryVariation::class,
            'balanceable_id' => $variation->id,
            'storage_location_id' => $location->id,
            'quantity_available' => 3,
            'quantity_reserved' => 0,
        ]);

        $this->expectException(InvalidArgumentException::class);
        (new EnsureDimensionValueDeletableAction)->execute($value);
    }

    public function test_allows_delete_when_no_stock(): void
    {
        $item = InventoryItem::create([
            'product_name' => 'Hat2',
            'sku' => 'HAT2',
            'has_variations' => true,
            'quantity_available' => 0,
            'quantity_reserved' => 0,
            'unit_price' => 5,
        ]);

        $dimension = VariationDimension::create([
            'inventory_item_id' => $item->id,
            'name' => 'Size',
            'sort_order' => 0,
        ]);

        $value = VariationDimensionValue::create([
            'variation_dimension_id' => $dimension->id,
            'value' => 'XL',
        ]);

        (new EnsureDimensionValueDeletableAction)->execute($value);
        $this->assertTrue(true);
    }
}
