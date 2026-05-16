import React, { useCallback, useEffect, useRef, useState } from 'react';
import { salesApi, type Order, type CreateOrderPayload, type OrderWorkflowResponse } from '../api/sales';
import { inventoryApi, type InventoryItem } from '../api/inventory';
import StatusBadge from '../components/StatusBadge';
import Pagination from '../components/Pagination';

interface OrderItemRow {
  inventory_item_id: number | '';
  product_name: string;
  quantity: number;
  unit_price: number;
}

export default function Orders() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [showCreate, setShowCreate] = useState(false);
  const [creating, setCreating] = useState(false);
  const [result, setResult] = useState<OrderWorkflowResponse | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [deleting, setDeleting] = useState<number | null>(null);
  const [delivering, setDelivering] = useState<number | null>(null);
  const [retrying, setRetrying] = useState<number | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  // Inventory items for selection
  const [inventoryItems, setInventoryItems] = useState<InventoryItem[]>([]);
  const [loadingInventory, setLoadingInventory] = useState(false);

  const [customerName, setCustomerName] = useState('');
  const [customerEmail, setCustomerEmail] = useState('');
  const [items, setItems] = useState<OrderItemRow[]>([
    { inventory_item_id: '', product_name: '', quantity: 1, unit_price: 0 },
  ]);

  // ── Auto-polling after actions (create, retry, deliver, delete) ──
  const pollTimerRef = useRef<ReturnType<typeof setInterval> | null>(null);
  const pollCountRef = useRef(0);

  const stopPolling = useCallback(() => {
    if (pollTimerRef.current) {
      clearInterval(pollTimerRef.current);
      pollTimerRef.current = null;
    }
    pollCountRef.current = 0;
  }, []);

  const startPolling = useCallback((targetPage = 1) => {
    stopPolling();
    setPage(targetPage);
    pollCountRef.current = 0;
    pollTimerRef.current = setInterval(() => {
      pollCountRef.current += 1;
      // Poll for ~30 seconds (10 × 3s), then stop
      if (pollCountRef.current >= 10) {
        stopPolling();
        return;
      }
      fetchOrdersSilent(targetPage);
    }, 3000);
  }, [stopPolling]);

  // Cleanup on unmount
  useEffect(() => () => stopPolling(), [stopPolling]);

  const fetchOrders = async (p = page) => {
    setLoading(true);
    try {
      const res = await salesApi.listOrders(p);
      setOrders(res.data);
      setLastPage(res.last_page);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to load orders');
    }
    setLoading(false);
  };

  /** Silent fetch — no loading spinner, used by auto-poll */
  const fetchOrdersSilent = async (p = page) => {
    try {
      const res = await salesApi.listOrders(p);
      setOrders(res.data);
      setLastPage(res.last_page);
    } catch {
      // silent
    }
  };

  const fetchInventory = async () => {
    setLoadingInventory(true);
    try {
      const data = await inventoryApi.listAll();
      setInventoryItems(data);
    } catch {
      setInventoryItems([]);
    }
    setLoadingInventory(false);
  };

  useEffect(() => { fetchOrders(); }, [page]);

  useEffect(() => {
    if (showCreate) fetchInventory();
  }, [showCreate]);

  const handleSelectItem = (idx: number, inventoryItemId: string) => {
    const id = parseInt(inventoryItemId);
    const inv = inventoryItems.find((i) => i.id === id);
    if (!inv) return;
    setItems((prev) =>
      prev.map((item, i) =>
        i === idx
          ? {
            ...item,
            inventory_item_id: id,
            product_name: inv.product_name,
            unit_price: parseFloat(inv.unit_price) || 0,
          }
          : item
      )
    );
  };

  const updateItemQty = (idx: number, qty: number) => {
    setItems((prev) => prev.map((item, i) => (i === idx ? { ...item, quantity: qty } : item)));
  };

  const addItem = () => {
    setItems((prev) => [...prev, { inventory_item_id: '', product_name: '', quantity: 1, unit_price: 0 }]);
  };

  const removeItem = (idx: number) => {
    setItems((prev) => prev.filter((_, i) => i !== idx));
  };

  const total = items.reduce((sum, i) => sum + i.quantity * i.unit_price, 0);

  const submitOrder = async (simulateFailure: boolean) => {
    setCreating(true);
    setError(null);
    setResult(null);

    if (items.some((i) => !i.inventory_item_id)) {
      setError('Please select an inventory item for each row.');
      setCreating(false);
      return;
    }

    try {
      const payload: CreateOrderPayload = {
        customer_name: customerName,
        customer_email: customerEmail,
        total_amount: total,
        items: items.map((i) => ({
          product_name: i.product_name,
          quantity: i.quantity,
          unit_price: i.unit_price,
        })),
        simulate_failure: simulateFailure || undefined,
      };
      const res = await salesApi.createOrder(payload);
      setResult(res);
      setShowCreate(false);
      setCustomerName('');
      setCustomerEmail('');
      setItems([{ inventory_item_id: '', product_name: '', quantity: 1, unit_price: 0 }]);
      // Auto-poll page 1 so the new order appears and updates in real time
      fetchOrders(1);
      startPolling(1);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to create order');
    }
    setCreating(false);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    await submitOrder(false);
  };

  const handleSubmitFail = async (e: React.MouseEvent) => {
    e.preventDefault();
    await submitOrder(true);
  };

  const handleDeliver = async (order: Order) => {
    if (!confirm(`Mark order #${order.id} for ${order.customer_name} as delivered?\nReserved inventory will be deducted (goods shipped).`)) return;
    setDelivering(order.id);
    setError(null);
    setSuccess(null);
    try {
      const res = await salesApi.deliverOrder(order.id);
      setSuccess(res.message + (res.correlation_id ? ` (Correlation: ${res.correlation_id.slice(0, 8)}…)` : ''));
      fetchOrders();
      startPolling();
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to deliver order');
    }
    setDelivering(null);
  };

  const handleRetry = async (order: Order) => {
    if (!confirm(`Retry failed order #${order.id}?\nThe workflow will resume and downstream services will process idempotently.`)) return;
    setRetrying(order.id);
    setError(null);
    setSuccess(null);
    try {
      const res = await salesApi.retryOrder(order.id);
      setSuccess(res.message + (res.correlation_id ? ` (Correlation: ${res.correlation_id.slice(0, 8)}…)` : ''));
      fetchOrders();
      startPolling();
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to retry order');
    }
    setRetrying(null);
  };

  const handleDelete = async (order: Order) => {
    if (!confirm(`Delete order #${order.id} for ${order.customer_name}?\nReserved inventory will be released.`)) return;
    setDeleting(order.id);
    setError(null);
    setSuccess(null);
    try {
      const res = await salesApi.deleteOrder(order.id);
      setSuccess(res.message + (res.correlation_id ? ` (Correlation: ${res.correlation_id.slice(0, 8)}…)` : ''));
      fetchOrders();
      startPolling();
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to delete order');
    }
    setDeleting(null);
  };

  const selectedIds = new Set(items.map((i) => i.inventory_item_id).filter(Boolean));

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Orders</h1>
          <p className="mt-1 text-sm text-gray-500">
            <strong>State</strong> = event processing (requested/completed/failed) · <strong>Status</strong> = business flow (submitted → paid → delivered)
          </p>
        </div>
        <div className="flex gap-3">
          <button onClick={() => fetchOrders()} className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            🔄 Refresh
          </button>
          <button
            onClick={() => setShowCreate(!showCreate)}
            className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
          >
            + New Order
          </button>
        </div>
      </div>

      {/* Success banner */}
      {success && (
        <div className="mb-4 rounded-lg bg-green-50 border border-green-200 p-4">
          <p className="text-sm font-medium text-green-800">✅ {success}</p>
        </div>
      )}

      {/* Result banner */}
      {result && (
        <div className="mb-4 rounded-lg bg-green-50 border border-green-200 p-4">
          <p className="text-sm font-medium text-green-800">✅ {result.message}</p>
          <p className="text-xs text-green-700 mt-1">
            Correlation ID: <code className="bg-green-100 px-1 rounded">{result.correlation_id}</code>
          </p>
        </div>
      )}
      {error && (
        <div className="mb-4 rounded-lg bg-red-50 border border-red-200 p-4">
          <p className="text-sm text-red-700">❌ {error}</p>
        </div>
      )}

      {/* Create Order Form */}
      {showCreate && (
        <div className="bg-white shadow rounded-lg p-6 mb-6">
          <h2 className="text-lg font-semibold mb-4">Create New Order</h2>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700">Customer Name *</label>
                <input
                  type="text"
                  required
                  value={customerName}
                  onChange={(e) => setCustomerName(e.target.value)}
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm border px-3 py-2"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">Customer Email *</label>
                <input
                  type="email"
                  required
                  value={customerEmail}
                  onChange={(e) => setCustomerEmail(e.target.value)}
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm border px-3 py-2"
                />
                <p className="mt-1 text-xs text-gray-400">
                  Required by Sales API validation.
                </p>
              </div>
            </div>

            <div>
              <div className="flex items-center justify-between mb-2">
                <label className="block text-sm font-medium text-gray-700">Order Items *</label>
                <button type="button" onClick={addItem} className="text-xs text-indigo-600 hover:text-indigo-500 font-medium">
                  + Add Item
                </button>
              </div>

              {loadingInventory ? (
                <p className="text-sm text-gray-400">Loading inventory…</p>
              ) : inventoryItems.length === 0 ? (
                <div className="rounded-md bg-yellow-50 border border-yellow-200 p-3">
                  <p className="text-sm text-yellow-800">
                    No inventory items available. Please{' '}
                    <a href="/inventory" className="underline font-medium">create inventory items</a>{' '}
                    first.
                  </p>
                </div>
              ) : (
                <>
                  <div className="grid grid-cols-12 gap-2 mb-1 text-xs font-medium text-gray-500 uppercase">
                    <div className="col-span-5">Product</div>
                    <div className="col-span-2">Qty Available</div>
                    <div className="col-span-2">Order Qty</div>
                    <div className="col-span-2">Unit Price</div>
                    <div className="col-span-1"></div>
                  </div>

                  {items.map((item, idx) => (
                    <div key={idx} className="grid grid-cols-12 gap-2 mb-2 items-center">
                      <div className="col-span-5">
                        <select
                          required
                          value={item.inventory_item_id}
                          onChange={(e) => handleSelectItem(idx, e.target.value)}
                          className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm border px-3 py-2"
                        >
                          <option value="">Select an item…</option>
                          {inventoryItems
                            .filter((inv) => !selectedIds.has(inv.id) || inv.id === item.inventory_item_id)
                            .map((inv) => (
                              <option key={inv.id} value={inv.id}>
                                {inv.product_name} ({inv.sku})
                              </option>
                            ))}
                        </select>
                      </div>
                      <div className="col-span-2 text-sm text-gray-500 px-2">
                        {item.inventory_item_id
                          ? inventoryItems.find((inv) => inv.id === item.inventory_item_id)?.quantity_available ?? '-'
                          : '-'}
                      </div>
                      <div className="col-span-2">
                        <input
                          type="number"
                          required
                          min={1}
                          max={
                            item.inventory_item_id
                              ? inventoryItems.find((inv) => inv.id === item.inventory_item_id)?.quantity_available ?? 999
                              : 999
                          }
                          value={item.quantity}
                          onChange={(e) => updateItemQty(idx, parseInt(e.target.value) || 1)}
                          className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm border px-3 py-2"
                        />
                      </div>
                      <div className="col-span-2 text-sm text-gray-700 px-2 font-medium">
                        ${item.unit_price.toFixed(2)}
                      </div>
                      <div className="col-span-1 text-right">
                        {items.length > 1 && (
                          <button type="button" onClick={() => removeItem(idx)} className="text-red-400 hover:text-red-600 text-sm">
                            ✕
                          </button>
                        )}
                      </div>
                    </div>
                  ))}

                  <div className="flex justify-between items-center mt-3 pt-3 border-t border-gray-100">
                    <p className="text-sm text-gray-500">
                      {items.filter((i) => i.inventory_item_id).length} item(s) selected
                    </p>
                    <p className="text-sm font-semibold text-gray-900">
                      Total: ${total.toFixed(2)}
                    </p>
                  </div>
                </>
              )}
            </div>

            <div className="flex gap-3 pt-2">
              <button
                type="submit"
                disabled={creating || inventoryItems.length === 0}
                className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
              >
                {creating ? 'Creating...' : 'Create Order (Start Workflow)'}
              </button>
              <button
                type="button"
                onClick={handleSubmitFail}
                disabled={creating || inventoryItems.length === 0 || items.some((i) => !i.inventory_item_id)}
                className="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500 disabled:opacity-50"
                title="Creates an order that will randomly fail at either Invoice or Payment service — demonstrates failure handling and retry"
              >
                {creating ? 'Creating...' : '💥 Create Fail Order'}
              </button>
              <button
                type="button"
                onClick={() => setShowCreate(false)}
                className="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
              >
                Cancel
              </button>
            </div>
            <p className="text-xs text-gray-400 mt-1">
              💡 <strong>Create Fail Order</strong> will randomly fail at the Invoice or Payment service, demonstrating eventual consistency failure handling.
              You can then <strong>Retry</strong> the failed order to see idempotent recovery.
            </p>
          </form>
        </div>
      )}

      {/* Orders — responsive: cards on mobile, table on lg+ */}
      <div className="bg-white shadow rounded-lg overflow-hidden">
        {loading ? (
          <div className="px-6 py-10 text-center text-sm text-gray-400">Loading...</div>
        ) : orders.length === 0 ? (
          <div className="px-6 py-10 text-center text-sm text-gray-400">No orders yet. Create one to start!</div>
        ) : (
          <>
            {/* ── Mobile card layout (< lg) ── */}
            <div className="lg:hidden divide-y divide-gray-200">
              {orders.map((order) => (
                <div key={order.id} className="p-4 space-y-3">
                  {/* Header row */}
                  <div className="flex items-start justify-between">
                    <div>
                      <span className="text-sm font-semibold text-gray-900">#{order.id}</span>
                      <span className="mx-2 text-gray-300">·</span>
                      <span className="text-sm text-gray-700">{order.customer_name}</span>
                      {order.customer_email && (
                        <div className="text-xs text-gray-400 mt-0.5">{order.customer_email}</div>
                      )}
                    </div>
                    <span className="text-sm font-semibold text-gray-900">${order.total_amount}</span>
                  </div>

                  {/* Items */}
                  {order.items && order.items.length > 0 && (
                    <div className="bg-gray-50 rounded-md p-2 space-y-1">
                      {order.items.map((item, idx) => (
                        <div key={idx} className="flex items-center justify-between text-xs text-gray-600">
                          <span>{item.product_name}</span>
                          <span className="text-gray-500">{item.quantity} × ${Number(item.unit_price).toFixed(2)}</span>
                        </div>
                      ))}
                      <div className="border-t border-gray-200 mt-1 pt-1 flex justify-between text-xs font-medium text-gray-700">
                        <span>Total Qty</span>
                        <span>{order.items.reduce((sum, i) => sum + i.quantity, 0)}</span>
                      </div>
                    </div>
                  )}

                  {/* Badges */}
                  <div className="flex flex-wrap items-center gap-2">
                    <span className="text-xs text-gray-500 uppercase font-medium">Status:</span>
                    <StatusBadge status={order.status} />
                    <span className="text-xs text-gray-500 uppercase font-medium ml-2">State:</span>
                    <StatusBadge status={order.state} />
                  </div>

                  {/* Failure description — only when state is failed */}
                  {order.state === 'failed' && order.state_failure_description && (
                    <p className="text-xs text-red-500 bg-red-50 rounded p-2" title={order.state_failure_description}>
                      {order.state_failure_description.length > 100
                        ? order.state_failure_description.slice(0, 100) + '…'
                        : order.state_failure_description}
                    </p>
                  )}

                  {/* Meta */}
                  <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-400">
                    <span>Correlation: <code className="font-mono text-gray-500">{order.correlation_id?.slice(0, 8)}…</code></span>
                    <span>{new Date(order.created_at).toLocaleString()}</span>
                  </div>

                  {/* Actions */}
                  <div className="flex flex-wrap gap-2 pt-1">
                    {order.state === 'failed' && (
                      <button
                        onClick={() => handleRetry(order)}
                        disabled={retrying === order.id}
                        className="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20 hover:bg-amber-100 disabled:opacity-50"
                      >
                        {retrying === order.id ? 'Retrying…' : '🔄 Retry'}
                      </button>
                    )}
                    {order.status === 'paid' && (
                      <button
                        onClick={() => handleDeliver(order)}
                        disabled={delivering === order.id}
                        className="inline-flex items-center rounded-md bg-green-50 px-2.5 py-1.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 hover:bg-green-100 disabled:opacity-50"
                      >
                        {delivering === order.id ? 'Delivering…' : '📦 Deliver'}
                      </button>
                    )}
                    <button
                      onClick={() => handleDelete(order)}
                      disabled={deleting === order.id}
                      className="inline-flex items-center rounded-md bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20 hover:bg-red-100 disabled:opacity-50"
                    >
                      {deleting === order.id ? 'Deleting…' : 'Delete'}
                    </button>
                  </div>
                </div>
              ))}
            </div>

            {/* ── Desktop table layout (lg+) ── */}
            <div className="hidden lg:block overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">State</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Correlation ID</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {orders.map((order) => (
                    <tr key={order.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#{order.id}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        <div>{order.customer_name}</div>
                        {order.customer_email && <div className="text-xs text-gray-400">{order.customer_email}</div>}
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-700">
                        {order.items && order.items.length > 0 ? (
                          <div className="space-y-0.5">
                            {order.items.map((item, idx) => (
                              <div key={idx} className="text-xs">
                                <span className="font-medium text-gray-800">{item.product_name}</span>
                                <span className="text-gray-400 ml-1">× {item.quantity}</span>
                                <span className="text-gray-500 ml-1">(${Number(item.unit_price).toFixed(2)})</span>
                              </div>
                            ))}
                          </div>
                        ) : (
                          <span className="text-xs text-gray-400">—</span>
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-center font-semibold text-gray-800">
                        {order.items && order.items.length > 0
                          ? order.items.reduce((sum, i) => sum + i.quantity, 0)
                          : '—'}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">${order.total_amount}</td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <StatusBadge status={order.status} />
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <StatusBadge status={order.state} />
                        {order.state === 'failed' && order.state_failure_description && (
                          <p className="text-xs text-red-500 mt-1 max-w-xs" title={order.state_failure_description}>
                            {order.state_failure_description.length > 60
                              ? order.state_failure_description.slice(0, 60) + '…'
                              : order.state_failure_description}
                          </p>
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-xs font-mono text-gray-500">{order.correlation_id?.slice(0, 8)}…</td>
                      <td className="px-6 py-4 whitespace-nowrap text-xs text-gray-400">{new Date(order.created_at).toLocaleString()}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-right space-x-2">
                        {order.state === 'failed' && (
                          <button
                            onClick={() => handleRetry(order)}
                            disabled={retrying === order.id}
                            className="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20 hover:bg-amber-100 disabled:opacity-50"
                          >
                            {retrying === order.id ? 'Retrying…' : '🔄 Retry'}
                          </button>
                        )}
                        {order.status === 'paid' && (
                          <button
                            onClick={() => handleDeliver(order)}
                            disabled={delivering === order.id}
                            className="inline-flex items-center rounded-md bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 hover:bg-green-100 disabled:opacity-50"
                          >
                            {delivering === order.id ? 'Delivering…' : '📦 Deliver'}
                          </button>
                        )}
                        <button
                          onClick={() => handleDelete(order)}
                          disabled={deleting === order.id}
                          className="text-red-600 hover:text-red-900 text-sm font-medium disabled:opacity-50"
                        >
                          {deleting === order.id ? 'Deleting…' : 'Delete'}
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </>
        )}
        <Pagination currentPage={page} lastPage={lastPage} onPageChange={setPage} />
      </div>
    </div>
  );
}
