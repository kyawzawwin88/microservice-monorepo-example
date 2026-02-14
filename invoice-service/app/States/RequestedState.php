<?php

namespace App\States;

class RequestedState extends InvoiceState
{
    public static $name = 'requested';

    public function label(): string
    {
        return 'Requested';
    }
}
