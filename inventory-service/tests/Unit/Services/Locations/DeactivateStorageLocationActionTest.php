<?php

namespace Tests\Unit\Services\Locations;

use App\Models\StorageLocation;
use App\Services\Locations\DeactivateStorageLocationAction;
use App\Services\Locations\EnsureLocationDeactivatableAction;
use Tests\TestCase;

class DeactivateStorageLocationActionTest extends TestCase
{
    public function test_deactivates_empty_location(): void
    {
        $location = StorageLocation::create(['name' => 'Site', 'code' => 'SITE', 'is_active' => true]);
        $action = new DeactivateStorageLocationAction(new EnsureLocationDeactivatableAction);

        $result = $action->execute($location);

        $this->assertFalse($result->is_active);
    }
}
