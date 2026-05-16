---
description: "Task list for storage location management UI"
---

# Tasks: Storage Location Management (UI)

**Input**: Design documents from `specs/inventory-service/storage-locations/location-management-ui/`  
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/inventory-locations-api.md

**Tests**: Unit tests are MANDATORY per constitution — every new/changed function has a PHPUnit or Vitest task.

**Organization**: Tasks grouped by user story (module → domain → workflow).

## Format: `[ID] [P?] [Story] Description`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Verify feature context and directory scaffolding.

- [x] T001 Verify `.specify/feature.json` points to `specs/inventory-service/storage-locations/location-management-ui`
- [x] T002 [P] Create `inventory-service/app/Services/Locations/` directory for location Actions
- [x] T003 [P] Confirm Vitest `npm test` script exists in `ui/package.json`

---

## Phase 2: Foundational (Backend API — Blocking)

**Purpose**: Admin list (`?all=1`), update, deactivate with stock guard — MUST complete before UI stories.

**⚠️ CRITICAL**: No user story UI work until this phase is complete.

### Unit tests first

- [x] T004 [P] Unit test `EnsureLocationDeactivatableAction` in `inventory-service/tests/Unit/Services/Locations/EnsureLocationDeactivatableActionTest.php`
- [x] T005 [P] Unit test `UpdateStorageLocationAction` in `inventory-service/tests/Unit/Services/Locations/UpdateStorageLocationActionTest.php`
- [x] T006 [P] Unit test `DeactivateStorageLocationAction` in `inventory-service/tests/Unit/Services/Locations/DeactivateStorageLocationActionTest.php`

### Implementation

- [x] T007 Implement `EnsureLocationDeactivatableAction` in `inventory-service/app/Services/Locations/EnsureLocationDeactivatableAction.php`
- [x] T008 Implement `UpdateStorageLocationAction` in `inventory-service/app/Services/Locations/UpdateStorageLocationAction.php`
- [x] T009 Implement `DeactivateStorageLocationAction` in `inventory-service/app/Services/Locations/DeactivateStorageLocationAction.php`
- [x] T010 Extend `StorageLocationController` (index `?all=1`, update, deactivate) in `inventory-service/app/Http/Controllers/StorageLocationController.php`
- [x] T011 Wire `PUT /inventory/locations/{id}` and `POST /inventory/locations/{id}/deactivate` in `inventory-service/routes/api.php`
- [x] T012 Feature test admin location flows in `inventory-service/tests/Feature/StorageLocationAdminTest.php`

**Checkpoint**: Backend API ready for FR-002–FR-007.

---

## Phase 3: User Story 1 — View Storage Locations (Priority: P1) 🎯 MVP

**Goal**: Dedicated admin page listing all locations with name, code, active status, sorted by name.

**Independent Test**: Open `/inventory/locations` and see all locations including inactive with badges.

### Implementation (US1)

- [x] T013 [P] [US1] Add `listAllLocations` to `ui/src/api/inventory.ts`
- [x] T014 [US1] Create `ui/src/pages/StorageLocations.tsx` with list, empty state, active/inactive badges
- [x] T015 [US1] Register route `/inventory/locations` **before** `/inventory/:id` in `ui/src/App.tsx`
- [x] T016 [US1] Add "Manage locations" link on `ui/src/pages/Inventory.tsx`

**Checkpoint**: US1 independently testable via admin page.

---

## Phase 4: User Story 2 — Create Storage Location (Priority: P1)

**Goal**: Inline create form on admin page; new locations active and appear in movement selectors.

**Independent Test**: Create location from UI; appears in list and stock transfer dropdown.

### Implementation (US2)

- [x] T017 [US2] Add `createLocation` to `ui/src/api/inventory.ts`
- [x] T018 [US2] Add inline create form with validation errors in `ui/src/pages/StorageLocations.tsx`
- [x] T019 [P] [US2] Unit test `locationFormUtils` in `ui/src/components/inventory/locationFormUtils.test.ts`

**Checkpoint**: US1 + US2 create flow complete.

---

## Phase 5: User Story 3 — Edit Location Details (Priority: P2)

**Goal**: Update name/code with duplicate-code rejection; stock unchanged.

**Independent Test**: Rename location; label updates in list and selectors.

### Implementation (US3)

- [x] T020 [US3] Add `updateLocation` to `ui/src/api/inventory.ts` and shared top form in `ui/src/pages/StorageLocations.tsx`

**Checkpoint**: US3 edit flow complete.

---

## Phase 6: User Story 4 — Deactivate Storage Location (Priority: P2)

**Goal**: Deactivate with stock guard; inactive hidden from movement selectors, visible in admin list.

**Independent Test**: Deactivate empty location; blocked when stock remains; inactive badge on admin page.

### Implementation (US4)

- [x] T021 [US4] Add `deactivateLocation` to `ui/src/api/inventory.ts` and confirm modal in `ui/src/pages/StorageLocations.tsx`

**Checkpoint**: All four user stories independently testable.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [x] T022 [P] Confirm `inventoryApi.locations()` still returns active-only for `StockTransfer.tsx` and `VariationProductForm.tsx`
- [x] T023 Run `inventory-service` PHPUnit and `ui` Vitest; fix failures
- [x] T024 Validate quickstart.md steps manually (document any blockers in PR notes)

---

## Dependencies & Execution Order

- **Phase 1** → **Phase 2** (blocks all UI)
- **Phase 3 (US1)** → **Phase 4 (US2)** → **Phase 5 (US3)** → **Phase 6 (US4)** (sequential on `StorageLocations.tsx`)
- **Phase 7** after desired stories complete

### MVP scope

Phases 1–3 deliver view-only admin page; add Phase 4 for self-service create.

---

## Implementation Strategy

1. Complete Phase 2 backend first (TDD: tests T004–T006, then T007–T012).
2. Ship US1 page + navigation (T013–T016).
3. Add create, edit, deactivate incrementally (T017–T021).
4. Regression + test run (T022–T024).
