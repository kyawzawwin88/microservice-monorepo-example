import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { inventoryApi, type InventoryItem, type InventoryReservation, type CreateInventoryPayload, type UpdateInventoryPayload } from '../api/inventory';
import StatusBadge from '../components/StatusBadge';
import Pagination from '../components/Pagination';
import VariationProductForm from '../components/inventory/VariationProductForm';

type CreateMode = 'simple' | 'variations';

export default function Inventory() {
  const [items, setItems] = useState<InventoryItem[]>([]);
  const [reservations, setReservations] = useState<InventoryReservation[]>([]);
  const [itemPage, setItemPage] = useState(1);
  const [itemLastPage, setItemLastPage] = useState(1);
  const [resPage, setResPage] = useState(1);
  const [resLastPage, setResLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [tab, setTab] = useState<'items' | 'reservations'>('items');
  const [showCreate, setShowCreate] = useState(false);
  const [createMode, setCreateMode] = useState<CreateMode>('simple');
  const [creating, setCreating] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  // Edit state
  const [editingItem, setEditingItem] = useState<InventoryItem | null>(null);
  const [editForm, setEditForm] = useState<UpdateInventoryPayload>({});
  const [updating, setUpdating] = useState(false);
  const [deleting, setDeleting] = useState<number | null>(null);

  const [form, setForm] = useState<CreateInventoryPayload>({
    product_name: '',
    sku: '',
    quantity_available: 0,
    unit_price: 0,
  });

  const fetchItems = async (p = itemPage) => {
    setLoading(true);
    try {
      const res = await inventoryApi.list(p);
      setItems(res.data);
      setItemLastPage(res.last_page);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to load');
    }
    setLoading(false);
  };

  const fetchReservations = async (p = resPage) => {
    setLoading(true);
    try {
      const res = await inventoryApi.reservations(p);
      setReservations(res.data);
      setResLastPage(res.last_page);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to load');
    }
    setLoading(false);
  };

  useEffect(() => { fetchItems(); }, [itemPage]);
  useEffect(() => { if (tab === 'reservations') fetchReservations(); }, [resPage, tab]);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    setCreating(true);
    setError(null);
    try {
      await inventoryApi.create(form);
      setSuccess('Inventory item created successfully');
      setShowCreate(false);
      setForm({ product_name: '', sku: '', quantity_available: 0, unit_price: 0 });
      fetchItems(1);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to create');
    }
    setCreating(false);
  };

  const startEdit = (item: InventoryItem) => {
    if (item.has_variations) {
      setError('Variation products are managed on the product detail page. Click the product name to open it.');
      return;
    }
    setEditingItem(item);
    setEditForm({
      product_name: item.product_name,
      sku: item.sku,
      quantity_available: item.quantity_available,
      unit_price: parseFloat(item.unit_price) || 0,
    });
    setShowCreate(false);
    setError(null);
    setSuccess(null);
  };

  const cancelEdit = () => {
    setEditingItem(null);
    setEditForm({});
  };

  const handleUpdate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingItem) return;
    setUpdating(true);
    setError(null);
    try {
      await inventoryApi.update(editingItem.id, editForm);
      setSuccess(`"${editForm.product_name}" updated successfully`);
      setEditingItem(null);
      setEditForm({});
      fetchItems();
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to update');
    }
    setUpdating(false);
  };

  const handleDelete = async (item: InventoryItem) => {
    if (!confirm(`Delete "${item.product_name}" (SKU: ${item.sku})?`)) return;
    setDeleting(item.id);
    setError(null);
    setSuccess(null);
    try {
      const res = await inventoryApi.delete(item.id);
      setSuccess(res.message);
      if (editingItem?.id === item.id) cancelEdit();
      fetchItems();
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to delete');
    }
    setDeleting(null);
  };

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Inventory</h1>
          <p className="mt-1 text-sm text-gray-500">Manage products and view stock reservations</p>
        </div>
        <div className="flex gap-3">
          <Link to="/inventory/locations" className="rounded-lg border border-indigo-300 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50">
            Manage locations
          </Link>
          <Link to="/inventory/transfer" className="rounded-lg border border-indigo-300 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50">
            Transfer stock
          </Link>
          <button onClick={() => { fetchItems(); fetchReservations(); }} className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            🔄 Refresh
          </button>
          <button
            onClick={() => {
              setShowCreate(!showCreate);
              cancelEdit();
              if (!showCreate) setCreateMode('simple');
            }}
            className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
          >
            + Add Product
          </button>
          <button
            onClick={() => {
              setShowCreate(true);
              setCreateMode('variations');
              cancelEdit();
            }}
            className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-violet-500"
          >
            + Product with Variations
          </button>
        </div>
      </div>

      {success && (
        <div className="mb-4 rounded-lg bg-green-50 border border-green-200 p-4">
          <p className="text-sm text-green-700">✅ {success}</p>
        </div>
      )}
      {error && (
        <div className="mb-4 rounded-lg bg-red-50 border border-red-200 p-4">
          <p className="text-sm text-red-700">❌ {error}</p>
        </div>
      )}

      {/* Create Inventory Form */}
      {showCreate && (
        <div className="bg-white shadow rounded-lg p-6 mb-6">
          <div className="flex gap-4 mb-4 border-b border-gray-200">
            <button
              type="button"
              onClick={() => setCreateMode('simple')}
              className={`pb-2 text-sm font-medium border-b-2 -mb-px ${
                createMode === 'simple'
                  ? 'border-indigo-600 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700'
              }`}
            >
              Simple product
            </button>
            <button
              type="button"
              onClick={() => setCreateMode('variations')}
              className={`pb-2 text-sm font-medium border-b-2 -mb-px ${
                createMode === 'variations'
                  ? 'border-violet-600 text-violet-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700'
              }`}
            >
              Product with variations
            </button>
          </div>

          {createMode === 'variations' ? (
            <>
              <h2 className="text-lg font-semibold mb-4">Add product with variations</h2>
              <VariationProductForm
                onCancel={() => setShowCreate(false)}
                onSuccess={(msg) => {
                  setSuccess(msg);
                  setShowCreate(false);
                  fetchItems(1);
                }}
                onError={(msg) => setError(msg)}
              />
            </>
          ) : (
            <>
          <h2 className="text-lg font-semibold mb-4">Add simple inventory product</h2>
          <form onSubmit={handleCreate} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700">Product Name *</label>
                <input
                  type="text"
                  required
                  value={form.product_name}
                  onChange={(e) => setForm((f) => ({ ...f, product_name: e.target.value }))}
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm border px-3 py-2"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">SKU *</label>
                <input
                  type="text"
                  required
                  value={form.sku}
                  onChange={(e) => setForm((f) => ({ ...f, sku: e.target.value }))}
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm border px-3 py-2"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">Quantity Available *</label>
                <input
                  type="number"
                  required
                  min={0}
                  value={form.quantity_available}
                  onChange={(e) => setForm((f) => ({ ...f, quantity_available: parseInt(e.target.value) || 0 }))}
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm border px-3 py-2"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">Unit Price ($) *</label>
                <input
                  type="number"
                  required
                  min={0}
                  step={0.01}
                  value={form.unit_price || ''}
                  onChange={(e) => setForm((f) => ({ ...f, unit_price: parseFloat(e.target.value) || 0 }))}
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm border px-3 py-2"
                />
              </div>
            </div>
            <div className="flex gap-3">
              <button type="submit" disabled={creating} className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                {creating ? 'Creating...' : 'Add Product'}
              </button>
              <button type="button" onClick={() => setShowCreate(false)} className="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                Cancel
              </button>
            </div>
          </form>
            </>
          )}
        </div>
      )}

      {/* Edit Inventory Form */}
      {editingItem && (
        <div className="bg-white shadow rounded-lg p-6 mb-6 border-l-4 border-indigo-500">
          <h2 className="text-lg font-semibold mb-4">Edit Product — #{editingItem.id}</h2>
          <form onSubmit={handleUpdate} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700">Product Name</label>
                <input
                  type="text"
                  required
                  value={editForm.product_name ?? ''}
                  onChange={(e) => setEditForm((f) => ({ ...f, product_name: e.target.value }))}
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm border px-3 py-2"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">SKU</label>
                <input
                  type="text"
                  required
                  value={editForm.sku ?? ''}
                  onChange={(e) => setEditForm((f) => ({ ...f, sku: e.target.value }))}
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm border px-3 py-2"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">Quantity Available</label>
                <input
                  type="number"
                  required
                  min={0}
                  value={editForm.quantity_available ?? 0}
                  onChange={(e) => setEditForm((f) => ({ ...f, quantity_available: parseInt(e.target.value) || 0 }))}
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm border px-3 py-2"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">Unit Price ($)</label>
                <input
                  type="number"
                  required
                  min={0}
                  step={0.01}
                  value={editForm.unit_price ?? ''}
                  onChange={(e) => setEditForm((f) => ({ ...f, unit_price: parseFloat(e.target.value) || 0 }))}
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm border px-3 py-2"
                />
              </div>
            </div>
            <div className="flex gap-3">
              <button type="submit" disabled={updating} className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                {updating ? 'Saving...' : 'Save Changes'}
              </button>
              <button type="button" onClick={cancelEdit} className="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                Cancel
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Tabs */}
      <div className="border-b border-gray-200 mb-4">
        <nav className="flex space-x-8">
          <button
            onClick={() => setTab('items')}
            className={`py-3 px-1 border-b-2 text-sm font-medium ${tab === 'items' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'}`}
          >
            📦 Products
          </button>
          <button
            onClick={() => setTab('reservations')}
            className={`py-3 px-1 border-b-2 text-sm font-medium ${tab === 'reservations' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'}`}
          >
            🔒 Reservations
          </button>
        </nav>
      </div>

      {/* Items Table */}
      {tab === 'items' && (
        <div className="bg-white shadow rounded-lg overflow-hidden">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Available</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reserved</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Updated</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {loading ? (
                <tr><td colSpan={9} className="px-6 py-10 text-center text-sm text-gray-400">Loading...</td></tr>
              ) : items.length === 0 ? (
                <tr><td colSpan={9} className="px-6 py-10 text-center text-sm text-gray-400">No inventory items. Add one above!</td></tr>
              ) : (
                items.map((item) => (
                  <tr key={item.id} className={`hover:bg-gray-50 ${editingItem?.id === item.id ? 'bg-indigo-50' : ''}`}>
                    <td className="px-6 py-4 text-sm font-medium text-gray-900">#{item.id}</td>
                    <td className="px-6 py-4 text-sm text-gray-700">
                      <Link to={`/inventory/${item.id}`} className="text-indigo-600 hover:underline">{item.product_name}</Link>
                    </td>
                    <td className="px-6 py-4 text-sm">
                      {item.has_variations ? (
                        <span className="inline-flex items-center rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-medium text-violet-800">
                          {item.variations_count ?? 0} variations
                        </span>
                      ) : (
                        <span className="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                          Single stock
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4 text-sm font-mono text-gray-500">{item.sku}</td>
                    <td className="px-6 py-4 text-sm text-gray-700 font-medium">${item.unit_price}</td>
                    <td className="px-6 py-4 text-sm">
                      {item.has_variations ? (
                        <Link to={`/inventory/${item.id}`} className="text-indigo-600 hover:underline text-xs">
                          View per variation →
                        </Link>
                      ) : (
                        <span className={item.quantity_available <= 0 ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'}>
                          {item.quantity_available}
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4 text-sm text-yellow-600">{item.quantity_reserved}</td>
                    <td className="px-6 py-4 text-xs text-gray-400">{new Date(item.updated_at).toLocaleString()}</td>
                    <td className="px-6 py-4 text-right space-x-2">
                      <button
                        onClick={() => startEdit(item)}
                        className="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                      >
                        Edit
                      </button>
                      <button
                        onClick={() => handleDelete(item)}
                        disabled={deleting === item.id}
                        className="text-red-600 hover:text-red-900 text-sm font-medium disabled:opacity-50"
                      >
                        {deleting === item.id ? 'Deleting…' : 'Delete'}
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
          <Pagination currentPage={itemPage} lastPage={itemLastPage} onPageChange={setItemPage} />
        </div>
      )}

      {/* Reservations Table */}
      {tab === 'reservations' && (
        <div className="bg-white shadow rounded-lg overflow-hidden">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product Name</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reserved</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">State</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Correlation ID</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {loading ? (
                <tr><td colSpan={8} className="px-6 py-10 text-center text-sm text-gray-400">Loading...</td></tr>
              ) : reservations.length === 0 ? (
                <tr><td colSpan={8} className="px-6 py-10 text-center text-sm text-gray-400">No reservations yet</td></tr>
              ) : (
                reservations.map((res) => (
                  <tr key={res.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 text-sm font-medium text-gray-900">#{res.id}</td>
                    <td className="px-6 py-4 text-sm text-gray-700">#{res.order_id}</td>
                    <td className="px-6 py-4 text-sm text-gray-700 font-medium">{res.product_name}</td>
                    <td className="px-6 py-4 text-sm text-gray-700">{res.quantity}</td>
                    <td className="px-6 py-4 text-sm text-yellow-600 font-semibold">{res.reserved_quantity}</td>
                    <td className="px-6 py-4">
                      <StatusBadge status={res.state} />
                      {res.state === 'failed' && res.state_failure_description && (
                        <p className="text-xs text-red-500 mt-1">{res.state_failure_description}</p>
                      )}
                    </td>
                    <td className="px-6 py-4 text-xs font-mono text-gray-500">{res.correlation_id?.slice(0, 8)}…</td>
                    <td className="px-6 py-4 text-xs text-gray-400">{new Date(res.created_at).toLocaleString()}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
          <Pagination currentPage={resPage} lastPage={resLastPage} onPageChange={setResPage} />
        </div>
      )}
    </div>
  );
}
