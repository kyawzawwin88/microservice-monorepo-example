<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\StockBalance;
use App\Models\StorageLocation;
use Tests\TestCase;

class StorageLocationAdminTest extends TestCase
{
    public function test_index_returns_active_only_by_default(): void
    {
        StorageLocation::create(['name' => 'Active', 'code' => 'ACT', 'is_active' => true]);
        StorageLocation::create(['name' => 'Closed', 'code' => 'CLS', 'is_active' => false]);

        $response = $this->getJson('/api/inventory/locations');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.code', 'ACT');
    }

    public function test_index_all_includes_inactive(): void
    {
        StorageLocation::create(['name' => 'Active', 'code' => 'ACT2', 'is_active' => true]);
        StorageLocation::create(['name' => 'Closed', 'code' => 'CLS2', 'is_active' => false]);

        $response = $this->getJson('/api/inventory/locations?all=1');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_store_creates_active_location(): void
    {
        $response = $this->postJson('/api/inventory/locations', [
            'name' => 'Store Backroom',
            'code' => 'STORE-01',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('is_active', true);
        $this->assertDatabaseHas('storage_locations', ['code' => 'STORE-01']);
    }

    public function test_store_rejects_duplicate_code(): void
    {
        StorageLocation::create(['name' => 'A', 'code' => 'DUP', 'is_active' => true]);

        $response = $this->postJson('/api/inventory/locations', [
            'name' => 'B',
            'code' => 'DUP',
        ]);

        $response->assertStatus(422);
    }

    public function test_update_changes_name(): void
    {
        $location = StorageLocation::create(['name' => 'Old', 'code' => 'UPD', 'is_active' => true]);

        $response = $this->putJson("/api/inventory/locations/{$location->id}", [
            'name' => 'Renamed',
            'code' => 'UPD',
        ]);

        $response->assertOk();
        $response->assertJsonPath('name', 'Renamed');
    }

    public function test_update_rejects_duplicate_code(): void
    {
        StorageLocation::create(['name' => 'Primary', 'code' => 'PRIMARY', 'is_active' => true]);
        $other = StorageLocation::create(['name' => 'Secondary', 'code' => 'SECOND', 'is_active' => true]);

        $response = $this->putJson("/api/inventory/locations/{$other->id}", [
            'name' => 'Secondary',
            'code' => 'PRIMARY',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }

    public function test_store_with_address_fields(): void
    {
        $response = $this->postJson('/api/inventory/locations', [
            'name' => 'West Hub',
            'code' => 'WEST-01',
            'address_street' => "400 Market St\nFloor 2",
            'country_code' => 'US',
            'postal_code' => '94105',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('country_code', 'US');
        $response->assertJsonPath('postal_code', '94105');
    }

    public function test_store_rejects_postal_without_country(): void
    {
        $response = $this->postJson('/api/inventory/locations', [
            'name' => 'Bad',
            'code' => 'BAD-ADDR',
            'postal_code' => '12345',
        ]);

        $response->assertStatus(422);
    }

    public function test_deactivate_succeeds_without_stock(): void
    {
        $location = StorageLocation::create(['name' => 'Empty', 'code' => 'EMP', 'is_active' => true]);

        $response = $this->postJson("/api/inventory/locations/{$location->id}/deactivate");

        $response->assertOk();
        $response->assertJsonPath('is_active', false);
    }

    public function test_deactivate_blocked_with_stock(): void
    {
        $location = StorageLocation::create(['name' => 'Stocked', 'code' => 'STK2', 'is_active' => true]);
        $item = InventoryItem::create([
            'product_name' => 'P',
            'sku' => 'P2',
            'has_variations' => false,
            'quantity_available' => 0,
            'quantity_reserved' => 0,
            'unit_price' => 1,
        ]);
        StockBalance::create([
            'balanceable_type' => InventoryItem::class,
            'balanceable_id' => $item->id,
            'storage_location_id' => $location->id,
            'quantity_available' => 3,
            'quantity_reserved' => 0,
        ]);

        $response = $this->postJson("/api/inventory/locations/{$location->id}/deactivate");

        $response->assertStatus(422);
        $this->assertTrue($location->fresh()->is_active);
    }
}
