import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { inventoryApi, type InventoryItem, type StorageLocation } from '../api/inventory';

export default function StockTransfer() {
  const [items, setItems] = useState<InventoryItem[]>([]);
  const [locations, setLocations] = useState<StorageLocation[]>([]);
  const [itemId, setItemId] = useState<number>(0);
  const [variationId, setVariationId] = useState<number | null>(null);
  const [sourceId, setSourceId] = useState(0);
  const [destId, setDestId] = useState(0);
  const [quantity, setQuantity] = useState(1);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    Promise.all([inventoryApi.listAll(), inventoryApi.locations()])
      .then(([itemsRes, locRes]) => {
        setItems(itemsRes);
        setLocations(locRes.data);
        if (itemsRes[0]) setItemId(itemsRes[0].id);
        if (locRes.data[0]) setSourceId(locRes.data[0].id);
        if (locRes.data[1]) setDestId(locRes.data[1].id);
      })
      .catch((e) => setError(e.message));
  }, []);

  const selected = items.find((i) => i.id === itemId);

  useEffect(() => {
    if (selected?.has_variations && selected.variations?.[0]) {
      setVariationId(selected.variations[0].id);
    } else {
      setVariationId(null);
    }
  }, [itemId, items]);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setMessage(null);
    try {
      await inventoryApi.transfer({
        inventory_item_id: selected?.has_variations ? undefined : itemId,
        inventory_variation_id: selected?.has_variations ? variationId ?? undefined : undefined,
        source_location_id: sourceId,
        destination_location_id: destId,
        quantity,
      });
      setMessage('Transfer completed');
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Transfer failed');
    }
  };

  return (
    <div className="p-6 max-w-lg">
      <Link to="/inventory" className="text-indigo-600 hover:underline">← Inventory</Link>
      <h1 className="text-2xl font-bold mt-4 mb-6">Stock transfer</h1>
      {error && <div className="bg-red-50 text-red-700 p-3 rounded mb-4">{error}</div>}
      {message && <div className="bg-green-50 text-green-700 p-3 rounded mb-4">{message}</div>}
      <form onSubmit={submit} className="space-y-4 border rounded-lg p-4">
        <label className="block text-sm font-medium">
          Product
          <select className="mt-1 w-full border rounded px-2 py-1" value={itemId} onChange={(e) => setItemId(Number(e.target.value))}>
            {items.map((i) => (
              <option key={i.id} value={i.id}>{i.product_name}</option>
            ))}
          </select>
        </label>
        {selected?.has_variations && selected.variations && (
          <label className="block text-sm font-medium">
            Variation
            <select className="mt-1 w-full border rounded px-2 py-1" value={variationId ?? ''} onChange={(e) => setVariationId(Number(e.target.value))}>
              {selected.variations.map((v) => (
                <option key={v.id} value={v.id}>{v.label}</option>
              ))}
            </select>
          </label>
        )}
        <label className="block text-sm font-medium">
          From location
          <select className="mt-1 w-full border rounded px-2 py-1" value={sourceId} onChange={(e) => setSourceId(Number(e.target.value))}>
            {locations.map((l) => <option key={l.id} value={l.id}>{l.name}</option>)}
          </select>
        </label>
        <label className="block text-sm font-medium">
          To location
          <select className="mt-1 w-full border rounded px-2 py-1" value={destId} onChange={(e) => setDestId(Number(e.target.value))}>
            {locations.map((l) => <option key={l.id} value={l.id}>{l.name}</option>)}
          </select>
        </label>
        <label className="block text-sm font-medium">
          Quantity
          <input type="number" min={1} className="mt-1 w-full border rounded px-2 py-1" value={quantity} onChange={(e) => setQuantity(Number(e.target.value))} />
        </label>
        <button type="submit" className="w-full bg-indigo-600 text-white py-2 rounded font-medium">Transfer stock</button>
      </form>
    </div>
  );
}
