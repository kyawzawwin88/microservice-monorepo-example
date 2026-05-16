<?php

namespace App\Services\Variations;

use App\Models\InventoryItem;
use App\Models\InventoryVariation;
use App\Models\StockBalance;
use App\Models\VariationDimension;
use App\Models\VariationDimensionValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AppendDimensionValueAction
{
    public function __construct(
        private readonly BuildVariationSkuAction $buildVariationSku,
    ) {}

    public function execute(
        VariationDimension $dimension,
        string $value,
        int $locationId,
        int $initialQuantity = 0,
    ): Collection {
        return DB::transaction(function () use ($dimension, $value, $locationId, $initialQuantity) {
            $newValue = VariationDimensionValue::create([
                'variation_dimension_id' => $dimension->id,
                'value' => $value,
            ]);

            $item = $dimension->inventoryItem;
            $otherDimensions = $item->variationDimensions()
                ->where('id', '!=', $dimension->id)
                ->with('values')
                ->orderBy('sort_order')
                ->get();

            $combinations = $this->combinationsWithValue($otherDimensions, $newValue);
            $created = collect();

            foreach ($combinations as $combo) {
                $valueModels = $combo;
                $valueIds = array_map(fn (VariationDimensionValue $v) => $v->id, $valueModels);
                sort($valueIds);
                $attributeHash = hash('sha256', implode('|', $valueIds));

                if (InventoryVariation::where('inventory_item_id', $item->id)
                    ->where('attribute_hash', $attributeHash)
                    ->exists()) {
                    continue;
                }

                $labels = array_map(fn (VariationDimensionValue $v) => $v->value, $valueModels);
                $variation = InventoryVariation::create([
                    'inventory_item_id' => $item->id,
                    'sku' => $this->buildVariationSku->execute($item, $labels),
                    'attribute_hash' => $attributeHash,
                    'label' => implode(' / ', $labels),
                ]);
                $variation->dimensionValues()->sync($valueIds);

                StockBalance::create([
                    'balanceable_type' => InventoryVariation::class,
                    'balanceable_id' => $variation->id,
                    'storage_location_id' => $locationId,
                    'quantity_available' => $initialQuantity,
                    'quantity_reserved' => 0,
                ]);

                $created->push($variation);
            }

            return $created;
        });
    }

    /**
     * @param  Collection<int, VariationDimension>  $otherDimensions
     * @return array<int, array<int, VariationDimensionValue>>
     */
    private function combinationsWithValue(Collection $otherDimensions, VariationDimensionValue $newValue): array
    {
        $sets = $otherDimensions->map(fn (VariationDimension $d) => $d->values->all())->all();
        $sets[] = [$newValue];

        $result = [[]];
        foreach ($sets as $set) {
            $append = [];
            foreach ($result as $product) {
                foreach ($set as $value) {
                    $append[] = array_merge($product, [$value]);
                }
            }
            $result = $append;
        }

        return $result;
    }
}
