<?php

namespace App\Models;

use App\Models\Concerns\HasCorrelationId;
use App\States\ReservationState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\HasStates;

class InventoryReservation extends Model
{
    use HasFactory, HasStates, HasCorrelationId;

    protected $fillable = [
        'correlation_id',
        'order_id',
        'product_name',
        'quantity',
        'reserved_quantity',
        'state',
        'state_failure_description',
    ];

    protected $casts = [
        'state' => ReservationState::class,
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
    ];
}
