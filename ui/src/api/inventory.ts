import { SERVICE_URLS, get, post, put, del, type PaginatedResponse } from './client';

export interface InventoryItem {
  id: number;
  product_name: string;
  sku: string;
  quantity_available: number;
  quantity_reserved: number;
  unit_price: string;
  created_at: string;
  updated_at: string;
}

export interface InventoryReservation {
  id: number;
  correlation_id: string;
  order_id: number;
  product_name: string;
  quantity: number;
  reserved_quantity: number;
  state: string;
  state_failure_description: string | null;
  created_at: string;
  updated_at: string;
}

export interface CreateInventoryPayload {
  product_name: string;
  sku: string;
  quantity_available: number;
  unit_price: number;
}

export interface UpdateInventoryPayload {
  product_name?: string;
  sku?: string;
  quantity_available?: number;
  unit_price?: number;
}

export interface EventLog {
  id: number;
  event_type: string;
  correlation_id: string | null;
  payload: unknown;
  source_service: string | null;
  created_at: string;
}

const base = () => `${SERVICE_URLS.inventory}/api`;

export const inventoryApi = {
  health: () => get<{ service: string; status: string; timestamp: string }>(`${base()}/health`),
  list: (page = 1) => get<PaginatedResponse<InventoryItem>>(`${base()}/inventory?page=${page}`),
  listAll: () => get<InventoryItem[]>(`${base()}/inventory/all`),
  getById: (id: number) => get<InventoryItem>(`${base()}/inventory/${id}`),
  create: (data: CreateInventoryPayload) => post<InventoryItem>(`${base()}/inventory`, data),
  update: (id: number, data: UpdateInventoryPayload) => put<InventoryItem>(`${base()}/inventory/${id}`, data),
  delete: (id: number) => del<{ message: string }>(`${base()}/inventory/${id}`),
  reservations: (page = 1) => get<PaginatedResponse<InventoryReservation>>(`${base()}/reservations?page=${page}`),
  eventLogs: (page = 1) => get<PaginatedResponse<EventLog>>(`${base()}/event-logs?page=${page}`),
};
