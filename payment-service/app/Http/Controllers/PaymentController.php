<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class PaymentController extends Controller
{
    /**
     * List all payments (paginated).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json(
            Payment::orderBy('created_at', 'desc')->paginate(20)
        );
    }

    /**
     * Get payment by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        return response()->json(Payment::findOrFail($id));
    }

    /**
     * Get payment by correlation ID.
     *
     * @param string $correlationId
     * @return JsonResponse
     */
    public function showByCorrelation(string $correlationId): JsonResponse
    {
        $payment = Payment::where('correlation_id', $correlationId)->firstOrFail();
        return response()->json($payment);
    }

    /**
     * Soft-delete a payment.
     *
     * Guard: cannot delete a payment if the related order has been delivered.
     * Once goods have shipped, the payment record must be preserved.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);

        // Guard: check if the related order has been delivered via Sales service API
        if ($payment->correlation_id) {
            try {
                $salesServiceUrl = env('SALES_SERVICE_URL', 'http://sales-service:8000');
                $response = @file_get_contents("{$salesServiceUrl}/api/orders/correlation/{$payment->correlation_id}");
                if ($response !== false) {
                    $order = json_decode($response, true);
                    if ($order && isset($order['status']) && $order['status'] === 'delivered') {
                        return response()->json([
                            'message' => 'Cannot delete payment — the related order has been delivered. Payment records must be preserved after delivery.',
                        ], 422);
                    }
                }
            } catch (\Throwable $e) {
                // If sales service is unreachable, allow deletion
            }
        }

        $payment->delete();

        return response()->json(['message' => 'Payment deleted successfully.']);
    }
}
