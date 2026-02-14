<?php

namespace App\States;

class FailedState extends InvoiceState
{
    public static $name = 'failed';

    public function label(): string
    {
        return 'Failed';
    }
}
