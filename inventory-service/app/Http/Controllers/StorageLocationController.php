<?php

namespace App\Http\Controllers;

use App\Models\StorageLocation;
use App\Services\Locations\DeactivateStorageLocationAction;
use App\Services\Locations\UpdateStorageLocationAction;
use App\Services\Locations\ValidateLocationAddressAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;

class StorageLocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = StorageLocation::query()->orderBy('name');

        if (! $request->boolean('all')) {
            $query->where('is_active', true);
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request, ValidateLocationAddressAction $addressValidator): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:storage_locations,code',
            'address_street' => 'nullable|string',
            'country_code' => 'nullable|string|max:2',
            'postal_code' => 'nullable|string|max:20',
        ]);

        try {
            $address = $addressValidator->execute(
                $validated['address_street'] ?? null,
                $validated['country_code'] ?? null,
                $validated['postal_code'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $location = StorageLocation::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'address_street' => $address['address_street'],
            'country_code' => $address['country_code'],
            'postal_code' => $address['postal_code'],
            'is_active' => true,
        ]);

        return response()->json($location, 201);
    }

    public function update(
        Request $request,
        int $id,
        UpdateStorageLocationAction $updateAction,
        ValidateLocationAddressAction $addressValidator
    ): JsonResponse {
        $location = StorageLocation::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:storage_locations,code,'.$location->id,
            'address_street' => 'nullable|string',
            'country_code' => 'nullable|string|max:2',
            'postal_code' => 'nullable|string|max:20',
        ]);

        try {
            $address = $addressValidator->execute(
                $validated['address_street'] ?? null,
                $validated['country_code'] ?? null,
                $validated['postal_code'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $location = $updateAction->execute($location, [
            'name' => $validated['name'],
            'code' => $validated['code'],
            'address_street' => $address['address_street'],
            'country_code' => $address['country_code'],
            'postal_code' => $address['postal_code'],
        ]);

        return response()->json($location);
    }

    public function deactivate(int $id, DeactivateStorageLocationAction $deactivateAction): JsonResponse
    {
        $location = StorageLocation::findOrFail($id);

        try {
            $location = $deactivateAction->execute($location);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($location);
    }
}
