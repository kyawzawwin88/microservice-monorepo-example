<?php

namespace App\Services\Locations;

use App\Models\StorageLocation;

class DeactivateStorageLocationAction
{
    public function __construct(
        private readonly EnsureLocationDeactivatableAction $ensureDeactivatable,
    ) {}

    public function execute(StorageLocation $location): StorageLocation
    {
        $this->ensureDeactivatable->execute($location);

        $location->update(['is_active' => false]);

        return $location->fresh();
    }
}
