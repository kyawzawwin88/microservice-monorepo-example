<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function markProcessed(): void
    {
        $this->update(['processed_at' => now()]);
    }
}
