<?php

namespace App\States;

class CompletedState extends OrderState
{
    public static $name = 'completed';

    public function label(): string
    {
        return 'Completed';
    }
}
