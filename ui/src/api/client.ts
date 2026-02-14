/**
 * Base API client for all microservices.
 *
 * In Docker-compose the Vite dev-server proxies:
 *   /sales/*     → sales-service:8000
 *   /invoice/*   → invoice-service:8000
 *   /payment/*   → payment-service:8000
 *   /inventory/* → inventory-service:8000
 *
 * When running outside Docker, override via env vars.
 */

export const SERVICE_URLS = {
  sales: import.meta.env.VITE_SALES_URL ?? '/svc/sales',
  invoice: import.meta.env.VITE_INVOICE_URL ?? '/svc/invoice',
  payment: import.meta.env.VITE_PAYMENT_URL ?? '/svc/payment',
  inventory: import.meta.env.VITE_INVENTORY_URL ?? '/svc/inventory',
};

export interface PaginatedResponse<T> {
  current_page: number;
  data: T[];
  last_page: number;
  per_page: number;
  total: number;
}

async function request<T>(url: string, options?: RequestInit): Promise<T> {
  const res = await fetch(url, {
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    ...options,
  });
  if (!res.ok) {
    const body = await res.json().catch(() => ({}));
    throw new Error(body.message ?? `HTTP ${res.status}`);
  }
  return res.json();
}

export function get<T>(url: string): Promise<T> {
  return request<T>(url);
}

export function post<T>(url: string, body: unknown): Promise<T> {
  return request<T>(url, { method: 'POST', body: JSON.stringify(body) });
}

export function put<T>(url: string, body: unknown): Promise<T> {
  return request<T>(url, { method: 'PUT', body: JSON.stringify(body) });
}

export function patch<T>(url: string, body?: unknown): Promise<T> {
  return request<T>(url, { method: 'PATCH', body: body ? JSON.stringify(body) : undefined });
}

export function del<T>(url: string): Promise<T> {
  return request<T>(url, { method: 'DELETE' });
}
