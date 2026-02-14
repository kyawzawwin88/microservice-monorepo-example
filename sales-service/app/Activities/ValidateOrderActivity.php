<?php

namespace App\Activities;

use App\Exceptions\OrderProcessingException;
use App\Models\Order;
use Workflow\Activity;

/**
 * Activity: Validate order data and ensure idempotency.
 * Checks that the order has valid items, amounts, and
 * hasn't been processed already (idempotency via correlation_id).
 */
class ValidateOrderActivity extends Activity
{
    public function execute(string $correlationId, array $orderData): array
    {
        // Idempotency check: skip if already processed
        if (Order::existsByCorrelation($correlationId)) {
            $existing = Order::findByCorrelation($correlationId);
            return [
                'valid' => true,
                'order_id' => $existing->id,
                'already_exists' => true,
            ];
        }

        // Validate order data
        if (empty($orderData['items'])) {
            throw new OrderProcessingException(
                'Order must contain at least one item',
                $correlationId
            );
        }

        if (($orderData['total_amount'] ?? 0) <= 0) {
            throw new OrderProcessingException(
                'Order total amount must be greater than zero',
                $correlationId
            );
        }

        if (empty($orderData['customer_name'])) {
            throw new OrderProcessingException(
                'Customer name is required',
                $correlationId
            );
        }

        return [
            'valid' => true,
            'order_id' => null,
            'already_exists' => false,
        ];
    }
}
