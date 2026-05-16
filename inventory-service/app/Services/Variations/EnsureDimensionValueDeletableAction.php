<?php

namespace App\Services\Variations;

use App\Models\InventoryVariation;
use App\Models\VariationDimensionValue;
use InvalidArgumentException;

class EnsureDimensionValueDeletableAction
{
    /**
     * @throws InvalidArgumentException when any variation using this value has stock
     */
    public function execute(VariationDimensionValue $dimensionValue): void
    {
        $variationIds = InventoryVariation::query()
            ->whereHas('dimensionValues', fn ($q) => $q->where('variation_dimension_values.id', $dimensionValue->id))
            ->pluck('id');

        if ($variationIds->isEmpty()) {
            return;
        }

        $hasStock = \App\Models\StockBalance::query()
            ->where('balanceable_type', InventoryVariation::class)
            ->whereIn('balanceable_id', $variationIds)
            ->where(function ($q) {
                $q->where('quantity_available', '>', 0)
                    ->orWhere('quantity_reserved', '>', 0);
            })
            ->exists();

        if ($hasStock) {
            throw new InvalidArgumentException(
                "Cannot remove dimension value \"{$dimensionValue->value}\" while variations still have stock."
            );
        }
    }
}
