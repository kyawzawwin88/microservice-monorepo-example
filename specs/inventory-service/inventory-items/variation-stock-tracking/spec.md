# Feature Specification: Inventory Item Variations & Per-Variation Stock

**Feature Branch**: `001-variation-stock-tracking`  
**Created**: 2026-05-16  
**Status**: Draft  
**Input**: task-98721 — Enhance inventory management to support item variations
(size, color, material, etc.) with independent stock quantities, stock-in/stock-out
transactions per variation, and reporting at variation and aggregated item levels.
Backward compatibility required for existing single-stock inventory items.

## Specification Placement *(mandatory)*

Per project constitution, this spec MUST live at:

`specs/inventory-service/inventory-items/variation-stock-tracking/spec.md`

| Field | Value |
|-------|-------|
| **Module** | inventory-service |
| **Domain** | inventory-items |
| **Workflow / capability** | variation-stock-tracking |

Downstream planning MAY split stock-in, stock-out, and reporting into separate
workflow task documents under this directory if implementation scope is large.

## Clarifications

### Session 2026-05-16

- Q: Preferred UI interaction pattern for inventory flows → A: Avoid modal
  popups as much as possible; use dedicated pages, inline forms, expandable
  sections, or side panels for primary workflows.
- Q: Movement between storage sites → A: Allow stock transfer from one location
  to another, with quantities scoped per location.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Create Items With Variations (Priority: P1)

As an inventory manager, I want to create an inventory item with multiple
variation dimensions (e.g., T-Shirt with sizes S/M/L and colors Red/Blue) so
each sellable combination is tracked as its own stock unit.

**Why this priority**: Without defined variations, no per-variation stock or
reporting is possible. This is the foundation for the entire feature.

**Independent Test**: Create a new item with two dimensions and multiple values;
verify each resulting variation is listed with zero or initial stock and a
distinct identity (e.g., unique identifier or SKU per variation).

**Acceptance Scenarios**:

1. **Given** no existing item, **When** the manager creates "T-Shirt" with
   dimensions Size (S, M, L) and Color (Red, Blue), **Then** the system creates
   six variations (S-Red, S-Blue, M-Red, M-Blue, L-Red, L-Blue) each with its
   own stock record.
2. **Given** a variation-capable item, **When** the manager views the item
   detail, **Then** all variations are visible with their attribute labels and
   current stock quantities.
3. **Given** an item with variations, **When** the manager adds a new value to
   an existing dimension (e.g., size XL), **Then** new variations are generated
   for that value combined with all other dimension values, without affecting
   stock of existing variations.

---

### User Story 2 - Stock Movements Per Variation (Priority: P1)

As a warehouse operator, I want to record stock-in and stock-out against a
specific variation so replenishment and deduction apply only to that variation's
quantity.

**Why this priority**: Independent per-variation stock control is the core
business requirement (per stakeholder review).

**Independent Test**: Stock-in 50 units on variation "M-Blue" only; confirm
"M-Blue" increases by 50 while other variations of the same item are unchanged;
stock-out 10 from "M-Blue" and confirm only that variation decreases.

**Acceptance Scenarios**:

1. **Given** variation "M-Blue" has 20 units, **When** the operator records
   stock-in of 30 for "M-Blue", **Then** "M-Blue" shows 50 units and other
   variations are unchanged.
2. **Given** variation "S-Red" has 15 units, **When** the operator records
   stock-out of 5 for "S-Red", **Then** "S-Red" shows 10 units and the
   transaction history references that variation.
3. **Given** a stock-out request exceeds available quantity for one variation,
   **When** the operator submits the movement, **Then** the system rejects the
   movement with a clear insufficient-stock message for that variation only.
4. **Given** an order or reservation targets a specific variation, **When**
   stock is reserved or deducted, **Then** only that variation's available and
   reserved quantities change at the selected location.
5. **Given** stock-in or stock-out for a variation, **When** the operator
   records the movement, **Then** they MUST select the storage location affected.

---

### User Story 3 - Stock Transfer Between Locations (Priority: P1)

As a warehouse operator, I want to transfer stock of a specific variation (or
legacy item) from one storage location to another so inventory is rebalanced
without manual stock-out plus stock-in at two sites.

**Why this priority**: Multi-location operations are required by stakeholders;
transfers must preserve per-location accuracy for variations and legacy items.

**Independent Test**: Location A has 40 units of variation "M-Blue"; transfer
20 to Location B; confirm A shows 20, B shows 20, and a transfer record links
source, destination, quantity, and variation (or legacy item).

**Acceptance Scenarios**:

1. **Given** variation "M-Blue" has 40 available at Warehouse A and 5 at
   Warehouse B, **When** the operator transfers 20 from A to B, **Then** A shows
   20 available, B shows 25 available, and other variations are unchanged.
2. **Given** a legacy item with 100 units at Location X, **When** the operator
   transfers 30 to Location Y, **Then** only item-level quantities at X and Y
   change; no variation selection is required.
3. **Given** insufficient stock at the source location, **When** the operator
   submits a transfer, **Then** the system rejects the transfer with a clear
   message and leaves both locations unchanged.
4. **Given** source and destination are the same location, **When** the operator
   submits a transfer, **Then** the system rejects the request.
5. **Given** a completed transfer, **When** the manager views movement history,
   **Then** the transfer appears as a single auditable event with source
   location, destination location, quantity, and product (variation or legacy
   item).

---

### User Story 4 - Stock Reporting at Two Levels (Priority: P2)

As an inventory manager, I want to view stock levels per variation and as an
aggregated total for the parent inventory item so I can reconcile warehouse
counts and identify which variants are low.

**Why this priority**: Reporting delivers operational value once variations and
movements exist; it depends on P1 data but can follow initial movement support.

**Independent Test**: After movements on multiple variations, open the item
report and confirm per-variation quantities match movements and the item total
equals the sum of variation quantities (excluding reserved logic if reported
separately).

**Acceptance Scenarios**:

1. **Given** an item with three variations having stocks 10, 20, and 5,
   **When** the manager views the item-level stock summary, **Then** aggregated
   available quantity shows 35.
2. **Given** the same item, **When** the manager views the variation breakdown
   report, **Then** each variation shows its own available, reserved (if
   applicable), and movement history.
3. **Given** a filter by low stock, **When** only one variation is below the
   threshold, **Then** the report highlights that variation without falsely
   flagging the entire item unless aggregated total is also below threshold.

---

### User Story 5 - Legacy Items Without Variations (Priority: P1)

As an inventory manager using existing data, I want current inventory items
(with a single stock quantity per item) to continue working unchanged so
upgrades do not disrupt live operations.

**Why this priority**: Backward compatibility is explicitly required; breaking
legacy items would block rollout.

**Independent Test**: Use an existing non-variation item; perform list, stock-in,
stock-out, reservation, and report actions; confirm behavior matches pre-feature
expectations with no variation UI or data required.

**Acceptance Scenarios**:

1. **Given** an inventory item created before variations existed, **When** any
   standard inventory operation runs, **Then** the system treats it as a single
   stock unit with no variation selection required.
2. **Given** mixed catalog (legacy and variation items), **When** the manager
   lists inventory, **Then** both types appear with correct stock display (single
   quantity vs variation breakdown).
3. **Given** historical stock movements for legacy items, **When** reports are
   generated, **Then** historical transactions remain visible and attributable
   to the item without requiring variation identifiers.

---

### Edge Cases

- What happens when the last unit of one variation is stocked out while others
  remain available? Item-level aggregated total decreases only by that
  variation's amount; the item remains orderable via other variations.
- How does the system handle duplicate variation attribute combinations? Creation
  MUST be rejected if the same combination already exists for the item.
- What happens when a dimension value is removed after stock exists? Removal MUST
  be blocked or require zero stock and explicit confirmation to prevent silent
  data loss.
- How are partial reservations handled when only some variations have stock?
  Reservation and fulfillment MUST target the requested variation; insufficient
  stock on that variation MUST NOT deduct from sibling variations.
- What happens when converting a legacy item to use variations? Out of scope for
  initial release unless explicitly requested; legacy items remain single-stock
  until a future migration feature is specified.
- How does reporting treat reserved vs available at item level? Aggregated
  figures MUST sum variation-level available and reserved separately and
  consistently with legacy single-stock items.
- What happens during a failed transfer mid-process? Transfer MUST be atomic:
  either both source decrement and destination increment succeed, or neither
  applies.
- How does transfer interact with reserved stock? Transfers MUST only move
  available (unreserved) quantity unless a future release explicitly allows
  reserved transfers.
- Can operators transfer partial quantities across multiple variations in one
  action? Initial release supports one product (one variation or one legacy
  item) per transfer transaction; bulk multi-line transfer is out of scope.

## Requirements *(mandatory)*

### User Interface Constraints

- **UI-001**: Primary inventory flows (create item, manage variations, stock-in,
  stock-out, transfer, reports) MUST NOT rely on modal popups; use dedicated
  pages, inline forms, expandable rows/sections, or side panels instead.
- **UI-002**: Modal dialogs MAY be used only for rare irreversible confirmations
  (e.g., delete dimension value with zero stock) and MUST NOT be the default
  pattern for data entry or multi-step workflows.

### Functional Requirements

- **FR-001**: System MUST allow creation of inventory items with one or more
  variation dimensions (e.g., size, color, material), each with a defined set
  of values.
- **FR-002**: System MUST generate one stock-tracked variation for each unique
  combination of dimension values (e.g., Size M + Color Blue).
- **FR-003**: Each variation MUST maintain its own available quantity, reserved
  quantity (where reservations apply), and movement history per storage location,
  independent of other variations and other locations.
- **FR-004**: System MUST support stock-in transactions recorded against a
  specific variation and storage location, increasing only that variation's
  available quantity at that location.
- **FR-005**: System MUST support stock-out transactions recorded against a
  specific variation and storage location, decreasing only that variation's
  available quantity at that location and rejecting insufficient stock for that
  variation at that location.
- **FR-013**: System MUST maintain stock balances per storage location for each
  variation and for each legacy item (item-level balance without variations).
- **FR-014**: System MUST support stock transfer from a source location to a
  destination location for a selected variation or legacy item, atomically
  decrementing source and incrementing destination by the transfer quantity.
- **FR-015**: System MUST reject transfers when source available quantity is
  insufficient, when source equals destination, or when quantity is zero or
  negative.
- **FR-016**: System MUST record transfer events in movement history with source
  location, destination location, quantity, product identity, and timestamp.
- **FR-006**: System MUST expose reporting that shows stock per variation and
  aggregated totals (available and reserved) at the parent inventory item
  level, with the ability to view by storage location and across all locations.
- **FR-017**: Inventory operator interfaces MUST follow UI-001 and UI-002
  (minimal modal usage for primary flows).
- **FR-007**: System MUST preserve full functionality for existing inventory
  items that have no variations, using the current single-quantity model without
  requiring variation selection.
- **FR-008**: System MUST preserve access to historical stock movements for
  legacy items without retroactively assigning variation identifiers.
- **FR-009**: System MUST assign each variation a distinct business identifier
  (e.g., variation SKU or code) suitable for lookup, reporting, and integration
  with order/reservation flows.
- **FR-010**: System MUST prevent duplicate variation combinations within the
  same inventory item.
- **FR-011**: Inventory listing and detail views MUST indicate whether an item
  is variation-based or legacy single-stock.
- **FR-012**: Existing inventory workflows (e.g., reservation, stock check,
  deduction, release) MUST continue to operate for legacy items and MUST support
  variation-targeted operations where product selection includes a variation.

### Key Entities

- **Inventory Item**: Parent product record (name, base SKU or code, optional
  variation configuration, legacy flag or implicit single-stock mode).
- **Variation Dimension**: Named attribute axis on an item (e.g., "Size") with
  an ordered or unordered set of allowed values.
- **Inventory Variation**: A concrete combination of dimension values belonging
  to one item; holds per-location stock quantities and identifier.
- **Storage Location**: A named site where stock is held (e.g., warehouse, store,
  aisle zone); used for stock-in, stock-out, transfer, and reporting.
- **Stock Balance**: Available and reserved quantities for a variation (or legacy
  item) at a specific storage location.
- **Stock Movement (Stock-In / Stock-Out / Transfer)**: A quantity change event
  tied to a variation or legacy item, always scoped to one or two locations
  (transfer: source and destination), with timestamp, quantity, type, and
  reference metadata.
- **Stock Report View**: A presentation of quantities at variation level and
  rolled-up item level, filterable by location, including optional low-stock
  indicators per variation per location.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Inventory managers can create an item with at least two dimensions
  and three values per dimension, and see all resulting variations in under 5
  minutes without manual per-row data entry for each combination.
- **SC-002**: 100% of stock-in and stock-out operations on variation items affect
  only the selected variation in acceptance testing (zero cross-variation
  leakage in test scenarios).
- **SC-003**: Aggregated item-level stock always equals the sum of variation
  available quantities (and reserved quantities, if shown) in automated
  reconciliation checks across 100% of test items.
- **SC-004**: All regression scenarios for legacy (non-variation) items pass
  without change to prior behavior for list, create, update, movement, and
  reservation flows.
- **SC-005**: Managers can identify low-stock variations and item-level totals
  from reports in a single view without exporting data for 95% of common
  catalog sizes (items with up to 50 variations).
- **SC-006**: Operators complete a single-location stock transfer (select
  product, source, destination, quantity) in under 2 minutes using non-modal
  UI patterns in usability testing.
- **SC-007**: 100% of transfer acceptance tests show atomic updates (no partial
  state where source decreased but destination did not increase).

## Assumptions

- Variation dimensions are defined at item creation or maintenance time; free-form
  variation labels on each movement are not required for v1.
- Combination generation follows the Cartesian product of dimension values (e.g.,
  3 sizes × 2 colors = 6 variations).
- Each variation receives a unique SKU or code derived from or associated with
  the parent item for operational traceability.
- Converting existing single-stock items into multi-variation items is out of
  scope for this feature; legacy items remain on the single-stock model.
- UI for operators (warehouse and manager) is in scope; layouts favor dedicated
  pages and inline interaction per clarification (minimal modals).
- At least one default storage location exists for legacy data migration; new
  movements require an explicit location.
- Stock transfers move only available (unreserved) quantity in v1.
- Order and reservation integrations will pass a variation identifier when
  the ordered product is variation-based; legacy orders continue to reference
  the item only.
- Material is treated like size and color as a standard dimension type with no
  special business rules beyond independent stock tracking.
