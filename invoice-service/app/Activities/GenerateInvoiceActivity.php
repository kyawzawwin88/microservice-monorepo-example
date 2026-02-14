<?php

namespace App\Activities;

use App\Exceptions\InvoiceProcessingException;
use App\Models\Invoice;
use App\States\FailedState;
use App\States\RequestedState;
use Workflow\Activity;

/**
 * Activity: Generate an invoice record from order data.
 * Ensures idempotency via correlation_id check.
 * If a previous attempt failed, resets state to requested for retry.
 */
class GenerateInvoiceActivity extends Activity
{
    public function execute(string $correlationId, array $orderData): array
    {
        // Idempotency: skip if invoice already exists for this correlation
        if (Invoice::existsByCorrelation($correlationId)) {
            $existing = Invoice::findByCorrelation($correlationId);

            // If the previous attempt failed, reset state to requested for retry
            if ($existing->state instanceof FailedState) {
                $existing->state->transitionTo(RequestedState::class);
                $existing->update(['state_failure_description' => null]);
            }

            return [
                'invoice_id' => $existing->id,
                'invoice_number' => $existing->invoice_number,
                'already_exists' => true,
            ];
        }

        // Generate invoice number
        $invoiceNumber = 'INV-' . strtoupper(substr($correlationId, 0, 8)) . '-' . now()->format('Ymd');

        if (($orderData['total_amount'] ?? 0) <= 0) {
            throw new InvoiceProcessingException(
                'Cannot generate invoice for zero or negative amount',
                $correlationId
            );
        }

        $invoice = Invoice::create([
            'correlation_id' => $correlationId,
            'order_id' => $orderData['order_id'],
            'invoice_number' => $invoiceNumber,
            'customer_name' => $orderData['customer_name'],
            'customer_email' => $orderData['customer_email'] ?? '',
            'amount' => $orderData['total_amount'],
            'line_items' => $orderData['items'] ?? [],
        ]);

        return [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'already_exists' => false,
        ];
    }
}
