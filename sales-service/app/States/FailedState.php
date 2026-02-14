<?php

namespace App\States;

class FailedState extends OrderState
{
    public static $name = 'failed';

    public function label(): string
    {
        return 'Failed';
    }
}
