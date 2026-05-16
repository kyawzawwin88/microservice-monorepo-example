<?php

namespace App\Services\Stock;

use App\Models\InventoryItem;
use App\Models\InventoryVariation;
use App\Models\StockBalance;

class GetStockReportAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(InventoryItem $item, ?int $locationId = null): array
    {
        if (! $item->has_variations) {
            return $this->legacyReport($item, $locationId);
        }

        $item->load(['variations.stockBalances.storageLocation']);

        $variations = [];
        $totalAvailable = 0;
        $totalReserved = 0;

        foreach ($item->variations as $variation) {
            $byLocation = [];
            foreach ($variation->stockBalances as $balance) {
                if ($locationId && $balance->storage_location_id !== $locationId) {
                    continue;
                }
                $byLocation[] = [
                    'location_id' => $balance->storage_location_id,
                    'quantity_available' => $balance->quantity_available,
                    'quantity_reserved' => $balance->quantity_reserved,
                ];
                $totalAvailable += $balance->quantity_available;
                $totalReserved += $balance->quantity_reserved;
            }

            $variations[] = [
                'id' => $variation->id,
                'label' => $variation->label,
                'sku' => $variation->sku,
                'by_location' => $byLocation,
            ];
        }

        return [
            'item_id' => $item->id,
            'has_variations' => true,
            'aggregated' => [
                'quantity_available' => $totalAvailable,
                'quantity_reserved' => $totalReserved,
            ],
            'variations' => $variations,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyReport(InventoryItem $item, ?int $locationId): array
    {
        $query = StockBalance::query()
            ->where('balanceable_type', InventoryItem::class)
            ->where('balanceable_id', $item->id);

        if ($locationId) {
            $query->where('storage_location_id', $locationId);
        }

        $balances = $query->get();

        return [
            'item_id' => $item->id,
            'has_variations' => false,
            'aggregated' => [
                'quantity_available' => $balances->sum('quantity_available'),
                'quantity_reserved' => $balances->sum('quantity_reserved'),
            ],
            'variations' => [],
        ];
    }
}
