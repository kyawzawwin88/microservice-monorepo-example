<?php

namespace App\Http\Controllers;

use App\Models\InventoryReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class InventoryReservationController extends Controller
{
    /**
     * List all inventory reservations (paginated).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json(
            InventoryReservation::orderBy('created_at', 'desc')->paginate(20)
        );
    }

    /**
     * Get reservation by correlation ID.
     *
     * @param string $correlationId
     * @return JsonResponse
     */
    public function showByCorrelation(string $correlationId): JsonResponse
    {
        $reservation = InventoryReservation::where('correlation_id', $correlationId)->firstOrFail();
        return response()->json($reservation);
    }
}
