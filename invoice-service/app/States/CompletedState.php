<?php

namespace App\States;

class CompletedState extends InvoiceState
{
    public static $name = 'completed';

    public function label(): string
    {
        return 'Completed';
    }
}
