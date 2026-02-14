<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class InvoiceController extends Controller
{
    /**
     * List all invoices (paginated).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json(
            Invoice::orderBy('created_at', 'desc')->paginate(20)
        );
    }

    /**
     * Get invoice by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        return response()->json(Invoice::findOrFail($id));
    }

    /**
     * Get invoice by correlation ID.
     *
     * @param string $correlationId
     * @return JsonResponse
     */
    public function showByCorrelation(string $correlationId): JsonResponse
    {
        $invoice = Invoice::where('correlation_id', $correlationId)->firstOrFail();
        return response()->json($invoice);
    }

    /**
     * Soft-delete an invoice.
     * Prevents deletion if a related payment exists (checked via correlation_id on payment service).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        // Check if related payment exists by calling payment service
        try {
            $paymentServiceUrl = env('PAYMENT_SERVICE_URL', 'http://payment-service:8000');
            $response = @file_get_contents("{$paymentServiceUrl}/api/payments/correlation/{$invoice->correlation_id}");
            if ($response !== false) {
                $payment = json_decode($response, true);
                if ($payment && !empty($payment['id'])) {
                    return response()->json([
                        'message' => 'Cannot delete invoice — a related payment exists (Payment #' . $payment['id'] . ').',
                    ], 422);
                }
            }
        } catch (\Throwable $e) {
            // If payment service is unreachable, allow deletion
        }

        $invoice->delete();

        return response()->json(['message' => 'Invoice deleted successfully.']);
    }
}
