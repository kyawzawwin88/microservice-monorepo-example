<?php

namespace App\Providers;

use App\Events\InvoiceCreationFailed;
use App\Events\OrderCreated;
use App\Events\PaymentCompleted;
use App\Events\PaymentProcessingFailed;
use App\Listeners\HandleInvoiceCreationFailed;
use App\Listeners\HandlePaymentCompleted;
use App\Listeners\HandlePaymentProcessingFailed;
use App\Listeners\LogIncomingEvent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     */
    protected $listen = [
        // Handler listeners are auto-discovered by Laravel 11 — only register LogIncomingEvent explicitly
        OrderCreated::class => [
            LogIncomingEvent::class,
        ],
        PaymentCompleted::class => [
            LogIncomingEvent::class,
        ],
        InvoiceCreationFailed::class => [
            LogIncomingEvent::class,
        ],
        PaymentProcessingFailed::class => [
            LogIncomingEvent::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
