<?php

namespace App\States;

class FailedState extends ReservationState
{
    public static $name = 'failed';
    public function label(): string { return 'Failed'; }
}
