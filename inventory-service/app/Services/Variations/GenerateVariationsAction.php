<?php

namespace App\Services\Variations;

use App\Models\InventoryItem;
use App\Models\InventoryVariation;
use App\Models\StockBalance;
use App\Models\StorageLocation;
use App\Models\VariationDimension;
use App\Models\VariationDimensionValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GenerateVariationsAction
{
    public function __construct(
        private readonly BuildVariationSkuAction $buildVariationSku,
    ) {}

    /**
     * @param  array<int, array{name: string, values: array<int, string>}>  $dimensions
     * @return Collection<int, InventoryVariation>
     */
    public function execute(
        InventoryItem $item,
        array $dimensions,
        int $locationId,
        int $initialQuantityPerVariation = 0,
    ): Collection {
        return DB::transaction(function () use ($item, $dimensions, $locationId, $initialQuantityPerVariation) {
            $dimensionModels = $this->persistDimensions($item, $dimensions);
            $combinations = $this->cartesianProduct($dimensionModels);

            $variations = collect();
            foreach ($combinations as $combo) {
                $valueIds = array_map(fn (VariationDimensionValue $v) => $v->id, $combo);
                sort($valueIds);
                $attributeHash = hash('sha256', implode('|', $valueIds));
                $labels = array_map(fn (VariationDimensionValue $v) => $v->value, $combo);
                $label = implode(' / ', $labels);
                $sku = $this->buildVariationSku->execute($item, $labels);

                if (InventoryVariation::where('inventory_item_id', $item->id)
                    ->where(function ($query) use ($attributeHash, $sku) {
                        $query->where('attribute_hash', $attributeHash)
                            ->orWhere('sku', $sku);
                    })
                    ->exists()) {
                    continue;
                }

                $variation = InventoryVariation::create([
                    'inventory_item_id' => $item->id,
                    'sku' => $sku,
                    'attribute_hash' => $attributeHash,
                    'label' => $label,
                ]);

                $variation->dimensionValues()->sync($valueIds);

                StockBalance::create([
                    'balanceable_type' => InventoryVariation::class,
                    'balanceable_id' => $variation->id,
                    'storage_location_id' => $locationId,
                    'quantity_available' => $initialQuantityPerVariation,
                    'quantity_reserved' => 0,
                ]);

                $variations->push($variation);
            }

            return $variations;
        });
    }

    /**
     * @param  array<int, array{name: string, values: array<int, string>}>  $dimensions
     * @return array<int, VariationDimension>
     */
    private function persistDimensions(InventoryItem $item, array $dimensions): array
    {
        $result = [];
        foreach ($dimensions as $index => $dimension) {
            $model = VariationDimension::create([
                'inventory_item_id' => $item->id,
                'name' => $dimension['name'],
                'sort_order' => $index,
            ]);

            foreach ($dimension['values'] as $value) {
                VariationDimensionValue::create([
                    'variation_dimension_id' => $model->id,
                    'value' => $value,
                ]);
            }

            $model->load('values');
            $result[] = $model;
        }

        return $result;
    }

    /**
     * @param  array<int, VariationDimension>  $dimensions
     * @return array<int, array<int, VariationDimensionValue>>
     */
    private function cartesianProduct(array $dimensions): array
    {
        $sets = array_map(
            fn (VariationDimension $d) => $d->values->all(),
            $dimensions
        );

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
