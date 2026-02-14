<?php

namespace App\Workflows;

use App\Activities\ChargePaymentActivity;
use App\Activities\ClearPaymentCacheActivity;
use App\Activities\DispatchEventBusActivity;
use App\Activities\UpdatePaymentStateActivity;
use App\Events\PaymentCompleted;
use App\Events\PaymentProcessingFailed;
use App\Exceptions\PaymentProcessingException;
use App\Models\Payment;
use Workflow\ActivityStub;
use Workflow\Workflow;

/**
 * ProcessPaymentWorkflow - Processes payment for an invoice.
 *
 * Triggered by InvoiceCreated event from Invoice service.
 * 1. (Optional) Simulate failure for demo purposes
 * 2. Charge payment (idempotent via correlation_id)
 * 3. Update payment state to 'completed'
 * 4. Clear payment caches
 * 5. Publish PaymentCompleted event to Sales service via event bus
 *
 * On failure: dispatches PaymentProcessingFailed back to Sales service
 * so the order state can be marked as 'failed'.
 */
class ProcessPaymentWorkflow extends Workflow
{
    public function execute(string $correlationId, array $invoiceData)
    {
        try {
            // ── Failure simulation (for eventual consistency demo) ──
            // When simulate_failure is set, THIS service fails.
            // Invoice service performs its own independent random check.
            if ($invoiceData['simulate_failure'] === true) {
                throw new PaymentProcessingException(
                    "[SIMULATED] Payment gateway timeout — failure for eventual consistency demo",
                    $correlationId
                );
            }

            // Activity 1: Charge payment (with gateway simulation)
            $paymentData = yield ActivityStub::make(
                ChargePaymentActivity::class,
                $correlationId,
                $invoiceData
            );

            $paymentId = $paymentData['payment_id'];

            // Activity 2: Update payment state to completed
            yield ActivityStub::make(
                UpdatePaymentStateActivity::class,
                $correlationId,
                'completed'
            );

            // Activity 3: Clear payment caches
            yield ActivityStub::make(
                ClearPaymentCacheActivity::class,
                $correlationId,
                $paymentId
            );

            // Build event payload for cross-service communication
            $eventPayload = [
                'correlationId' => $correlationId,
                'paymentId' => $paymentId,
                'amount' => (float) $invoiceData['amount'],
                'paymentMethod' => $paymentData['payment_method'] ?? 'credit_card',
            ];

            // Activity 4: Publish to Sales service via event bus
            // Wrapped in Activity so it only fires ONCE (not on every workflow replay)
            yield ActivityStub::make(
                DispatchEventBusActivity::class,
                PaymentCompleted::class,
                $eventPayload,
                'eventbus',
                'sales'
            );

            return $paymentId;

        } catch (PaymentProcessingException $e) {
            $payment = Payment::where('correlation_id', $correlationId)->first();
            if ($payment) {
                $payment->markAsFailed($e->getMessage());
                $payment->state->transitionTo(\App\States\FailedState::class);
            }

            // Propagate failure back to Sales service so order state → failed
            // Wrapped in Activity so it only fires ONCE
            yield ActivityStub::make(
                DispatchEventBusActivity::class,
                PaymentProcessingFailed::class,
                [
                    'correlationId' => $correlationId,
                    'reason' => $e->getMessage(),
                ],
                'eventbus',
                'sales'
            );

            throw $e;
        }
    }
}
