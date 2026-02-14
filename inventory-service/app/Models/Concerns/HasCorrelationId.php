<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasCorrelationId
{
    public static function bootHasCorrelationId(): void
    {
        static::creating(function ($model) {
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) Str::uuid();
            }
        });
    }

    public function scopeByCorrelation($query, string $correlationId)
    {
        return $query->where('correlation_id', $correlationId);
    }

    public static function existsByCorrelation(string $correlationId): bool
    {
        return static::where('correlation_id', $correlationId)->exists();
    }

    public static function findByCorrelation(string $correlationId)
    {
        return static::where('correlation_id', $correlationId)->firstOrFail();
    }

    public function markAsFailed(string $reason): void
    {
        $this->update(['state_failure_description' => $reason]);
    }
}
