---
description: "Task list for inventory item variations and per-variation stock tracking"
---

# Tasks: Inventory Item Variations & Per-Variation Stock

**Input**: Design documents from `specs/inventory-service/inventory-items/variation-stock-tracking/`  
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/inventory-variations-api.md

**Tests**: Unit tests are MANDATORY per constitution — every new/changed function has a PHPUnit or UI unit test task.

**Organization**: Tasks grouped by user story (module → domain → workflow). Paths use repo root.

## Format: `[ID] [P?] [Story] Description`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Tooling and test scaffolding for inventory-service and ui.

- [x] T001 Verify feature branch `001-variation-stock-tracking` and `.specify/feature.json` points to `specs/inventory-service/inventory-items/variation-stock-tracking`
- [x] T002 [P] Confirm `inventory-service/tests/Unit` exists; add `tests/Unit/Services` and `tests/Unit/Models` directories if missing
- [x] T003 [P] Add Vitest (or existing test runner) config in `ui/package.json` if no `npm test` script exists
- [x] T004 [P] Register new API routes group in `inventory-service/routes/api.php` (placeholder comments for locations, movements, reports)
- [x] T005 [P] Add `StorageLocationSeeder` stub in `inventory-service/database/seeders/StorageLocationSeeder.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Schema, models, legacy backfill, and admin locations — MUST complete before user story phases.

**⚠️ CRITICAL**: No user story work until this phase is complete.

### Migrations & models

- [x] T006 Create migration `add_has_variations_to_inventory_items_table` in `inventory-service/database/migrations/`
- [x] T007 Create migration `create_storage_locations_table` in `inventory-service/database/migrations/`
- [x] T008 Create migration `create_variation_dimensions_tables` (dimensions + values) in `inventory-service/database/migrations/`
- [x] T009 Create migration `create_inventory_variations_table` (+ pivot `variation_attribute_values`) in `inventory-service/database/migrations/`
- [x] T010 Create migration `create_stock_balances_table` in `inventory-service/database/migrations/`
- [x] T011 Create migration `create_stock_movements_table` in `inventory-service/database/migrations/`
- [x] T012 [P] Add model `StorageLocation` in `inventory-service/app/Models/StorageLocation.php`
- [x] T013 [P] Add model `VariationDimension` in `inventory-service/app/Models/VariationDimension.php`
- [x] T014 [P] Add model `VariationDimensionValue` in `inventory-service/app/Models/VariationDimensionValue.php`
- [x] T015 [P] Add model `InventoryVariation` in `inventory-service/app/Models/InventoryVariation.php`
- [x] T016 [P] Add model `StockBalance` (morph balanceable) in `inventory-service/app/Models/StockBalance.php`
- [x] T017 [P] Add model `StockMovement` in `inventory-service/app/Models/StockMovement.php`
- [x] T018 Extend `InventoryItem` with `has_variations`, relations to dimensions/variations in `inventory-service/app/Models/InventoryItem.php`

### Unit tests — models

- [x] T019 [P] Unit test `StockBalance` unique constraint per balanceable+location in `inventory-service/tests/Unit/Models/StockBalanceTest.php`
- [x] T020 [P] Unit test `InventoryVariation` attribute_hash uniqueness in `inventory-service/tests/Unit/Models/InventoryVariationTest.php`

### Legacy backfill & sync

- [x] T021 Implement `SyncLegacyItemStockFromBalanceAction` (single responsibility) in `inventory-service/app/Services/Stock/SyncLegacyItemStockFromBalanceAction.php`
- [x] T022 Unit test `SyncLegacyItemStockFromBalanceAction` in `inventory-service/tests/Unit/Services/Stock/SyncLegacyItemStockFromBalanceActionTest.php`
- [x] T023 Implement data migration/command to seed default location and backfill `stock_balances` from existing `inventory_items` quantities in `inventory-service/database/seeders/StorageLocationSeeder.php`
- [x] T024 Run migrations and seeder locally; verify legacy items retain `quantity_available` via quickstart step 6

### Admin storage locations API

- [x] T025 Implement `StorageLocationController` (index, store) in `inventory-service/app/Http/Controllers/StorageLocationController.php`
- [x] T026 Unit test `StorageLocationController` validation in `inventory-service/tests/Unit/Http/StorageLocationControllerTest.php`
- [x] T027 Wire `GET/POST /inventory/locations` routes in `inventory-service/routes/api.php`

**Checkpoint**: Schema ready, default location exists, legacy stock backfilled.

---

## Phase 3: User Story 1 — Create Items With Variations (Priority: P1) 🎯 MVP

**Goal**: Create variation-capable items; auto-generate Cartesian combinations with SKUs and zero initial balances.

**Independent Test**: POST item with Size S/M/L and Color Red/Blue → 6 variations with distinct SKUs; add size XL → new combinations without altering existing stock.

### Unit tests first (US1)

- [x] T028 [P] [US1] Unit test `GenerateVariationsAction` Cartesian count (3×2=6) in `inventory-service/tests/Unit/Services/Variations/GenerateVariationsActionTest.php`
- [x] T029 [P] [US1] Unit test `GenerateVariationsAction` rejects duplicate attribute_hash in `inventory-service/tests/Unit/Services/Variations/GenerateVariationsActionTest.php`
- [x] T030 [P] [US1] Unit test `AppendDimensionValueAction` adds combinations only in `inventory-service/tests/Unit/Services/Variations/AppendDimensionValueActionTest.php`

### Implementation (US1)

- [x] T031 [US1] Implement `GenerateVariationsAction` in `inventory-service/app/Services/Variations/GenerateVariationsAction.php`
- [x] T032 [US1] Implement `BuildVariationSkuAction` in `inventory-service/app/Services/Variations/BuildVariationSkuAction.php`
- [x] T033 [US1] Implement `AppendDimensionValueAction` in `inventory-service/app/Services/Variations/AppendDimensionValueAction.php`
- [x] T034 [US1] Extend `InventoryItemController@store` for variation payload per `contracts/inventory-variations-api.md` in `inventory-service/app/Http/Controllers/InventoryItemController.php`
- [x] T035 [US1] Extend `InventoryItemController@show` to include dimensions and variations in `inventory-service/app/Http/Controllers/InventoryItemController.php`
- [x] T036 [US1] Add `POST /inventory/{id}/dimensions/{dimensionId}/values` handler in `inventory-service/app/Http/Controllers/InventoryItemController.php`
- [x] T037 [US1] Feature test create variation item returns 6 variations in `inventory-service/tests/Feature/InventoryVariationCreateTest.php`

### UI (US1)

- [x] T038 [P] [US1] Extend types and API client in `ui/src/api/inventory.ts` for variation create/show
- [x] T039 [US1] Add dedicated create flow (inline form, no modal) on `ui/src/pages/Inventory.tsx` or new `ui/src/pages/InventoryCreate.tsx`
- [x] T040 [US1] Add `ui/src/pages/InventoryItemDetail.tsx` with expandable variation list (read-only balances)
- [x] T041 [US1] Register routes in `ui/src/App.tsx` for `/inventory/:id` and create path
- [x] T042 [P] [US1] Unit test variation form validation helper in `ui/src/components/inventory/variationForm.test.ts`

**Checkpoint**: US1 independently testable via API and item detail page.

---

## Phase 4: User Story 2 — Stock Movements Per Variation (Priority: P1)

**Goal**: Stock-in and stock-out per variation (or legacy item) at a selected location.

**Independent Test**: Stock-in 30 to M-Blue at location 1 only; other variations unchanged; stock-out rejects insufficient stock.

### Unit tests first (US2)

- [x] T043 [P] [US2] Unit test `RecordStockInAction` increments correct balance only in `inventory-service/tests/Unit/Services/Stock/RecordStockInActionTest.php`
- [x] T044 [P] [US2] Unit test `RecordStockOutAction` rejects insufficient stock in `inventory-service/tests/Unit/Services/Stock/RecordStockOutActionTest.php`
- [x] T045 [P] [US2] Unit test legacy stock-in syncs `inventory_items` columns in `inventory-service/tests/Unit/Services/Stock/RecordStockInActionTest.php`

### Implementation (US2)

- [x] T046 [US2] Implement `ResolveStockBalanceAction` (variation or legacy + location) in `inventory-service/app/Services/Stock/ResolveStockBalanceAction.php`
- [x] T047 [US2] Implement `RecordStockInAction` in `inventory-service/app/Services/Stock/RecordStockInAction.php`
- [x] T048 [US2] Implement `RecordStockOutAction` in `inventory-service/app/Services/Stock/RecordStockOutAction.php`
- [x] T049 [US2] Implement `StockMovementController` (stock-in, stock-out) in `inventory-service/app/Http/Controllers/StockMovementController.php`
- [x] T050 [US2] Wire `POST /inventory/stock-in` and `POST /inventory/stock-out` in `inventory-service/routes/api.php`
- [x] T051 [US2] Feature test stock-in isolation per variation in `inventory-service/tests/Feature/StockMovementTest.php`

### UI (US2)

- [x] T052 [P] [US2] Add stock-in/out API methods in `ui/src/api/inventory.ts`
- [x] T053 [US2] Add inline stock-in/out forms per variation row in `ui/src/pages/InventoryItemDetail.tsx` (location selector, no modal)
- [x] T054 [P] [US2] Unit test stock movement form validation in `ui/src/components/inventory/stockMovementForm.test.ts`

**Checkpoint**: US2 independently testable on item detail page.

---

## Phase 5: User Story 3 — Stock Transfer Between Locations (Priority: P1)

**Goal**: Atomic transfer of available stock between two locations for one variation or legacy item.

**Independent Test**: Transfer 20 from location A to B; A decreases, B increases; same-location rejected.

### Unit tests first (US3)

- [x] T055 [P] [US3] Unit test `TransferStockAction` atomic success in `inventory-service/tests/Unit/Services/Stock/TransferStockActionTest.php`
- [x] T056 [P] [US3] Unit test `TransferStockAction` rejects same location and insufficient available in `inventory-service/tests/Unit/Services/Stock/TransferStockActionTest.php`
- [x] T057 [P] [US3] Unit test transfer excludes reserved quantity in `inventory-service/tests/Unit/Services/Stock/TransferStockActionTest.php`

### Implementation (US3)

- [x] T058 [US3] Implement `TransferStockAction` (DB transaction) in `inventory-service/app/Services/Stock/TransferStockAction.php`
- [x] T059 [US3] Add `transfer` method to `StockMovementController` in `inventory-service/app/Http/Controllers/StockMovementController.php`
- [x] T060 [US3] Wire `POST /inventory/transfers` in `inventory-service/routes/api.php`
- [x] T061 [US3] Feature test transfer atomicity (SC-007) in `inventory-service/tests/Feature/StockTransferTest.php`

### UI (US3)

- [x] T062 [P] [US3] Add transfer API method in `ui/src/api/inventory.ts`
- [x] T063 [US3] Create dedicated page `ui/src/pages/StockTransfer.tsx` (source/dest/location/product selectors, submit on page)
- [x] T064 [US3] Register `/inventory/transfer` route and nav link in `ui/src/App.tsx` and `ui/src/components/Layout.tsx`
- [x] T065 [P] [US3] Unit test transfer page validation in `ui/src/pages/StockTransfer.test.tsx`

**Checkpoint**: US3 independently testable via API and transfer page.

---

## Phase 6: User Story 5 — Legacy Items Without Variations (Priority: P1)

**Goal**: Existing single-stock items work unchanged; no variation UI required.

**Independent Test**: Legacy item list/create/stock movements/reservations behave as before.

### Unit tests & regression (US5)

- [x] T066 [P] [US5] Unit test legacy item API response shape unchanged in `inventory-service/tests/Unit/Models/InventoryItemLegacyTest.php`
- [x] T067 [US5] Feature test legacy stock-in/out without variation_id in `inventory-service/tests/Feature/LegacyInventoryRegressionTest.php`
- [x] T068 [US5] Update `CheckStockActivity` to resolve legacy balance by `product_name` + default location in `inventory-service/app/Activities/CheckStockActivity.php`
- [x] T069 [US5] Unit test `CheckStockActivity` legacy path in `inventory-service/tests/Unit/Activities/CheckStockActivityTest.php`
- [x] T070 [US5] Update `DeductStockActivity` and `ReleaseStockActivity` for `StockBalance` legacy path in `inventory-service/app/Activities/DeductStockActivity.php` and `ReleaseStockActivity.php`
- [x] T071 [P] [US5] Unit tests for deduct/release legacy path in `inventory-service/tests/Unit/Activities/DeductStockActivityTest.php`

### UI (US5)

- [x] T072 [US5] Ensure `ui/src/pages/Inventory.tsx` legacy create/edit forms unchanged for `has_variations=false` items
- [x] T073 [US5] Display single-quantity badge for legacy rows in `ui/src/pages/Inventory.tsx`

**Checkpoint**: SC-004 regression scenarios pass.

---

## Phase 7: User Story 4 — Stock Reporting at Two Levels (Priority: P2)

**Goal**: Reports show per-variation and aggregated item totals, filterable by location.

**Independent Test**: Three variations 10+20+5 → aggregated 35; low-stock highlights single variation.

### Unit tests first (US4)

- [x] T074 [P] [US4] Unit test `GetStockReportAction` aggregation equals sum of variations in `inventory-service/tests/Unit/Services/Stock/GetStockReportActionTest.php`
- [x] T075 [P] [US4] Unit test low-stock filter per variation in `inventory-service/tests/Unit/Services/Stock/GetStockReportActionTest.php`

### Implementation (US4)

- [x] T076 [US4] Implement `GetStockReportAction` in `inventory-service/app/Services/Stock/GetStockReportAction.php`
- [x] T077 [US4] Implement `StockReportController@show` in `inventory-service/app/Http/Controllers/StockReportController.php`
- [x] T078 [US4] Wire `GET /inventory/{id}/stock-report` in `inventory-service/routes/api.php`
- [x] T079 [US4] Feature test report totals (SC-003) in `inventory-service/tests/Feature/StockReportTest.php`

### UI (US4)

- [x] T080 [P] [US4] Add report API and types in `ui/src/api/inventory.ts`
- [x] T081 [US4] Add report section with location filter on `ui/src/pages/InventoryItemDetail.tsx` (inline table, no modal)
- [x] T082 [P] [US4] Unit test report summary component in `ui/src/components/inventory/stockReportUtils.test.ts`

**Checkpoint**: US4 reporting visible on item detail.

---

## Phase 8: Reservation Integration (Cross-cutting, supports US2/US5)

**Purpose**: Variation-aware reservations per plan Phase D.

- [x] T083 Extend `InventoryReservation` migration/model with `inventory_variation_id`, `storage_location_id` in `inventory-service/app/Models/InventoryReservation.php`
- [x] T084 Update `CheckStockActivity` for `variation_id` + `location_id` in order payload in `inventory-service/app/Activities/CheckStockActivity.php`
- [x] T085 Unit test `CheckStockActivity` variation path in `inventory-service/tests/Unit/Activities/CheckStockActivityVariationTest.php`
- [x] T086 Document payload extension in `specs/inventory-service/inventory-items/variation-stock-tracking/contracts/inventory-variations-api.md` (verify matches implementation)

---

## Phase 9: Polish & Cross-Cutting Concerns

**Purpose**: Quality gates, docs, and end-to-end validation.

- [x] T087 [P] Add `has_variations` indicator to `InventoryItemController@index` response in `inventory-service/app/Http/Controllers/InventoryItemController.php`
- [x] T088 [P] Block dimension value delete when stock &gt; 0 in `EnsureDimensionValueDeletableAction` in `inventory-service/app/Services/Variations/`
- [x] T089 Run full PHPUnit suite: `docker compose exec inventory-service ./vendor/bin/phpunit` (18 tests)
- [x] T090 Run UI tests: `cd ui && npx vitest run`
- [x] T091 Execute manual quickstart scenarios in `specs/inventory-service/inventory-items/variation-stock-tracking/quickstart.md` (see qa/qa-20260516-190500.md)
- [x] T092 [P] Verify UI-001: audit `ui/src/pages/Inventory.tsx`, `InventoryItemDetail.tsx`, `StockTransfer.tsx` for modal usage; only delete confirm() on Inventory list
- [x] T093 Update checklist `specs/inventory-service/inventory-items/variation-stock-tracking/checklists/requirements.md` notes after implementation complete

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)** → **Foundational (Phase 2)** → User story phases
- **US1 (Phase 3)** → **US2 (Phase 4)** → **US3 (Phase 5)** (sequential dependency on balances)
- **US5 (Phase 6)** can start after Phase 2 (parallel with US1–US3 for backend regression)
- **US4 (Phase 7)** after US1 + US2 (needs variation data)
- **Reservation (Phase 8)** after US2 foundational balance resolution
- **Polish (Phase 9)** last

### User Story Dependencies

| Story | Depends on | Independent test |
|-------|------------|------------------|
| US1 | Phase 2 | Create 6 variations via API |
| US2 | US1 (variation rows) | Stock-in one variation |
| US3 | US2 (balances at 2+ locations) | Transfer between locations |
| US5 | Phase 2 | Legacy item CRUD unchanged |
| US4 | US1, US2 | Aggregated report = 35 |

### Parallel Opportunities

- T012–T017 model files [P] in parallel after migrations
- T019–T020 model tests [P]
- US1 tests T028–T030 [P] before implementation
- UI API client tasks [P] alongside backend when contract stable
- US4/US5 backend can overlap if staffed separately after Phase 2

---

## Parallel Example: User Story 1

```bash
# Tests first (parallel):
tests/Unit/Services/Variations/GenerateVariationsActionTest.php
tests/Unit/Services/Variations/AppendDimensionValueActionTest.php

# Then sequential: Actions → Controller → Feature test → UI
```

---

## Implementation Strategy

### MVP First (User Story 1 only)

1. Complete Phase 1–2 (schema + locations + legacy backfill)
2. Complete Phase 3 (US1) — variation create + detail view
3. **STOP and VALIDATE**: quickstart steps 1–2 (create item, verify 6 variations)

### Incremental Delivery

1. Foundation → US1 → demo variations  
2. US2 → stock movements  
3. US3 → transfers  
4. US5 → legacy regression gate  
5. US4 → reporting  
6. Phase 8–9 → reservations + polish

### Suggested MVP Scope

**Phases 1–3 (through T042)** — variation catalog creation and visibility.

---

## Task Summary

| Metric | Count |
|--------|-------|
| **Total tasks** | 93 |
| **Phase 1 Setup** | 5 |
| **Phase 2 Foundational** | 22 |
| **US1** | 15 |
| **US2** | 12 |
| **US3** | 11 |
| **US5** | 8 |
| **US4** | 9 |
| **Reservation** | 4 |
| **Polish** | 7 |

**Format validation**: All tasks use `- [ ] T###` with file paths; story phases include `[USn]` labels.
