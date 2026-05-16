# API Contract: Inventory Variations & Stock

**Service**: inventory-service (port 8004)  
**Base path**: `/api` (via gateway) or direct service routes as configured  
**Date**: 2026-05-16

## Storage locations (admin)

### `GET /inventory/locations`

List active storage locations.

**Response 200**:

```json
{
  "data": [
    { "id": 1, "name": "Main Warehouse", "code": "WH-MAIN", "is_active": true }
  ]
}
```

### `POST /inventory/locations` (admin)

Create location. Body: `{ "name": "...", "code": "..." }`

## Inventory items with variations

### `POST /inventory` (extended)

Create item. Legacy mode (unchanged): `{ "product_name", "sku", "quantity_available", "unit_price" }`.

Variation mode:

```json
{
  "product_name": "T-Shirt",
  "sku": "TSHIRT-BASE",
  "unit_price": 19.99,
  "has_variations": true,
  "dimensions": [
    { "name": "Size", "values": ["S", "M", "L"] },
    { "name": "Color", "values": ["Red", "Blue"] }
  ],
  "initial_location_id": 1,
  "initial_quantity_per_variation": 0
}
```

**Response 201**: Item + generated `variations[]` with `id`, `sku`, `label`, balances.

### `GET /inventory/{id}` (extended)

**Response 200**: Item with `has_variations`, `dimensions`, `variations[]` (each with per-location balances), or legacy quantities.

### `POST /inventory/{id}/dimensions/{dimensionId}/values`

Add dimension value; regenerates new variation combinations (existing stock preserved).

## Stock movements

### `POST /inventory/stock-in`

```json
{
  "inventory_variation_id": 12,
  "storage_location_id": 1,
  "quantity": 30,
  "reference": "PO-1001"
}
```

Legacy alternative: `{ "inventory_item_id": 5, "storage_location_id": 1, "quantity": 30 }`

**Response 201**: `{ "movement_id", "balance": { "quantity_available", "quantity_reserved" } }`

**Errors**: 422 insufficient validation; 409 duplicate movement idempotency key (optional header `Idempotency-Key`).

### `POST /inventory/stock-out`

Same body shape as stock-in. **422** if insufficient available at location.

### `POST /inventory/transfers`

```json
{
  "inventory_variation_id": 12,
  "source_location_id": 1,
  "destination_location_id": 2,
  "quantity": 20
}
```

**Response 201**: `{ "movement_id", "source_balance", "destination_balance" }`

**Errors**: 422 same location, insufficient stock, zero quantity.

## Reporting

### `GET /inventory/{id}/stock-report`

Query: `?location_id=` (optional)

**Response 200**:

```json
{
  "item_id": 1,
  "has_variations": true,
  "aggregated": { "quantity_available": 35, "quantity_reserved": 2 },
  "variations": [
    {
      "id": 12,
      "label": "M / Blue",
      "sku": "TSHIRT-M-BLUE",
      "by_location": [
        { "location_id": 1, "quantity_available": 20, "quantity_reserved": 0 }
      ]
    }
  ]
}
```

## Reservation integration (event/workflow payload)

Extend order item payload consumed by `CheckStockActivity`:

```json
{
  "product_name": "T-Shirt",
  "quantity": 2,
  "variation_id": 12,
  "location_id": 1
}
```

Legacy: omit `variation_id` / `location_id` → resolve by `product_name` + default location.

`DeductStockActivity` and `ReleaseStockActivity` use the reservation’s `inventory_variation_id` and `storage_location_id` when present; otherwise they adjust legacy item `StockBalance` rows by `product_name` and sync `inventory_items.quantity_*` columns.

## Error envelope

```json
{
  "message": "Insufficient stock for variation at location WH-MAIN",
  "code": "insufficient_stock"
}
```
