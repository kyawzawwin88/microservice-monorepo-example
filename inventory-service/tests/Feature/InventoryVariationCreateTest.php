<?php

namespace Tests\Feature;

use App\Models\InventoryVariation;
use App\Models\StorageLocation;
use Tests\TestCase;

class InventoryVariationCreateTest extends TestCase
{
    public function test_create_item_with_variations_returns_six_combinations(): void
    {
        $location = StorageLocation::create(['name' => 'Main', 'code' => 'DEFAULT', 'is_active' => true]);

        $response = $this->postJson('/api/inventory', [
            'product_name' => 'T-Shirt',
            'sku' => 'TSHIRT-'.uniqid(),
            'unit_price' => 19.99,
            'has_variations' => true,
            'dimensions' => [
                ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                ['name' => 'Color', 'values' => ['Red', 'Blue']],
            ],
            'initial_location_id' => $location->id,
            'initial_quantity_per_variation' => 0,
        ]);

        $response->assertCreated();
        $itemId = $response->json('item.id');
        $this->assertCount(6, $response->json('variations'));
        $this->assertSame(6, InventoryVariation::where('inventory_item_id', $itemId)->count());
    }
}
