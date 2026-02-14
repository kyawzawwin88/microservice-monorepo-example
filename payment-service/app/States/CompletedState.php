<?php

namespace App\States;

class CompletedState extends PaymentState
{
    public static $name = 'completed';
    public function label(): string { return 'Completed'; }
}
