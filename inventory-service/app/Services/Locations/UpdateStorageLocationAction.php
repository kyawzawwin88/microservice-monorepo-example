<?php

namespace App\Services\Locations;

use App\Models\StorageLocation;

class UpdateStorageLocationAction
{
    /**
     * @param  array{name: string, code: string, address_street?: ?string, country_code?: ?string, postal_code?: ?string}  $data
     */
    public function execute(StorageLocation $location, array $data): StorageLocation
    {
        $location->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'address_street' => $data['address_street'] ?? null,
            'country_code' => $data['country_code'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
        ]);

        return $location->fresh();
    }
}
