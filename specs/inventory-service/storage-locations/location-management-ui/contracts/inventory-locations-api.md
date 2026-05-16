# API Contract: Storage Locations (Admin)

**Service**: inventory-service (port 8004)  
**Base path**: `/api`  
**Date**: 2026-05-16  
**Feature**: `location-management-ui`

Extends location endpoints documented in [variation-stock contract](../../inventory-items/variation-stock-tracking/contracts/inventory-variations-api.md).

## Types

```typescript
interface StorageLocation {
  id: number;
  name: string;
  code: string;
  is_active: boolean;
  created_at?: string;
  updated_at?: string;
}
```

## Endpoints

### `GET /inventory/locations`

**Purpose**: Movement selectors (active only).

**Query**: none

**Response 200**:

```json
{
  "data": [
    { "id": 1, "name": "Main Warehouse", "code": "WH-MAIN", "is_active": true }
  ]
}
```

Only rows where `is_active = true`, ordered by `name`.

---

### `GET /inventory/locations?all=1`

**Purpose**: Admin management page (all locations).

**Response 200**: Same shape as above; includes inactive rows (`is_active: false`).

---

### `POST /inventory/locations`

**Purpose**: Create location (existing; unchanged).

**Body**:

```json
{ "name": "Store Backroom", "code": "STORE-01" }
```

**Response 201**: `StorageLocation` object.

**Errors**:
- `422` — validation (`name`/`code` required, duplicate `code`)

---

### `PUT /inventory/locations/{id}`

**Purpose**: Update name and/or code (US3).

**Body**:

```json
{ "name": "Renamed Warehouse", "code": "WH-MAIN" }
```

**Response 200**: Updated `StorageLocation`.

**Errors**:
- `404` — unknown id
- `422` — validation or duplicate `code`

---

### `POST /inventory/locations/{id}/deactivate`

**Purpose**: Deactivate location (US4).

**Body**: empty

**Response 200**:

```json
{
  "id": 2,
  "name": "Old Site",
  "code": "OLD",
  "is_active": false
}
```

**Errors**:
- `404` — unknown id
- `422` — location still has available or reserved stock (message explains transfer/clear first)
- `422` — already inactive (idempotent message acceptable)

## Consumer rules

| Consumer | Endpoint | Filter |
|----------|----------|--------|
| StockTransfer, VariationProductForm, stock-in/out | `GET /locations` (no `all`) | Active only |
| StorageLocations admin page | `GET /locations?all=1` | All |
| After create/update | Refetch selectors on next mount | N/A |

## UI routes (SPA)

| Route | Page |
|-------|------|
| `/inventory/locations` | `StorageLocations.tsx` |
| `/inventory` | Link: "Manage locations" |

Register **before** `/inventory/:id` in React Router to avoid `:id` capturing `locations`.
