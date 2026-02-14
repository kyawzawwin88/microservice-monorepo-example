<?php

namespace App\States;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class PaymentState extends State
{
    abstract public function label(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(RequestedState::class)
            ->allowTransition(RequestedState::class, CompletedState::class)
            ->allowTransition(RequestedState::class, FailedState::class)
            ->allowTransition(FailedState::class, RequestedState::class);
    }
}
