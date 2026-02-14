<?php

namespace App\Providers;

use App\Events\OrderCreated;
use App\Listeners\HandleOrderCreated;
use App\Listeners\LogIncomingEvent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderCreated::class => [
            LogIncomingEvent::class,
            // HandleOrderCreated is auto-discovered by Laravel 11 — do NOT list it here
        ],
    ];

    public function boot(): void {}

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
