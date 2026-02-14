import { SERVICE_URLS, get, del, type PaginatedResponse } from './client';

export interface Invoice {
  id: number;
  correlation_id: string;
  order_id: number | null;
  invoice_number: string;
  amount: string;
  state: string;
  state_failure_description: string | null;
  created_at: string;
  updated_at: string;
}

export interface EventLog {
  id: number;
  event_type: string;
  correlation_id: string | null;
  payload: unknown;
  source_service: string | null;
  created_at: string;
}

const base = () => `${SERVICE_URLS.invoice}/api`;

export const invoiceApi = {
  health: () => get<{ service: string; status: string; timestamp: string }>(`${base()}/health`),
  list: (page = 1) => get<PaginatedResponse<Invoice>>(`${base()}/invoices?page=${page}`),
  getById: (id: number) => get<Invoice>(`${base()}/invoices/${id}`),
  getByCorrelation: (cid: string) => get<Invoice>(`${base()}/invoices/correlation/${cid}`),
  delete: (id: number) => del<{ message: string }>(`${base()}/invoices/${id}`),
  eventLogs: (page = 1) => get<PaginatedResponse<EventLog>>(`${base()}/event-logs?page=${page}`),
};
