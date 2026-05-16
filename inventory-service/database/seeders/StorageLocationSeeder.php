<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\StockBalance;
use App\Models\StorageLocation;
use Illuminate\Database\Seeder;

class StorageLocationSeeder extends Seeder
{
    public function run(): void
    {
        $default = StorageLocation::updateOrCreate(
            ['code' => 'DEFAULT'],
            ['name' => 'Main Warehouse', 'is_active' => true]
        );

        StorageLocation::updateOrCreate(
            ['code' => 'WH-B'],
            ['name' => 'Warehouse B', 'is_active' => true]
        );

        InventoryItem::where('has_variations', false)->each(function (InventoryItem $item) use ($default) {
            StockBalance::updateOrCreate(
                [
                    'balanceable_type' => InventoryItem::class,
                    'balanceable_id' => $item->id,
                    'storage_location_id' => $default->id,
                ],
                [
                    'quantity_available' => $item->quantity_available,
                    'quantity_reserved' => $item->quantity_reserved,
                ]
            );
        });
    }
}
