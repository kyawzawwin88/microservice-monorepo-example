# Research: Storage Location Management (UI)

**Feature**: `location-management-ui` | **Branch**: `003-inventory-location-admin` | **Date**: 2026-05-16

## R1: Admin list vs movement selectors

**Decision**: Keep `GET /inventory/locations` for **active-only** (movement selectors). Add query `?all=1` (or dedicated admin route) returning **all** locations sorted by name for the management page.

**Rationale**: Stock-in, transfer, and variation forms must never offer inactive sites (FR-006). Administrators still need to see inactive rows on the admin page (FR-010, US4).

**Alternatives considered**:
- Single endpoint always returning all rows — rejected; risks inactive locations leaking into movement UIs if a consumer forgets to filter.
- Separate `/inventory/locations/admin` — acceptable but adds surface area; query flag reuses one controller with explicit intent.

## R2: Update and deactivate API shape

**Decision**:
- `PUT /inventory/locations/{id}` — body `{ "name", "code" }` with unique `code` validation ignoring current row.
- `POST /inventory/locations/{id}/deactivate` — sets `is_active = false` after guard; no hard delete.

**Rationale**: Matches existing REST style in inventory-service; deactivate is a distinct business action with stock guard (FR-007), not a generic PATCH.

**Alternatives considered**:
- `PATCH` with partial body — flexible but weaker contract for tests and UI.
- `DELETE` — rejected; spec forbids physical deletion (Assumptions).

## R3: Deactivation guard implementation

**Decision**: `EnsureLocationDeactivatableAction` checks `stock_balances` for the location where `quantity_available > 0 OR quantity_reserved > 0`. Throws domain exception → HTTP 422 with message.

**Rationale**: FR-007 references available and reserved quantities across variations; `stock_balances` is the source of truth post variation-stock-tracking.

**Alternatives considered**:
- Sum `stock_movements` — rejected; balances are maintained for reads and match reservation flows.
- Block only if `quantity_available > 0` — rejected; reserved stock still ties the location to live operations.

## R4: UI page pattern

**Decision**: New page `ui/src/pages/StorageLocations.tsx` at route `/inventory/locations`; link from Inventory page header and optional nav sub-link. Inline create/edit rows or a simple form section on the same page (UI-001). Confirm modal only for deactivate (UI-002).

**Rationale**: Aligns with `Inventory.tsx`, `StockTransfer.tsx`, and variation-stock UI constraints.

**Alternatives considered**:
- Tab on Inventory list — rejected; locations are catalog admin, not item CRUD.
- Modal wizard for create — rejected by UI-002.

## R5: Reactivate out of scope for v1

**Decision**: No `activate` endpoint in v1; inactive locations remain in DB and admin list. Reactivation can be a follow-up if operators need it.

**Rationale**: Spec does not require reactivation; reduces scope. Workaround: direct DB or future story.

**Alternatives considered**:
- `POST .../activate` in v1 — deferred unless product asks during `/speckit-clarify`.

## R6: Testing strategy

**Decision**:
- PHPUnit: unit tests for `UpdateStorageLocationAction`, `DeactivateStorageLocationAction`, `EnsureLocationDeactivatableAction`; feature tests for HTTP routes.
- UI: Vitest for form validation helpers; manual smoke in quickstart.

**Rationale**: Constitution III — every new Action and controller method gets unit/feature coverage.
