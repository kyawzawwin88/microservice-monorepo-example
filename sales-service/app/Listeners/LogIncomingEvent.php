<?php

namespace App\Listeners;

use App\Models\EventLog;

/**
 * Universal listener that logs every incoming event
 * with its raw payload for audit and debugging purposes.
 */
class LogIncomingEvent
{
    public function handle(object $event): void
    {
        $correlationId = $event->correlationId ?? 'unknown';
        $eventType = get_class($event);
        $sourceService = $this->resolveSourceService($eventType);

        EventLog::logEvent(
            correlationId: $correlationId,
            eventType: class_basename($eventType),
            sourceService: $sourceService,
            payload: $this->extractPayload($event),
        );
    }

    private function resolveSourceService(string $eventClass): string
    {
        if (str_contains($eventClass, 'Payment')) return 'payment-service';
        if (str_contains($eventClass, 'Invoice')) return 'invoice-service';
        if (str_contains($eventClass, 'Inventory')) return 'inventory-service';
        return 'sales-service';
    }

    private function extractPayload(object $event): array
    {
        return json_decode(json_encode($event), true) ?? [];
    }
}
