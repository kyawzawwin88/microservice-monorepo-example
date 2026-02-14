<?php

namespace App\Models;

use App\Models\Concerns\HasCorrelationId;
use App\States\InvoiceState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStates\HasStates;

class Invoice extends Model
{
    use HasFactory, HasStates, HasCorrelationId, SoftDeletes;

    protected $fillable = [
        'correlation_id',
        'order_id',
        'invoice_number',
        'customer_name',
        'customer_email',
        'amount',
        'line_items',
        'state',
        'state_failure_description',
    ];

    protected $casts = [
        'state' => InvoiceState::class,
        'line_items' => 'array',
        'amount' => 'decimal:2',
    ];
}
