<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Services\Stock\RecordStockInAction;
use App\Services\Stock\RecordStockOutAction;
use App\Services\Stock\TransferStockAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;

class StockMovementController extends Controller
{
    public function stockIn(Request $request, RecordStockInAction $action): JsonResponse
    {
        $validated = $this->validateMovement($request);

        try {
            $result = $action->execute(
                $validated['inventory_variation_id'] ?? null,
                $validated['inventory_item_id'] ?? null,
                $validated['storage_location_id'],
                $validated['quantity'],
                $validated['reference'] ?? null,
            );
        } catch (InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 'insufficient_stock'], 422);
        }

        return response()->json([
            'movement_id' => $result['movement']->id,
            'balance' => $result['balance'],
        ], 201);
    }

    public function stockOut(Request $request, RecordStockOutAction $action): JsonResponse
    {
        $validated = $this->validateMovement($request);

        try {
            $result = $action->execute(
                $validated['inventory_variation_id'] ?? null,
                $validated['inventory_item_id'] ?? null,
                $validated['storage_location_id'],
                $validated['quantity'],
                $validated['reference'] ?? null,
            );
        } catch (InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 'insufficient_stock'], 422);
        }

        return response()->json([
            'movement_id' => $result['movement']->id,
            'balance' => $result['balance'],
        ], 201);
    }

    public function transfer(Request $request, TransferStockAction $action): JsonResponse
    {
        $validated = $request->validate([
            'inventory_variation_id' => 'nullable|integer|exists:inventory_variations,id',
            'inventory_item_id' => 'nullable|integer|exists:inventory_items,id',
            'source_location_id' => 'required|integer|exists:storage_locations,id',
            'destination_location_id' => 'required|integer|exists:storage_locations,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if (empty($validated['inventory_variation_id']) && empty($validated['inventory_item_id'])) {
            return response()->json(['message' => 'inventory_variation_id or inventory_item_id required'], 422);
        }

        try {
            $result = $action->execute(
                $validated['inventory_variation_id'] ?? null,
                $validated['inventory_item_id'] ?? null,
                $validated['source_location_id'],
                $validated['destination_location_id'],
                $validated['quantity'],
            );
        } catch (InsufficientStockException|InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 'transfer_rejected'], 422);
        }

        return response()->json([
            'movement_id' => $result['movement']->id,
            'source_balance' => $result['source_balance'],
            'destination_balance' => $result['destination_balance'],
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateMovement(Request $request): array
    {
        $validated = $request->validate([
            'inventory_variation_id' => 'nullable|integer|exists:inventory_variations,id',
            'inventory_item_id' => 'nullable|integer|exists:inventory_items,id',
            'storage_location_id' => 'required|integer|exists:storage_locations,id',
            'quantity' => 'required|integer|min:1',
            'reference' => 'nullable|string|max:255',
        ]);

        if (empty($validated['inventory_variation_id']) && empty($validated['inventory_item_id'])) {
            abort(422, 'inventory_variation_id or inventory_item_id required');
        }

        return $validated;
    }
}
