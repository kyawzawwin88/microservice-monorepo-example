<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * EventLog tracks every incoming event with raw payload.
 * This provides an audit trail for debugging and tracing
 * event-driven communication across microservices.
 */
class EventLog extends Model
{
    protected $fillable = [
        'correlation_id',
        'event_type',
        'source_service',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Log an incoming event
     */
    public static function logEvent(
        string $correlationId,
        string $eventType,
        string $sourceService,
        array $payload
    ): self {
        return self::create([
            'correlation_id' => $correlationId,
            'event_type' => $eventType,
            'source_service' => $sourceService,
            'payload' => $payload,
        ]);
    }

    /**
     * Mark event as processed
     */
    public function markProcessed(): void
    {
        $this->update(['processed_at' => now()]);
    }
}
