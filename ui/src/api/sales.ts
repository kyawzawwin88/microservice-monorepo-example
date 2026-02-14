import { SERVICE_URLS, get, post, patch, del, type PaginatedResponse } from './client';

export interface Order {
  id: number;
  correlation_id: string;
  customer_name: string;
  customer_email: string | null;
  total_amount: string;
  items: { product_name: string; quantity: number; unit_price: number }[];
  state: string;                       // microservice event-processing state (requested/completed/failed)
  status: string;                      // business order status (submitted/paid/delivered)
  state_failure_description: string | null;
  created_at: string;
  updated_at: string;
}

export interface CreateOrderPayload {
  customer_name: string;
  customer_email?: string;
  total_amount: number;
  items: { product_name: string; quantity: number; unit_price: number }[];
  simulate_failure?: boolean;
}

export interface OrderWorkflowResponse {
  message: string;
  correlation_id: string;
  workflow_id: string;
}

export interface HealthResponse {
  service: string;
  status: string;
  timestamp: string;
}

export interface EventLog {
  id: number;
  event_type: string;
  correlation_id: string | null;
  payload: unknown;
  source_service: string | null;
  created_at: string;
}

const base = () => `${SERVICE_URLS.sales}/api`;

export const salesApi = {
  health: () => get<HealthResponse>(`${base()}/health`),
  listOrders: (page = 1) => get<PaginatedResponse<Order>>(`${base()}/orders?page=${page}`),
  getOrder: (id: number) => get<Order>(`${base()}/orders/${id}`),
  getOrderByCorrelation: (cid: string) => get<Order>(`${base()}/orders/correlation/${cid}`),
  createOrder: (data: CreateOrderPayload) => post<OrderWorkflowResponse>(`${base()}/orders`, data),
  retryOrder: (id: number) => post<OrderWorkflowResponse>(`${base()}/orders/${id}/retry`, {}),
  deliverOrder: (id: number) => patch<OrderWorkflowResponse>(`${base()}/orders/${id}/deliver`),
  deleteOrder: (id: number) => del<OrderWorkflowResponse>(`${base()}/orders/${id}`),
  eventLogs: (page = 1) => get<PaginatedResponse<EventLog>>(`${base()}/event-logs?page=${page}`),
};
