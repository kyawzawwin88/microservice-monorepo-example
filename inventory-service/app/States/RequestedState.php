<?php

namespace App\States;

class RequestedState extends ReservationState
{
    public static $name = 'requested';
    public function label(): string { return 'Requested'; }
}
