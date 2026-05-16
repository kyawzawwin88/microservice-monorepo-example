<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\StockBalance;
use App\Models\VariationDimension;
use App\Services\Stock\ResolveStockBalanceAction;
use App\Services\Stock\SyncLegacyItemStockFromBalanceAction;
use App\Services\Variations\AppendDimensionValueAction;
use App\Services\Variations\GenerateVariationsAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class InventoryItemController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            InventoryItem::withCount('variations')
                ->orderBy('product_name')
                ->paginate(20)
        );
    }

    public function all(): JsonResponse
    {
        return response()->json(
            InventoryItem::with(['variations:id,inventory_item_id,label,sku,attribute_hash'])
                ->orderBy('product_name')
                ->get()
        );
    }

    public function show(string $id): JsonResponse
    {
        $item = InventoryItem::with([
            'variationDimensions.values',
            'variations.stockBalances.storageLocation',
            'stockBalances.storageLocation',
        ])->findOrFail((int) $id);

        return response()->json($item);
    }

    public function store(
        Request $request,
        GenerateVariationsAction $generateVariations,
        ResolveStockBalanceAction $resolveBalance,
        SyncLegacyItemStockFromBalanceAction $syncLegacy,
    ): JsonResponse {
        if ($request->boolean('has_variations')) {
            return $this->storeWithVariations($request, $generateVariations);
        }

        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:inventory_items,sku',
            'quantity_available' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'storage_location_id' => 'nullable|integer|exists:storage_locations,id',
        ]);

        return DB::transaction(function () use ($validated, $resolveBalance, $syncLegacy) {
            $item = InventoryItem::create([
                'product_name' => $validated['product_name'],
                'sku' => $validated['sku'],
                'has_variations' => false,
                'quantity_available' => $validated['quantity_available'],
                'quantity_reserved' => 0,
                'unit_price' => $validated['unit_price'],
            ]);

            $locationId = $resolveBalance->resolveLocationId($validated['storage_location_id'] ?? null);
            $balance = StockBalance::create([
                'balanceable_type' => InventoryItem::class,
                'balanceable_id' => $item->id,
                'storage_location_id' => $locationId,
                'quantity_available' => $validated['quantity_available'],
                'quantity_reserved' => 0,
            ]);
            $syncLegacy->execute($item, $balance);

            return response()->json($item->fresh(), 201);
        });
    }

    public function addDimensionValue(
        int $id,
        int $dimensionId,
        Request $request,
        AppendDimensionValueAction $action,
        ResolveStockBalanceAction $resolveBalance,
    ): JsonResponse {
        $item = InventoryItem::findOrFail($id);
        if (! $item->has_variations) {
            return response()->json(['message' => 'Item does not support variations'], 422);
        }

        $dimension = VariationDimension::where('inventory_item_id', $id)->findOrFail($dimensionId);
        $validated = $request->validate([
            'value' => 'required|string|max:100',
            'initial_quantity' => 'nullable|integer|min:0',
        ]);

        $locationId = $resolveBalance->resolveLocationId($request->input('storage_location_id'));
        $created = $action->execute(
            $dimension,
            $validated['value'],
            $locationId,
            $validated['initial_quantity'] ?? 0,
        );

        return response()->json(['variations' => $created], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'product_name' => 'sometimes|string|max:255',
            'sku' => 'sometimes|string|max:100|unique:inventory_items,sku,'.$id,
            'quantity_available' => 'sometimes|integer|min:0',
            'unit_price' => 'sometimes|numeric|min:0',
        ]);

        if ($item->has_variations && isset($validated['quantity_available'])) {
            unset($validated['quantity_available']);
        }

        $item->update($validated);

        return response()->json($item);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = InventoryItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Inventory item deleted successfully.']);
    }

    private function storeWithVariations(
        Request $request,
        GenerateVariationsAction $generateVariations,
    ): JsonResponse {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:inventory_items,sku',
            'unit_price' => 'required|numeric|min:0',
            'dimensions' => 'required|array|min:1',
            'dimensions.*.name' => 'required|string|max:100',
            'dimensions.*.values' => 'required|array|min:1',
            'dimensions.*.values.*' => 'required|string|max:100',
            'initial_location_id' => 'required|integer|exists:storage_locations,id',
            'initial_quantity_per_variation' => 'nullable|integer|min:0',
        ]);

        return DB::transaction(function () use ($validated, $generateVariations) {
            $item = InventoryItem::create([
                'product_name' => $validated['product_name'],
                'sku' => $validated['sku'],
                'has_variations' => true,
                'quantity_available' => 0,
                'quantity_reserved' => 0,
                'unit_price' => $validated['unit_price'],
            ]);

            $variations = $generateVariations->execute(
                $item,
                $validated['dimensions'],
                $validated['initial_location_id'],
                $validated['initial_quantity_per_variation'] ?? 0,
            );

            $item->load(['variationDimensions.values', 'variations.stockBalances']);

            return response()->json([
                'item' => $item,
                'variations' => $variations,
            ], 201);
        });
    }
}
