# Data Model: Storage Location Management

**Feature**: `location-management-ui` | **Date**: 2026-05-16

## Existing entity (no schema migration required)

### StorageLocation

| Field | Type | Rules |
|-------|------|--------|
| `id` | bigint PK | Auto |
| `name` | string | Required, max 255, display label |
| `code` | string | Required, max 50, **unique**, stable identifier |
| `is_active` | boolean | Default `true`; `false` = deactivated |
| `created_at`, `updated_at` | timestamps | Standard |

**Table**: `storage_locations` (created by variation-stock migrations).

### Relationships

```text
StorageLocation 1 ── * StockBalance
StorageLocation 1 ── * StockMovement (storage_location_id, source, destination)
```

Deactivation does **not** delete related rows; movement history and balances remain for reporting.

## Domain rules

| Rule | Enforcement |
|------|-------------|
| Unique code on create | DB unique index + validation |
| Unique code on update | Validation ignoring current `id` |
| Deactivate only when no stock | `EnsureLocationDeactivatableAction` before `is_active = false` |
| Movement selectors | Only `is_active = true` locations |
| Admin list | All locations, default sort `name` ASC |
| Rename preserves stock | Update only `name`/`code` columns; no balance mutation |

## Stock balance check (deactivation)

For `storage_location_id = :id`:

```text
blocked IF EXISTS stock_balances
  WHERE storage_location_id = :id
    AND (quantity_available > 0 OR quantity_reserved > 0)
```

Legacy-only items without variation balances still use `stock_balances` after variation-stock backfill; if a location has no balance rows, deactivation is allowed.

## State transitions

```text
[created] ── is_active=true ──► [active]
[active]  ── deactivate (guard pass) ──► [inactive]
[inactive] ── (v1: no API) ──► [active]  # future reactivation
```

## UI view model (read-only projection)

| Field | Source |
|-------|--------|
| `id` | `StorageLocation.id` |
| `name` | `StorageLocation.name` |
| `code` | `StorageLocation.code` |
| `is_active` | `StorageLocation.is_active` |
| `has_stock` (optional badge) | Derived: any balance with available or reserved &gt; 0 |

`has_stock` may be computed client-side from a future summary endpoint or inferred only on deactivate error in v1.
