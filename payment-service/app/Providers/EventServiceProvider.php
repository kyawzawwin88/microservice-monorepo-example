<?php

namespace App\Providers;

use App\Events\InvoiceCreated;
use App\Listeners\HandleInvoiceCreated;
use App\Listeners\LogIncomingEvent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InvoiceCreated::class => [
            LogIncomingEvent::class,
            // HandleInvoiceCreated is auto-discovered by Laravel 11 — do NOT list it here
        ],
    ];

    public function boot(): void {}

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
