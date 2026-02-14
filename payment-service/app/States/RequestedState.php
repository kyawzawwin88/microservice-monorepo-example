<?php

namespace App\States;

class RequestedState extends PaymentState
{
    public static $name = 'requested';
    public function label(): string { return 'Requested'; }
}
