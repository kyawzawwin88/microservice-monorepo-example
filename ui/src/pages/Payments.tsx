import React, { useEffect, useState } from 'react';
import { paymentApi, type Payment } from '../api/payments';
import StatusBadge from '../components/StatusBadge';
import Pagination from '../components/Pagination';

export default function Payments() {
  const [payments, setPayments] = useState<Payment[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [deleting, setDeleting] = useState<number | null>(null);

  const fetch = async (p = page) => {
    setLoading(true);
    try {
      const res = await paymentApi.list(p);
      setPayments(res.data);
      setLastPage(res.last_page);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to load payments');
    }
    setLoading(false);
  };

  const handleDelete = async (payment: Payment) => {
    if (!confirm(`Delete payment #${payment.id} ($${payment.amount})?`)) return;
    setDeleting(payment.id);
    setError(null);
    setSuccess(null);
    try {
      const res = await paymentApi.delete(payment.id);
      setSuccess(res.message);
      fetch();
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to delete payment');
    }
    setDeleting(null);
  };

  useEffect(() => { fetch(); }, [page]);

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Payments</h1>
          <p className="mt-1 text-sm text-gray-500">
            Payments are processed by the ProcessPaymentWorkflow when an InvoiceCreated event is received
          </p>
        </div>
        <button onClick={() => fetch()} className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
          🔄 Refresh
        </button>
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

      <div className="bg-white shadow rounded-lg overflow-hidden">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice ID</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Txn Ref</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">State</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Correlation ID</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
              <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {loading ? (
              <tr><td colSpan={9} className="px-6 py-10 text-center text-sm text-gray-400">Loading...</td></tr>
            ) : payments.length === 0 ? (
              <tr><td colSpan={9} className="px-6 py-10 text-center text-sm text-gray-400">No payments yet. Create an order to trigger the full flow.</td></tr>
            ) : (
              payments.map((pay) => (
                <tr key={pay.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 text-sm font-medium text-gray-900">#{pay.id}</td>
                  <td className="px-6 py-4 text-sm text-gray-700">{pay.invoice_id ? `#${pay.invoice_id}` : '—'}</td>
                  <td className="px-6 py-4 text-sm text-gray-700">${pay.amount}</td>
                  <td className="px-6 py-4 text-sm text-gray-700">{pay.payment_method ?? '—'}</td>
                  <td className="px-6 py-4 text-xs font-mono text-gray-500">{pay.transaction_reference?.slice(0, 12) ?? '—'}</td>
                  <td className="px-6 py-4">
                    <StatusBadge status={pay.state} />
                    {pay.state === 'failed' && pay.state_failure_description && (
                      <p className="text-xs text-red-500 mt-1 max-w-xs truncate">{pay.state_failure_description}</p>
                    )}
                  </td>
                  <td className="px-6 py-4 text-xs font-mono text-gray-500">{pay.correlation_id?.slice(0, 8)}…</td>
                  <td className="px-6 py-4 text-xs text-gray-400">{new Date(pay.created_at).toLocaleString()}</td>
                  <td className="px-6 py-4 text-right">
                    <button
                      onClick={() => handleDelete(pay)}
                      disabled={deleting === pay.id}
                      className="text-red-600 hover:text-red-900 text-sm font-medium disabled:opacity-50"
                    >
                      {deleting === pay.id ? 'Deleting…' : 'Delete'}
                    </button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
        <Pagination currentPage={page} lastPage={lastPage} onPageChange={setPage} />
      </div>
    </div>
  );
}
