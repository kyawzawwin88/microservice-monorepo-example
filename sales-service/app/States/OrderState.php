<?php

namespace App\States;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Base state for Order model using spatie/laravel-model-states.
 *
 * 'state' tracks the microservice event-processing lifecycle:
 * - Requested: Service received the event/API request and is processing
 * - Completed: Service finished processing successfully
 * - Failed:    Processing encountered an error
 *
 * NOTE: Business order status (submitted/paid/delivered) is tracked
 *       separately in the 'status' column — NOT here.
 */
abstract class OrderState extends State
{
    abstract public function label(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(RequestedState::class)
            ->allowTransition(RequestedState::class, CompletedState::class)
            ->allowTransition(RequestedState::class, FailedState::class)
            ->allowTransition(CompletedState::class, FailedState::class)  // Downstream service failure
            ->allowTransition(FailedState::class, RequestedState::class); // Allow retry
    }
}
