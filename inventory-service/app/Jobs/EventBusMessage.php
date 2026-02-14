<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * Generic event bus message for cross-service communication.
 *
 * This job is pushed to the shared eventbus database queue.
 * When processed by the consuming service's worker, it reconstructs
 * the event and dispatches it locally to trigger the service's listeners.
 *
 * Idempotency: Uses Redis cache to ensure each (event_type, correlation_id)
 * combination is only processed ONCE, even if the eventbus re-delivers
 * the message multiple times.
 */
class EventBusMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $eventClass,
        public array $eventData,
    ) {}

    public function handle(): void
    {
        if (!class_exists($this->eventClass)) {
            return;
        }

        // Extract correlation ID for deduplication
        $correlationId = $this->eventData['correlationId'] ?? null;

        // Idempotency guard: skip if already processed
        if ($correlationId) {
            $cacheKey = 'eventbus:processed:' . class_basename($this->eventClass) . ':' . $correlationId;
            if (!Cache::add($cacheKey, true, 3600)) {
                // Already processed — skip duplicate
                return;
            }
        }

        $event = new $this->eventClass(...array_values($this->eventData));
        event($event);
    }
}
