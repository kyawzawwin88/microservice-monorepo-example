<?php

namespace App\States;

class RequestedState extends OrderState
{
    public static $name = 'requested';

    public function label(): string
    {
        return 'Requested';
    }
}
