<?php

namespace App\Workflows;

use App\Activities\ClearInvoiceCacheActivity;
use App\Activities\DispatchEventBusActivity;
use App\Activities\GenerateInvoiceActivity;
use App\Activities\UpdateInvoiceStateActivity;
use App\Events\InvoiceCreated;
use App\Events\InvoiceCreationFailed;
use App\Exceptions\InvoiceProcessingException;
use App\Models\Invoice;
use Workflow\ActivityStub;
use Workflow\Workflow;

/**
 * CreateInvoiceWorkflow - Generates invoice from order data.
 *
 * Triggered by OrderCreated event from Sales service.
 * 1. (Optional) Simulate random failure for demo purposes
 * 2. Generate invoice record (idempotent)
 * 3. Update invoice state to 'completed'
 * 4. Clear invoice caches
 * 5. Publish InvoiceCreated event to Payment service via event bus
 *
 * On failure: dispatches InvoiceCreationFailed back to Sales service
 * so the order state can be marked as 'failed'.
 */
class CreateInvoiceWorkflow extends Workflow
{
    public function execute(string $correlationId, array $orderData)
    {
        try {
            // ── Failure simulation (for eventual consistency demo) ──
            // When simulate_failure is set, randomly decide if THIS service fails.
            // Uses rand(1,10): values 1-5 → fail (50%), 6-10 → succeed.
            // Payment service performs its own independent random check.
            if ($orderData['simulate_failure'] === true) {
                $roll = rand(1, 10);
                if ($roll <= 5) {
                    throw new InvoiceProcessingException(
                        "[SIMULATED] Invoice generation service unavailable (rolled {$roll}/10) — random failure for eventual consistency demo",
                        $correlationId
                    );
                }
            }

            // Activity 1: Generate invoice
            $invoiceData = yield ActivityStub::make(
                GenerateInvoiceActivity::class,
                $correlationId,
                $orderData
            );

            $invoiceId = $invoiceData['invoice_id'];

            // Activity 2: Update invoice state to completed
            yield ActivityStub::make(
                UpdateInvoiceStateActivity::class,
                $correlationId,
                'completed'
            );

            // Activity 3: Clear invoice caches
            yield ActivityStub::make(
                ClearInvoiceCacheActivity::class,
                $correlationId,
                $invoiceId
            );

            // Build event payload for cross-service communication
            $eventPayload = [
                'correlationId' => $correlationId,
                'invoiceId' => $invoiceId,
                'invoiceNumber' => $invoiceData['invoice_number'],
                'orderId' => $orderData['order_id'],
                'customerName' => $orderData['customer_name'],
                'customerEmail' => $orderData['customer_email'] ?? '',
                'amount' => (float) $orderData['total_amount'],
                'lineItems' => $orderData['items'] ?? [],
                'simulateFailure' => !empty($orderData['simulate_failure']),
            ];

            // Activity 4: Publish to Payment service via event bus
            // Wrapped in Activity so it only fires ONCE (not on every workflow replay)
            yield ActivityStub::make(
                DispatchEventBusActivity::class,
                InvoiceCreated::class,
                $eventPayload,
                'eventbus',
                'payment'
            );

            return $invoiceId;

        } catch (InvoiceProcessingException $e) {
            $invoice = Invoice::where('correlation_id', $correlationId)->first();
            if ($invoice) {
                $invoice->markAsFailed($e->getMessage());
                $invoice->state->transitionTo(\App\States\FailedState::class);
            }

            // Propagate failure back to Sales service so order state → failed
            // Wrapped in Activity so it only fires ONCE
            yield ActivityStub::make(
                DispatchEventBusActivity::class,
                InvoiceCreationFailed::class,
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
