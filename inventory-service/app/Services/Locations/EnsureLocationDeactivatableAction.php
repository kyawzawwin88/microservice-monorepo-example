<?php

namespace App\Services\Locations;

use App\Models\StockBalance;
use App\Models\StorageLocation;
use InvalidArgumentException;

class EnsureLocationDeactivatableAction
{
    /**
     * @throws InvalidArgumentException when location still holds stock
     */
    public function execute(StorageLocation $location): void
    {
        if (! $location->is_active) {
            throw new InvalidArgumentException('Location is already inactive.');
        }

        $hasStock = StockBalance::query()
            ->where('storage_location_id', $location->id)
            ->where(function ($q) {
                $q->where('quantity_available', '>', 0)
                    ->orWhere('quantity_reserved', '>', 0);
            })
            ->exists();

        if ($hasStock) {
            throw new InvalidArgumentException(
                "Cannot deactivate \"{$location->name}\" while it still has available or reserved stock. Transfer or clear stock first."
            );
        }
    }
}
