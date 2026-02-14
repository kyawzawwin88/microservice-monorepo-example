<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Seed some initial inventory items for demo purposes.
     */
    public function run(): void
    {
        $items = [
            ['product_name' => 'Widget A', 'sku' => 'WGT-A001', 'quantity_available' => 100, 'quantity_reserved' => 0],
            ['product_name' => 'Widget B', 'sku' => 'WGT-B001', 'quantity_available' => 50, 'quantity_reserved' => 0],
            ['product_name' => 'Gadget X', 'sku' => 'GDG-X001', 'quantity_available' => 200, 'quantity_reserved' => 0],
            ['product_name' => 'Gadget Y', 'sku' => 'GDG-Y001', 'quantity_available' => 75, 'quantity_reserved' => 0],
            ['product_name' => 'Component Z', 'sku' => 'CMP-Z001', 'quantity_available' => 500, 'quantity_reserved' => 0],
        ];

        foreach ($items as $item) {
            InventoryItem::updateOrCreate(
                ['sku' => $item['sku']],
                $item
            );
        }
    }
}
