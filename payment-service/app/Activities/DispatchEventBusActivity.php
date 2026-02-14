<?php

namespace App\Activities;

use App\Jobs\EventBusMessage;
use Workflow\Activity;

/**
 * Activity: Dispatch an EventBusMessage to a cross-service queue.
 *
 * Wrapping the dispatch in an Activity ensures it only fires ONCE.
 * In a durable workflow, bare code in execute() replays on every
 * workflow replay, but Activity results are cached — so this
 * prevents duplicate event dispatches.
 */
class DispatchEventBusActivity extends Activity
{
    public function execute(string $eventClass, array $eventData, string $connection, string $queue): bool
    {
        EventBusMessage::dispatch($eventClass, $eventData)
            ->onConnection($connection)
            ->onQueue($queue);

        return true;
    }
}
