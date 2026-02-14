<?php

namespace App\Listeners;

use App\Models\EventLog;

class LogIncomingEvent
{
    public function handle(object $event): void
    {
        $correlationId = $event->correlationId ?? 'unknown';
        $eventType = get_class($event);

        EventLog::logEvent(
            correlationId: $correlationId,
            eventType: class_basename($eventType),
            sourceService: $this->resolveSourceService($eventType),
            payload: json_decode(json_encode($event), true) ?? [],
        );
    }

    private function resolveSourceService(string $eventClass): string
    {
        if (str_contains($eventClass, 'Order')) return 'sales-service';
        if (str_contains($eventClass, 'Invoice')) return 'invoice-service';
        if (str_contains($eventClass, 'Payment')) return 'payment-service';
        return 'inventory-service';
    }
}
