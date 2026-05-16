<?php

namespace App\Http\Controllers;

use App\Events\OrderCreated;
use App\Jobs\EventBusMessage;
use App\Models\Order;
use App\States\CompletedState;
use App\States\RequestedState;
use App\Workflows\DeleteOrderWorkflow;
use App\Workflows\DeliverOrderWorkflow;
use App\Workflows\NewOrderWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Workflow\WorkflowStub;

class OrderController extends Controller
{
    /**
     * List all orders (paginated).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json(
            Order::orderBy('created_at', 'desc')->paginate(20)
        );
    }

    /**
     * Create a new order — triggers NewOrderWorkflow.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'total_amount' => 'required|numeric|min:0.01',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'simulate_failure' => 'nullable|boolean',
        ]);

        $correlationId = (string) Str::uuid();

        $workflow = WorkflowStub::make(NewOrderWorkflow::class);
        $workflow->start($correlationId, $validated);

        $message = !empty($validated['simulate_failure'])
            ? 'Order workflow initiated (failure simulation enabled — invoice or payment will randomly fail)'
            : 'Order workflow initiated';

        return response()->json([
            'message' => $message,
            'correlation_id' => $correlationId,
            'workflow_id' => $workflow->id(),
        ], 202);
    }

    /**
     * Get order by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        return response()->json(Order::findOrFail($id));
    }

    /**
     * Get order by correlation ID.
     *
     * @param string $correlationId
     * @return JsonResponse
     */
    public function showByCorrelation(string $correlationId): JsonResponse
    {
        $order = Order::where('correlation_id', $correlationId)->firstOrFail();
        return response()->json($order);
    }

    /**
     * Mark a paid order as delivered — triggers DeliverOrderWorkflow.
     *
     * Guard: only orders with business status 'paid' can be delivered.
     * The workflow transitions the business status to 'delivered' and publishes
     * an OrderDelivered event to the Inventory service so reserved stock is deducted.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deliver(int $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        // Guard: only orders with business status 'paid' can be delivered
        if ($order->status !== Order::STATUS_PAID) {
            return response()->json([
                'message' => "Cannot deliver order — current status is '{$order->status}'. Only 'paid' orders can be delivered.",
            ], 422);
        }

        $workflow = WorkflowStub::make(DeliverOrderWorkflow::class);
        $workflow->start($order->correlation_id, $order->id);

        return response()->json([
            'message' => 'Deliver order workflow initiated. Reserved inventory will be deducted.',
            'correlation_id' => $order->correlation_id,
            'workflow_id' => $workflow->id(),
        ], 202);
    }

    /**
     * Retry a failed order — re-dispatches the OrderCreated event.
     *
     * This endpoint demonstrates eventual consistency recovery:
     * 1. Resets order state from 'failed' → 'requested'
     * 2. Clears failure description
     * 3. Re-dispatches OrderCreated event WITHOUT simulate_failure
     * 4. Downstream services handle idempotency (skip existing records)
     * 5. Marks order state as 'completed' (local processing done)
     *
     * @param int $id
     * @return JsonResponse
     */
    public function retry(int $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        // Guard: only 'failed' orders can be retried
        if ($order->state::$name !== 'failed') {
            return response()->json([
                'message' => "Cannot retry order — current state is '{$order->state::$name}'. Only 'failed' orders can be retried.",
            ], 422);
        }

        // Step 1: Reset order state to 'requested' and clear failure
        $order->state->transitionTo(RequestedState::class);
        $order->update(['state_failure_description' => null]);

        // Step 2: Re-dispatch OrderCreated event WITHOUT simulate_failure
        $eventData = [
            'correlationId' => $order->correlation_id,
            'orderId' => $order->id,
            'customerName' => $order->customer_name,
            'customerEmail' => $order->customer_email ?? '',
            'totalAmount' => (float) $order->total_amount,
            'items' => $order->items,
            'simulateFailure' => false, // No failure on retry
        ];

        EventBusMessage::dispatch(OrderCreated::class, $eventData)
            ->onConnection('eventbus')
            ->onQueue('invoice');

        // Step 3: Mark local processing as completed
        $order->refresh();
        $order->state->transitionTo(CompletedState::class);

        return response()->json([
            'message' => 'Order retry initiated. The workflow will resume — downstream services handle idempotency.',
            'correlation_id' => $order->correlation_id,
        ], 202);
    }

    /**
     * Delete an order — triggers DeleteOrderWorkflow.
     *
     * The workflow soft-deletes the order and publishes an OrderDeleted event
     * to the Inventory service so that reserved stock is released.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        // Guard: prevent deletion if a related invoice exists
        try {
            $invoiceServiceUrl = env('INVOICE_SERVICE_URL', 'http://invoice-service:8000');
            $response = @file_get_contents("{$invoiceServiceUrl}/api/invoices/correlation/{$order->correlation_id}");
            if ($response !== false) {
                $invoice = json_decode($response, true);
                if ($invoice && !empty($invoice['id'])) {
                    return response()->json([
                        'message' => 'Cannot delete order — a related invoice exists (Invoice #' . ($invoice['invoice_number'] ?? $invoice['id']) . ').',
                    ], 422);
                }
            }
        } catch (\Throwable $e) {
            // If invoice service is unreachable, allow deletion
        }

        $workflow = WorkflowStub::make(DeleteOrderWorkflow::class);
        $workflow->start($order->correlation_id, $order->id, $order->customer_name);

        return response()->json([
            'message' => 'Order deletion workflow initiated. Reserved inventory will be released.',
            'correlation_id' => $order->correlation_id,
            'workflow_id' => $workflow->id(),
        ], 202);
    }
}
