<?php

namespace App\Models;

use App\Models\Concerns\HasCorrelationId;
use App\States\OrderState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStates\HasStates;

class Order extends Model
{
    use HasFactory, HasStates, HasCorrelationId, SoftDeletes;

    /**
     * Order statuses (business logic).
     * Tracked in the 'status' column — separate from 'state'.
     */
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_PAID = 'paid';
    public const STATUS_DELIVERED = 'delivered';

    protected $fillable = [
        'correlation_id',
        'customer_name',
        'customer_email',
        'total_amount',
        'items',
        'state',
        'status',
        'state_failure_description',
    ];

    protected $casts = [
        'state' => OrderState::class,
        'items' => 'array',
        'total_amount' => 'decimal:2',
    ];
}
