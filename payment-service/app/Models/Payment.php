<?php

namespace App\Models;

use App\Models\Concerns\HasCorrelationId;
use App\States\PaymentState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStates\HasStates;

class Payment extends Model
{
    use HasFactory, HasStates, HasCorrelationId, SoftDeletes;

    protected $fillable = [
        'correlation_id',
        'invoice_id',
        'invoice_number',
        'amount',
        'payment_method',
        'transaction_reference',
        'state',
        'state_failure_description',
    ];

    protected $casts = [
        'state' => PaymentState::class,
        'amount' => 'decimal:2',
    ];
}
