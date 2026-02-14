<?php

namespace App\Workflows;

use App\Activities\ClearOrderCacheActivity;
use App\Activities\DispatchEventBusActivity;
use App\Activities\UpdateOrderStateActivity;
use App\Activities\ValidateOrderActivity;
use App\Events\OrderCreated;
use App\Exceptions\OrderProcessingException;
use App\Models\Order;
use App\States\FailedState;
use Workflow\ActivityStub;
use Workflow\Workflow;

/**
 * NewOrderWorkflow - The main business workflow for creating orders.
 *
 * State lifecycle (microservice event tracking):
 *   requested → completed  (or failed on error)
 *
 * Business status:
 *   → submitted (initial)
 *
 * Inventory is NOT reserved at this stage.
 * Reservation happens later when payment is confirmed (OrderPaid event).
 */
class NewOrderWorkflow extends Workflow
{
    public function execute(string $correlationId, array $orderData)
    {
        try {
            // Activity 1: Validate order data and check idempotency
            $validation = yield ActivityStub::make(
                ValidateOrderActivity::class,
                $correlationId,
                $orderData
            );

            // If order already exists, skip creation (idempotent)
            if ($validation['already_exists']) {
                return $validation['order_id'];
            }

            // Activity 2: Create order record
            //   state  = 'requested' (default)
            //   status = 'submitted' (business logic)
            $orderId = yield ActivityStub::make(
                UpdateOrderStateActivity::class,
                $correlationId,
                $orderData,
                'requested',   // state: event processing in progress
                'submitted'    // status: business status
            );

            // Activity 3: Clear order-related caches
            yield ActivityStub::make(
                ClearOrderCacheActivity::class,
                $correlationId,
                $orderId
            );

            // Build event payload for cross-service communication
            $eventData = [
                'correlationId' => $correlationId,
                'orderId' => $orderId,
                'customerName' => $orderData['customer_name'],
                'customerEmail' => $orderData['customer_email'] ?? '',
                'totalAmount' => (float) $orderData['total_amount'],
                'items' => $orderData['items'],
                'simulateFailure' => !empty($orderData['simulate_failure']),
            ];

            // Activity 4: Publish to Invoice service via event bus
            // Wrapped in Activity so it only fires ONCE (not on every workflow replay)
            yield ActivityStub::make(
                DispatchEventBusActivity::class,
                OrderCreated::class,
                $eventData,
                'eventbus',
                'invoice'
            );

            // Activity 5: Mark event processing as completed
            //   state: requested → completed
            yield ActivityStub::make(
                UpdateOrderStateActivity::class,
                $correlationId,
                $orderData,
                'completed'    // state: processing done
            );

            return $orderId;

        } catch (OrderProcessingException $e) {
            // Transition order to failed state
            $order = Order::where('correlation_id', $correlationId)->first();
            if ($order) {
                $order->markAsFailed($e->getMessage());
                $order->state->transitionTo(FailedState::class);
            }

            throw $e;
        }
    }
}
