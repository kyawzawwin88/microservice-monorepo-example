import { SERVICE_URLS, get, del, type PaginatedResponse } from './client';

export interface Payment {
  id: number;
  correlation_id: string;
  invoice_id: number | null;
  amount: string;
  payment_method: string | null;
  transaction_reference: string | null;
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

const base = () => `${SERVICE_URLS.payment}/api`;

export const paymentApi = {
  health: () => get<{ service: string; status: string; timestamp: string }>(`${base()}/health`),
  list: (page = 1) => get<PaginatedResponse<Payment>>(`${base()}/payments?page=${page}`),
  getById: (id: number) => get<Payment>(`${base()}/payments/${id}`),
  getByCorrelation: (cid: string) => get<Payment>(`${base()}/payments/correlation/${cid}`),
  delete: (id: number) => del<{ message: string }>(`${base()}/payments/${id}`),
  eventLogs: (page = 1) => get<PaginatedResponse<EventLog>>(`${base()}/event-logs?page=${page}`),
};
