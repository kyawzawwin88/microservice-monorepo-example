<?php

namespace Tests\Unit\Services\Locations;

use App\Models\StorageLocation;
use App\Services\Locations\UpdateStorageLocationAction;
use Tests\TestCase;

class UpdateStorageLocationActionTest extends TestCase
{
    public function test_updates_name_and_code(): void
    {
        $location = StorageLocation::create(['name' => 'Old', 'code' => 'OLD', 'is_active' => true]);

        $updated = (new UpdateStorageLocationAction)->execute($location, [
            'name' => 'New Warehouse',
            'code' => 'NEW',
        ]);

        $this->assertSame('New Warehouse', $updated->name);
        $this->assertSame('NEW', $updated->code);
        $this->assertDatabaseHas('storage_locations', ['id' => $location->id, 'code' => 'NEW']);
    }

    public function test_updates_address_fields(): void
    {
        $location = StorageLocation::create(['name' => 'Site', 'code' => 'SITE', 'is_active' => true]);

        $updated = (new UpdateStorageLocationAction)->execute($location, [
            'name' => 'Site',
            'code' => 'SITE',
            'address_street' => '1 Industrial Park',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ]);

        $this->assertSame('DE', $updated->country_code);
        $this->assertSame('10115', $updated->postal_code);
    }
}
