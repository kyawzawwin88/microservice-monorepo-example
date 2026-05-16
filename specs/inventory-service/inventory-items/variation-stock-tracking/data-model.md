# Data Model: Variation Stock Tracking

**Feature**: `variation-stock-tracking` | **Date**: 2026-05-16

## Entity Relationship Overview

```text
inventory_items (1) ──< variation_dimensions (N)
                              └──< variation_dimension_values (N)
inventory_items (1) ──< inventory_variations (N)
                              └──< stock_balances (N) >── storage_locations
inventory_items (legacy) ──< stock_balances (N) >── storage_locations
stock_movements ──> (variation | item) + location(s)
```

## Tables

### `storage_locations`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | Display name |
| code | string unique | Short code (e.g. `WH-A`) |
| is_active | boolean | Inactive hidden from operator selects |
| timestamps | | |

**Validation**: name required; code unique among active.

### `inventory_items` (extend existing)

| Column | Type | Notes |
|--------|------|-------|
| has_variations | boolean default false | When true, quantities on row are aggregate/sync only |
| …existing | | product_name, sku, quantity_*, unit_price |

**Rules**: Legacy rows `has_variations = false`. Variation items may keep base SKU; variation SKU on child rows.

### `variation_dimensions`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| inventory_item_id | FK | |
| name | string | e.g. Size, Color |
| sort_order | int | UI ordering |

### `variation_dimension_values`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| variation_dimension_id | FK | |
| value | string | e.g. M, Blue |

**Unique**: `(variation_dimension_id, value)`

### `inventory_variations`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| inventory_item_id | FK | |
| sku | string unique | FR-009 |
| attribute_hash | string | Canonical combo key for FR-010 |
| label | string | e.g. `M / Blue` |
| timestamps | | |

**Unique**: `(inventory_item_id, attribute_hash)`

### `variation_attribute_values` (pivot)

Links `inventory_variations` to chosen `variation_dimension_values` (many-to-many).

### `stock_balances`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| balanceable_type | string | `InventoryVariation` or `InventoryItem` |
| balanceable_id | bigint | |
| storage_location_id | FK | |
| quantity_available | int ≥ 0 | |
| quantity_reserved | int ≥ 0 | |

**Unique**: `(balanceable_type, balanceable_id, storage_location_id)`

**Invariant**: `quantity_reserved ≤ quantity_available` not required (reserved can equal moved-from-available); transfer uses `available - reserved` as transferable per spec.

### `stock_movements`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| movement_type | enum | `stock_in`, `stock_out`, `transfer` |
| balanceable_type/id | morph | Variation or legacy item |
| storage_location_id | FK nullable | Target for in/out |
| source_location_id | FK nullable | Transfer source |
| destination_location_id | FK nullable | Transfer dest |
| quantity | int > 0 | |
| reference | string nullable | External ref |
| created_by | string nullable | Operator id/name |
| timestamps | | |

## State & Behavior

### Variation generation

- Input: dimensions + values → Cartesian product.
- On new dimension value: append new variation rows; existing balances unchanged (spec US1 scenario 3).
- On dimension value delete: blocked if any balance &gt; 0 at any location (edge case).

### Stock-in / stock-out

- Target: variation + location OR legacy item + location.
- Updates one `stock_balances` row; writes `stock_movements`.
- Legacy: sync `inventory_items.quantity_*` from default location balance.

### Transfer

- Input: variation|item, source_location_id, destination_location_id, quantity.
- Validates: source ≠ dest, quantity ≤ available−reserved at source.
- Transaction: decrement source balance, increment destination, one `transfer` movement.

### Reporting aggregates

- **Variation level**: `stock_balances` for variation (+ optional location filter).
- **Item level (variation items)**: `SUM(quantity_available)`, `SUM(quantity_reserved)` across variations (and locations per filter).
- **Legacy item**: read from item balance rows or mirrored columns.

## Reservation workflow mapping

`InventoryReservation` extended (plan):

| Field | Purpose |
|-------|---------|
| inventory_variation_id | nullable FK |
| storage_location_id | nullable FK, default if omitted |

`CheckStockActivity` resolves balance by variation_id or product_name + default location.

## Index recommendations

- `stock_balances(storage_location_id, balanceable_type, balanceable_id)`
- `inventory_variations(inventory_item_id)`
- `stock_movements(balanceable_type, balanceable_id, created_at)`
