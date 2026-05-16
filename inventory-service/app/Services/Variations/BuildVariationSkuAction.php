<?php

namespace App\Services\Variations;

use App\Models\InventoryItem;
use Illuminate\Support\Str;

class BuildVariationSkuAction
{
    /**
     * @param  array<int, string>  $valueLabels
     */
    public function execute(InventoryItem $item, array $valueLabels): string
    {
        $suffix = collect($valueLabels)
            ->map(fn (string $v) => Str::upper(Str::slug($v, '')))
            ->implode('-');

        return $item->sku.'-'.$suffix;
    }
}
