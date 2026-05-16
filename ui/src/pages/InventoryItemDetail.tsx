import React, { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { inventoryApi, type InventoryItem, type StorageLocation } from '../api/inventory';

export default function InventoryItemDetail() {
  const { id } = useParams<{ id: string }>();
  const [item, setItem] = useState<InventoryItem | null>(null);
  const [locations, setLocations] = useState<StorageLocation[]>([]);
  const [locationId, setLocationId] = useState<number>(0);
  const [movementQty, setMovementQty] = useState(10);
  const [selectedVariationId, setSelectedVariationId] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [reportLocationId, setReportLocationId] = useState<number | undefined>(undefined);
  const [stockReport, setStockReport] = useState<Awaited<ReturnType<typeof inventoryApi.stockReport>> | null>(null);

  const load = async () => {
    if (!id) return;
    const [itemRes, locRes] = await Promise.all([
      inventoryApi.getById(Number(id)),
      inventoryApi.locations(),
    ]);
    setItem(itemRes);
    setLocations(locRes.data);
    if (locRes.data[0]) setLocationId(locRes.data[0].id);
    if (itemRes.variations?.[0]) setSelectedVariationId(itemRes.variations[0].id);
    const report = await inventoryApi.stockReport(Number(id), reportLocationId);
    setStockReport(report);
  };

  useEffect(() => { load().catch((e) => setError(e.message)); }, [id, reportLocationId]);

  const stockIn = async () => {
    if (!item || !locationId) return;
    setError(null);
    try {
      await inventoryApi.stockIn({
        inventory_variation_id: item.has_variations ? selectedVariationId ?? undefined : undefined,
        inventory_item_id: item.has_variations ? undefined : item.id,
        storage_location_id: locationId,
        quantity: movementQty,
      });
      setSuccess('Stock-in recorded');
      await load();
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'Stock-in failed');
    }
  };

  const stockOut = async () => {
    if (!item || !locationId) return;
    setError(null);
    try {
      await inventoryApi.stockOut({
        inventory_variation_id: item.has_variations ? selectedVariationId ?? undefined : undefined,
        inventory_item_id: item.has_variations ? undefined : item.id,
        storage_location_id: locationId,
        quantity: movementQty,
      });
      setSuccess('Stock-out recorded');
      await load();
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'Stock-out failed');
    }
  };

  if (!item) return <div className="p-6">Loading…</div>;

  return (
    <div className="p-6 space-y-6">
      <Link to="/inventory" className="text-indigo-600 hover:underline">← Back to inventory</Link>
      <h1 className="text-2xl font-bold">{item.product_name}</h1>
      <p className="text-gray-600">
        SKU: {item.sku}
        {item.has_variations && (
          <span className="ml-2 text-xs bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded">Variations</span>
        )}
      </p>

      {error && <div className="bg-red-50 text-red-700 p-3 rounded">{error}</div>}
      {success && <div className="bg-green-50 text-green-700 p-3 rounded">{success}</div>}

      {item.has_variations && item.variations && (
        <section className="border rounded-lg overflow-hidden">
          <h2 className="bg-gray-50 px-4 py-2 font-semibold">Variations</h2>
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b">
                <th className="text-left p-2">Label</th>
                <th className="text-left p-2">SKU</th>
                <th className="text-right p-2">Available</th>
              </tr>
            </thead>
            <tbody>
              {item.variations.map((v) => {
                const bal = v.stock_balances?.find((b) => b.storage_location_id === locationId);
                return (
                  <tr
                    key={v.id}
                    className={`border-b cursor-pointer ${selectedVariationId === v.id ? 'bg-indigo-50' : ''}`}
                    onClick={() => setSelectedVariationId(v.id)}
                  >
                    <td className="p-2">{v.label}</td>
                    <td className="p-2">{v.sku}</td>
                    <td className="p-2 text-right">{bal?.quantity_available ?? 0}</td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </section>
      )}

      <section className="border rounded-lg overflow-hidden">
        <div className="bg-gray-50 px-4 py-2 flex flex-wrap items-center justify-between gap-2">
          <h2 className="font-semibold">Stock report</h2>
          <label className="text-sm flex items-center gap-2">
            Location filter
            <select
              className="border rounded px-2 py-1"
              value={reportLocationId ?? ''}
              onChange={(e) => setReportLocationId(e.target.value ? Number(e.target.value) : undefined)}
            >
              <option value="">All locations</option>
              {locations.map((l) => (
                <option key={l.id} value={l.id}>{l.name}</option>
              ))}
            </select>
          </label>
        </div>
        {stockReport && (
          <div className="p-4 space-y-3 text-sm">
            <p>
              <span className="font-medium">Total available:</span>{' '}
              {stockReport.aggregated.quantity_available}
              {' · '}
              <span className="font-medium">Total reserved:</span>{' '}
              {stockReport.aggregated.quantity_reserved}
            </p>
            {stockReport.has_variations && stockReport.variations.length > 0 && (
              <table className="w-full">
                <thead>
                  <tr className="border-b text-left">
                    <th className="p-2">Variation</th>
                    <th className="p-2 text-right">Available</th>
                    <th className="p-2 text-right">Reserved</th>
                  </tr>
                </thead>
                <tbody>
                  {stockReport.variations.map((v) => {
                    const totals = v.by_location.reduce(
                      (acc, loc) => ({
                        available: acc.available + loc.quantity_available,
                        reserved: acc.reserved + loc.quantity_reserved,
                      }),
                      { available: 0, reserved: 0 },
                    );
                    const lowStock = totals.available <= 5;
                    return (
                      <tr key={v.id} className={`border-b ${lowStock ? 'bg-amber-50' : ''}`}>
                        <td className="p-2">
                          {v.label}
                          {lowStock && (
                            <span className="ml-2 text-xs text-amber-700 font-medium">Low stock</span>
                          )}
                        </td>
                        <td className="p-2 text-right">{totals.available}</td>
                        <td className="p-2 text-right">{totals.reserved}</td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            )}
          </div>
        )}
      </section>

      <section className="border rounded-lg p-4 space-y-3 max-w-md">
        <h2 className="font-semibold">Stock movement (inline)</h2>
        <label className="block text-sm">
          Location
          <select
            className="mt-1 w-full border rounded px-2 py-1"
            value={locationId}
            onChange={(e) => setLocationId(Number(e.target.value))}
          >
            {locations.map((l) => (
              <option key={l.id} value={l.id}>{l.name}</option>
            ))}
          </select>
        </label>
        <label className="block text-sm">
          Quantity
          <input
            type="number"
            min={1}
            className="mt-1 w-full border rounded px-2 py-1"
            value={movementQty}
            onChange={(e) => setMovementQty(Number(e.target.value))}
          />
        </label>
        <div className="flex gap-2">
          <button type="button" onClick={stockIn} className="px-3 py-1.5 bg-green-600 text-white rounded">
            Stock in
          </button>
          <button type="button" onClick={stockOut} className="px-3 py-1.5 bg-orange-600 text-white rounded">
            Stock out
          </button>
        </div>
      </section>
    </div>
  );
}
