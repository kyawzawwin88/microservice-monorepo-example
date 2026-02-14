import React, { useEffect, useState } from 'react';
import { salesApi, type EventLog } from '../api/sales';
import { invoiceApi } from '../api/invoices';
import { paymentApi } from '../api/payments';
import { inventoryApi } from '../api/inventory';
import Pagination from '../components/Pagination';

type ServiceName = 'sales' | 'invoice' | 'payment' | 'inventory';

const apis: Record<ServiceName, { eventLogs: (page?: number) => Promise<{ data: EventLog[]; last_page: number }> }> = {
  sales: salesApi,
  invoice: invoiceApi,
  payment: paymentApi,
  inventory: inventoryApi,
};

export default function EventLogs() {
  const [service, setService] = useState<ServiceName>('sales');
  const [logs, setLogs] = useState<EventLog[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchLogs = async (p = page) => {
    setLoading(true);
    setError(null);
    try {
      const res = await apis[service].eventLogs(p);
      setLogs(res.data);
      setLastPage(res.last_page);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to load event logs');
    }
    setLoading(false);
  };

  useEffect(() => { setPage(1); }, [service]);
  useEffect(() => { fetchLogs(); }, [page, service]);

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Event Logs</h1>
          <p className="mt-1 text-sm text-gray-500">Raw event payloads recorded across all microservices</p>
        </div>
        <button onClick={() => fetchLogs()} className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
          🔄 Refresh
        </button>
      </div>

      {/* Service Selector */}
      <div className="border-b border-gray-200 mb-4">
        <nav className="flex space-x-8">
          {(['sales', 'invoice', 'payment', 'inventory'] as ServiceName[]).map((svc) => (
            <button
              key={svc}
              onClick={() => setService(svc)}
              className={`py-3 px-1 border-b-2 text-sm font-medium capitalize ${
                service === svc
                  ? 'border-indigo-500 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700'
              }`}
            >
              {svc}
            </button>
          ))}
        </nav>
      </div>

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
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Correlation ID</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payload</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Received</th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {loading ? (
              <tr><td colSpan={6} className="px-6 py-10 text-center text-sm text-gray-400">Loading...</td></tr>
            ) : logs.length === 0 ? (
              <tr><td colSpan={6} className="px-6 py-10 text-center text-sm text-gray-400">No event logs for {service} service</td></tr>
            ) : (
              logs.map((log) => (
                <tr key={log.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 text-sm font-medium text-gray-900">#{log.id}</td>
                  <td className="px-6 py-4 text-sm">
                    <span className="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                      {log.event_type}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-xs font-mono text-gray-500">{log.correlation_id?.slice(0, 8) ?? '—'}…</td>
                  <td className="px-6 py-4 text-xs text-gray-500">{log.source_service ?? '—'}</td>
                  <td className="px-6 py-4 text-xs max-w-xs">
                    <details>
                      <summary className="cursor-pointer text-indigo-600 hover:text-indigo-500">View payload</summary>
                      <pre className="mt-2 p-2 bg-gray-50 rounded text-xs overflow-auto max-h-40">
                        {JSON.stringify(log.payload, null, 2)}
                      </pre>
                    </details>
                  </td>
                  <td className="px-6 py-4 text-xs text-gray-400">{new Date(log.created_at).toLocaleString()}</td>
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
