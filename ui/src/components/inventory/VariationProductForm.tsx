import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  inventoryApi,
  type CreateVariationInventoryPayload,
  type StorageLocation,
} from '../../api/inventory';

type DimensionRow = { name: string; values: string };

const defaultDimensions = (): DimensionRow[] => [
  { name: 'Size', values: 'S, M, L' },
  { name: 'Color', values: 'Red, Blue' },
];

interface Props {
  onCancel: () => void;
  onSuccess: (message: string) => void;
  onError: (message: string) => void;
}

export default function VariationProductForm({ onCancel, onSuccess, onError }: Props) {
  const navigate = useNavigate();
  const [locations, setLocations] = useState<StorageLocation[]>([]);
  const [submitting, setSubmitting] = useState(false);
  const [productName, setProductName] = useState('');
  const [sku, setSku] = useState('');
  const [unitPrice, setUnitPrice] = useState(0);
  const [locationId, setLocationId] = useState(0);
  const [initialQty, setInitialQty] = useState(0);
  const [dimensions, setDimensions] = useState<DimensionRow[]>(defaultDimensions);

  useEffect(() => {
    inventoryApi
      .locations()
      .then((res) => {
        setLocations(res.data);
        if (res.data[0]) setLocationId(res.data[0].id);
      })
      .catch((e) => onError(e.message));
  }, [onError]);

  const updateDimension = (index: number, field: keyof DimensionRow, value: string) => {
    setDimensions((rows) => rows.map((r, i) => (i === index ? { ...r, [field]: value } : r)));
  };

  const addDimension = () => setDimensions((rows) => [...rows, { name: '', values: '' }]);

  const removeDimension = (index: number) => {
    setDimensions((rows) => (rows.length <= 1 ? rows : rows.filter((_, i) => i !== index)));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!locationId) {
      onError('No storage location available. Run migrations and seed locations first.');
      return;
    }

    const parsedDimensions = dimensions
      .map((d) => ({
        name: d.name.trim(),
        values: d.values
          .split(',')
          .map((v) => v.trim())
          .filter(Boolean),
      }))
      .filter((d) => d.name && d.values.length > 0);

    if (parsedDimensions.length === 0) {
      onError('Add at least one dimension with comma-separated values (e.g. S, M, L).');
      return;
    }

    const payload: CreateVariationInventoryPayload = {
      product_name: productName,
      sku,
      unit_price: unitPrice,
      has_variations: true,
      dimensions: parsedDimensions,
      initial_location_id: locationId,
      initial_quantity_per_variation: initialQty,
    };

    setSubmitting(true);
    try {
      const res = await inventoryApi.createWithVariations(payload);
      const count = res.variations?.length ?? 0;
      onSuccess(`Created "${productName}" with ${count} variations`);
      navigate(`/inventory/${res.item.id}`);
    } catch (err: unknown) {
      onError(err instanceof Error ? err.message : 'Failed to create variation product');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <p className="text-sm text-gray-600">
        Each combination of dimension values becomes its own variation (e.g. Size × Color).
      </p>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label className="block text-sm font-medium text-gray-700">Product name *</label>
          <input
            type="text"
            required
            value={productName}
            onChange={(e) => setProductName(e.target.value)}
            placeholder="T-Shirt"
            className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700">Base SKU *</label>
          <input
            type="text"
            required
            value={sku}
            onChange={(e) => setSku(e.target.value)}
            placeholder="TSHIRT"
            className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700">Unit price ($) *</label>
          <input
            type="number"
            required
            min={0}
            step="0.01"
            value={unitPrice}
            onChange={(e) => setUnitPrice(parseFloat(e.target.value) || 0)}
            className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700">Initial location *</label>
          <select
            required
            value={locationId}
            onChange={(e) => setLocationId(Number(e.target.value))}
            className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
          >
            {locations.map((l) => (
              <option key={l.id} value={l.id}>
                {l.name} ({l.code})
              </option>
            ))}
          </select>
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700">Starting qty per variation</label>
          <input
            type="number"
            min={0}
            value={initialQty}
            onChange={(e) => setInitialQty(parseInt(e.target.value, 10) || 0)}
            className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
          />
        </div>
      </div>

      <div className="border rounded-lg p-4 space-y-3 bg-gray-50">
        <div className="flex items-center justify-between">
          <h3 className="text-sm font-semibold text-gray-800">Variation dimensions</h3>
          <button
            type="button"
            onClick={addDimension}
            className="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
          >
            + Add dimension
          </button>
        </div>
        {dimensions.map((dim, index) => (
          <div key={index} className="grid grid-cols-1 md:grid-cols-12 gap-2 items-end">
            <div className="md:col-span-3">
              <label className="block text-xs text-gray-500">Dimension name</label>
              <input
                type="text"
                required
                value={dim.name}
                onChange={(e) => updateDimension(index, 'name', e.target.value)}
                placeholder="Size"
                className="mt-1 w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm"
              />
            </div>
            <div className="md:col-span-8">
              <label className="block text-xs text-gray-500">Values (comma-separated)</label>
              <input
                type="text"
                required
                value={dim.values}
                onChange={(e) => updateDimension(index, 'values', e.target.value)}
                placeholder="S, M, L"
                className="mt-1 w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm"
              />
            </div>
            <div className="md:col-span-1">
              <button
                type="button"
                onClick={() => removeDimension(index)}
                className="text-sm text-red-600 hover:text-red-800 py-1.5"
                title="Remove dimension"
              >
                ✕
              </button>
            </div>
          </div>
        ))}
      </div>

      <div className="flex gap-3">
        <button
          type="submit"
          disabled={submitting}
          className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
        >
          {submitting ? 'Creating…' : 'Create with variations'}
        </button>
        <button
          type="button"
          onClick={onCancel}
          className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
          Cancel
        </button>
      </div>
    </form>
  );
}
