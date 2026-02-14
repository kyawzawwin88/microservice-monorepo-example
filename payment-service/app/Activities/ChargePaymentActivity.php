<?php

namespace App\Activities;

use App\Exceptions\PaymentProcessingException;
use App\Models\Payment;
use Illuminate\Support\Str;
use Workflow\Activity;

/**
 * Activity: Process the payment charge.
 * Simulates payment gateway integration with idempotency.
 */
class ChargePaymentActivity extends Activity
{
    public function execute(string $correlationId, array $invoiceData): array
    {
        // Idempotency: skip if payment already exists for this correlation
        if (Payment::existsByCorrelation($correlationId)) {
            $existing = Payment::findByCorrelation($correlationId);
            return [
                'payment_id' => $existing->id,
                'transaction_reference' => $existing->transaction_reference,
                'already_exists' => true,
            ];
        }

        $amount = $invoiceData['amount'] ?? 0;

        if ($amount <= 0) {
            throw new PaymentProcessingException(
                'Payment amount must be greater than zero',
                $correlationId
            );
        }

        // Simulate payment gateway processing
        $transactionReference = 'TXN-' . strtoupper(Str::random(12));
        $paymentMethod = 'credit_card'; // Simulated

        $payment = Payment::create([
            'correlation_id' => $correlationId,
            'invoice_id' => $invoiceData['invoice_id'],
            'invoice_number' => $invoiceData['invoice_number'] ?? '',
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'transaction_reference' => $transactionReference,
        ]);

        return [
            'payment_id' => $payment->id,
            'transaction_reference' => $transactionReference,
            'payment_method' => $paymentMethod,
            'already_exists' => false,
        ];
    }
}
