<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class InventoryItemController extends Controller
{
    /**
     * List all inventory items (paginated).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json(
            InventoryItem::orderBy('product_name')->paginate(20)
        );
    }

    /**
     * List all inventory items (no pagination, for dropdowns).
     *
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {
        return response()->json(
            InventoryItem::where('quantity_available', '>', 0)
                ->orderBy('product_name')
                ->get()
        );
    }

    /**
     * Get a single inventory item by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        return response()->json(InventoryItem::findOrFail($id));
    }

    /**
     * Create a new inventory item.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:inventory_items,sku',
            'quantity_available' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
        ]);

        $item = InventoryItem::create([
            'product_name' => $validated['product_name'],
            'sku' => $validated['sku'],
            'quantity_available' => $validated['quantity_available'],
            'quantity_reserved' => 0,
            'unit_price' => $validated['unit_price'],
        ]);

        return response()->json($item, 201);
    }

    /**
     * Update an existing inventory item.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'product_name' => 'sometimes|string|max:255',
            'sku' => 'sometimes|string|max:100|unique:inventory_items,sku,' . $id,
            'quantity_available' => 'sometimes|integer|min:0',
            'unit_price' => 'sometimes|numeric|min:0',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    /**
     * Soft-delete an inventory item.
     * Prevents deletion if item has reserved stock (quantity_reserved > 0).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $item = InventoryItem::findOrFail($id);

        $item->delete();

        return response()->json(['message' => 'Inventory item deleted successfully.']);
    }
}
