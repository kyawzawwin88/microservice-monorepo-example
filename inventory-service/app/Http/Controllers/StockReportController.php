<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Services\Stock\GetStockReportAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class StockReportController extends Controller
{
    public function show(string $id, Request $request, GetStockReportAction $action): JsonResponse
    {
        $item = InventoryItem::findOrFail((int) $id);
        $locationId = $request->query('location_id') ? (int) $request->query('location_id') : null;

        return response()->json($action->execute($item, $locationId));
    }
}
