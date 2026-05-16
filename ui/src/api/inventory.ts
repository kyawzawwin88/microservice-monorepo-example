import { SERVICE_URLS, get, post, put, del, type PaginatedResponse } from './client';

export interface StorageLocation {
  id: number;
  name: string;
  code: string;
  address_street?: string | null;
  country_code?: string | null;
  postal_code?: string | null;
  is_active: boolean;
}

export interface StorageLocationPayload {
  name: string;
  code: string;
  address_street?: string;
  country_code?: string;
  postal_code?: string;
}

export interface StockBalance {
  id: number;
  storage_location_id: number;
  quantity_available: number;
  quantity_reserved: number;
  storage_location?: StorageLocation;
}

export interface InventoryVariation {
  id: number;
  sku: string;
  label: string;
  attribute_hash: string;
  stock_balances?: StockBalance[];
}

export interface VariationDimension {
  id: number;
  name: string;
  sort_order: number;
  values?: { id: number; value: string }[];
}

export interface InventoryItem {
  id: number;
  product_name: string;
  sku: string;
  has_variations?: boolean;
  variations_count?: number;
  quantity_available: number;
  quantity_reserved: number;
  unit_price: string;
  variation_dimensions?: VariationDimension[];
  variations?: InventoryVariation[];
  stock_balances?: StockBalance[];
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
  storage_location_id?: number;
}

export interface CreateVariationInventoryPayload {
  product_name: string;
  sku: string;
  unit_price: number;
  has_variations: true;
  dimensions: { name: string; values: string[] }[];
  initial_location_id: number;
  initial_quantity_per_variation?: number;
}

export interface StockMovementPayload {
  inventory_variation_id?: number;
  inventory_item_id?: number;
  storage_location_id: number;
  quantity: number;
  reference?: string;
}

export interface TransferPayload {
  inventory_variation_id?: number;
  inventory_item_id?: number;
  source_location_id: number;
  destination_location_id: number;
  quantity: number;
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
  locations: () => get<{ data: StorageLocation[] }>(`${base()}/inventory/locations`),
  listAllLocations: () => get<{ data: StorageLocation[] }>(`${base()}/inventory/locations?all=1`),
  createLocation: (data: StorageLocationPayload) =>
    post<StorageLocation>(`${base()}/inventory/locations`, data),
  updateLocation: (id: number, data: StorageLocationPayload) =>
    put<StorageLocation>(`${base()}/inventory/locations/${id}`, data),
  deactivateLocation: (id: number) =>
    post<StorageLocation>(`${base()}/inventory/locations/${id}/deactivate`, {}),
  createWithVariations: (data: CreateVariationInventoryPayload) =>
    post<{ item: InventoryItem; variations: InventoryVariation[] }>(`${base()}/inventory`, data),
  stockIn: (data: StockMovementPayload) => post(`${base()}/inventory/stock-in`, data),
  stockOut: (data: StockMovementPayload) => post(`${base()}/inventory/stock-out`, data),
  transfer: (data: TransferPayload) => post(`${base()}/inventory/transfers`, data),
  stockReport: (id: number, locationId?: number) =>
    get<{
      item_id: number;
      has_variations: boolean;
      aggregated: { quantity_available: number; quantity_reserved: number };
      variations: { id: number; label: string; sku: string; by_location: StockBalance[] }[];
    }>(`${base()}/inventory/${id}/stock-report${locationId ? `?location_id=${locationId}` : ''}`),
  reservations: (page = 1) => get<PaginatedResponse<InventoryReservation>>(`${base()}/reservations?page=${page}`),
  eventLogs: (page = 1) => get<PaginatedResponse<EventLog>>(`${base()}/event-logs?page=${page}`),
};
