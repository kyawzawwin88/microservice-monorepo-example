<?php

namespace App\States;

class FailedState extends PaymentState
{
    public static $name = 'failed';
    public function label(): string { return 'Failed'; }
}
