<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Trait to add correlation ID and state tracking fields
 * to any model in the microservice architecture.
 *
 * Every record must have:
 * - correlation_id: Unique UUID across all services for tracing a common request
 * - state: requested | completed | failed
 * - state_failure_description: Error description when state is failed
 */
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

    /**
     * Scope: find by correlation ID
     */
    public function scopeByCorrelation($query, string $correlationId)
    {
        return $query->where('correlation_id', $correlationId);
    }

    /**
     * Check if record exists with the given correlation ID (idempotency check)
     */
    public static function existsByCorrelation(string $correlationId): bool
    {
        return static::where('correlation_id', $correlationId)->exists();
    }

    /**
     * Find or fail by correlation ID
     */
    public static function findByCorrelation(string $correlationId)
    {
        return static::where('correlation_id', $correlationId)->firstOrFail();
    }

    /**
     * Mark record as failed with description
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'state_failure_description' => $reason,
        ]);
    }
}
