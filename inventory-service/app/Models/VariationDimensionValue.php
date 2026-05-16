<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VariationDimensionValue extends Model
{
    protected $fillable = [
        'variation_dimension_id',
        'value',
    ];

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(VariationDimension::class, 'variation_dimension_id');
    }

    public function variations(): BelongsToMany
    {
        return $this->belongsToMany(
            InventoryVariation::class,
            'inventory_variation_dimension_value',
            'variation_dimension_value_id',
            'inventory_variation_id'
        );
    }
}
