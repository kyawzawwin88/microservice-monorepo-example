<?php

namespace App\Providers;

use App\Events\OrderCreated;
use App\Events\OrderDeleted;
use App\Events\OrderDelivered;
use App\Events\OrderPaid;
use App\Listeners\HandleOrderCreated;
use App\Listeners\HandleOrderDeleted;
use App\Listeners\HandleOrderDelivered;
use App\Listeners\HandleOrderPaid;
use App\Listeners\LogIncomingEvent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Handler listeners are auto-discovered by Laravel 11 — only register LogIncomingEvent explicitly

        // OrderCreated — logging only (no reservation at this stage)
        OrderCreated::class => [
            LogIncomingEvent::class,
        ],

        // OrderPaid — reserve inventory after payment is confirmed
        OrderPaid::class => [
            LogIncomingEvent::class,
        ],

        // OrderDelivered — deduct reserved stock (goods left warehouse)
        OrderDelivered::class => [
            LogIncomingEvent::class,
        ],

        // OrderDeleted — release reserved stock
        OrderDeleted::class => [
            LogIncomingEvent::class,
        ],
    ];

    public function boot(): void {}

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
