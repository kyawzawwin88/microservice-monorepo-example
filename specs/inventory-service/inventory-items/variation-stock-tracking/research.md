# Research: Variation Stock Tracking

**Feature**: `variation-stock-tracking` | **Date**: 2026-05-16

## 1. Variation combination strategy

**Decision**: Cartesian product of dimension values at item save time; persist each
combination as an `inventory_variations` row.

**Rationale**: Matches spec assumptions and stakeholder example (S/M/L × Red/Blue).
Pre-generated rows simplify per-variation stock balances and reporting.

**Alternatives considered**:
- *Dynamic resolution at movement time* — rejected; harder to enforce FR-009 SKU and FR-010 uniqueness.
- *Manual row-by-row entry* — rejected; fails SC-001 (no bulk auto-generation).

## 2. Stock balance storage model

**Decision**: `stock_balances` table keyed by `(balanceable_type, balanceable_id, storage_location_id)` where balanceable is `InventoryVariation` or legacy `InventoryItem` (single row per location for legacy).

**Rationale**: FR-003/FR-013 require per-location quantities; normalized table supports transfers and reporting without JSON blobs.

**Alternatives considered**:
- *Quantity columns only on variations* — insufficient for multi-location.
- *Keep only `inventory_items.quantity_*`* — rejected for variations; legacy sync uses default location row + column mirror.

## 3. Legacy backward compatibility

**Decision**: `inventory_items.has_variations` boolean (default `false`). Legacy items:
- Continue exposing `quantity_available` / `quantity_reserved` on API responses.
- Internally map to `stock_balances` for `storage_location_id = default`.
- On movement/reservation, update balance row then sync columns on `inventory_items`.

**Rationale**: FR-007/FR-008/SC-004; minimizes breaking `CheckStockActivity` product_name lookup until sales passes `variation_id`.

**Alternatives considered**:
- *Big-bang migration to variations only* — rejected; violates spec.

## 4. Storage locations

**Decision**: Admin-managed `storage_locations` catalog (name, code, active flag). Operators select from list; no ad-hoc creation in v1.

**Rationale**: Clarification session deferred Q1; admin catalog is default best practice for stable reporting and transfers (recommended Option A).

**Alternatives considered**:
- Operator-created locations — deferred.
- Single implicit location only — insufficient for transfer story (User Story 3).

## 5. Stock transfer atomicity

**Decision**: Single DB transaction in `TransferStockAction`: lock source balance, validate available (excluding reserved), decrement source, increment destination, insert `stock_movements` type `transfer`.

**Rationale**: FR-014, FR-015, edge case atomicity, SC-007.

**Alternatives considered**:
- *Compensating stock-out + stock-in* — rejected; two audit events, harder atomic guarantee.

## 6. Reservation integration

**Decision**: Phase 1 accepts optional `variation_id` + `location_id` on reservation payload; fallback to `product_name` + default location for legacy orders.

**Rationale**: FR-012; aligns with spec assumption without blocking sales-service changes in same PR (contract documented).

**Alternatives considered**:
- *Require sales changes before inventory deploy* — rejected; breaks backward compatibility path.

## 7. UI interaction pattern

**Decision**: Dedicated routes (`/inventory/:id`, `/inventory/transfer`) and inline expandable sections on list page; modals only for destructive confirm (delete dimension value with zero stock).

**Rationale**: UI-001, UI-002, SC-006.

**Alternatives considered**:
- Modal wizards for create/transfer — rejected per clarification.

## 8. Testing approach

**Decision**: PHPUnit unit tests per Action/Service/Activity; Feature tests for HTTP endpoints; UI component tests for form validation logic.

**Rationale**: Constitution Principle III (non-negotiable).

**Alternatives considered**:
- Integration-only tests — rejected; insufficient granularity for SRP functions.
