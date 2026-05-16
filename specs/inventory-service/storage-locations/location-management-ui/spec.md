# Feature Specification: Storage Location Management (UI)

**Feature Branch**: `003-inventory-location-admin`  
**Created**: 2026-05-16  
**Status**: Draft  
**Input**: Add a page to manage storage locations in the UI for inventory.

## Specification Placement *(mandatory)*

Per project constitution, this spec MUST live at:

`specs/inventory-service/storage-locations/location-management-ui/spec.md`

| Field | Value |
|-------|-------|
| **Module** | inventory-service (data) + ui (operator interface) |
| **Domain** | storage-locations |
| **Workflow / capability** | location-management-ui |

Downstream planning MAY extend the inventory-service API for update/deactivate if not yet available.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - View Storage Locations (Priority: P1)

As an inventory administrator, I want to see all storage locations in one place so I can
understand where stock can be held and which sites are active.

**Why this priority**: Stock-in, stock-out, transfers, and variation stock all require
a valid location; operators need a trustworthy directory before other flows work.

**Independent Test**: Open the locations page and verify every configured location
shows name, code, and active status without using other inventory screens.

**Acceptance Scenarios**:

1. **Given** at least two locations exist, **When** the administrator opens the
   locations page, **Then** all locations are listed with name, code, and whether
   each is active.
2. **Given** no locations exist, **When** the administrator opens the page,
   **Then** an empty state explains that locations must be created before stock
   movements and offers a clear action to add the first location.
3. **Given** locations exist, **When** the list is shown, **Then** locations are
   sorted in a predictable order (e.g., by name) so operators can find a site quickly.

---

### User Story 2 - Create Storage Location (Priority: P1)

As an inventory administrator, I want to add a new storage location with a unique
code so stock can be received, stored, and transferred at that site.

**Why this priority**: Without create capability, locations can only be added via
database seeding or API tools, blocking self-service warehouse setup.

**Independent Test**: Add a location from the UI and confirm it appears in the list
and is selectable on stock movement screens.

**Acceptance Scenarios**:

1. **Given** the administrator is on the locations page, **When** they submit a
   valid name and unique code, **Then** the new location is saved and shown in the
   list with active status.
2. **Given** a location code already exists, **When** the administrator submits
   a duplicate code, **Then** the system rejects the request with a clear message
   and does not create a second record.
3. **Given** required fields are missing, **When** the administrator submits the
   form, **Then** validation errors identify what must be corrected before save.
4. **Given** a new location was created, **When** the administrator opens stock-in,
   transfer, or variation detail flows, **Then** the new location appears in
   location selectors.

---

### User Story 3 - Edit Location Details (Priority: P2)

As an inventory administrator, I want to update a location’s display name (and
code only when safe) so warehouse labels stay accurate without re-seeding data.

**Why this priority**: Names change when warehouses are renamed; editing avoids
technical workarounds while codes remain stable identifiers for integrations.

**Independent Test**: Change a location’s name on the page and confirm the updated
label appears in the list and in location dropdowns on other inventory pages.

**Acceptance Scenarios**:

1. **Given** an existing active location, **When** the administrator updates its
   name and saves, **Then** the new name is shown everywhere that location is
   displayed.
2. **Given** an attempt to change a code to one already in use, **When** the
   administrator saves, **Then** the system rejects the change with a clear message.
3. **Given** a location with existing stock balances, **When** only the name is
   edited, **Then** stock quantities at that location are unchanged.

---

### User Story 4 - Deactivate Storage Location (Priority: P2)

As an inventory administrator, I want to deactivate a location that is no longer
used so it cannot be selected for new movements but historical stock data remains
intact.

**Why this priority**: Closing a warehouse should not delete audit history or break
reports for past movements tied to that site.

**Independent Test**: Deactivate a location and confirm it no longer appears in
selectors for new stock-in/out/transfer while remaining visible in admin list
(marked inactive) and in historical reports.

**Acceptance Scenarios**:

1. **Given** a location with zero available and reserved stock, **When** the
   administrator deactivates it, **Then** it is marked inactive and hidden from
   default selectors for new movements.
2. **Given** a location still has available or reserved stock, **When** the
   administrator attempts deactivation, **Then** the system blocks the action
   with an explanation that stock must be moved or cleared first.
3. **Given** an inactive location, **When** the administrator views the locations
   page, **Then** inactive locations are distinguishable from active ones (e.g.,
   badge or filter).

---

### Edge Cases

- What happens when the administrator loses network connectivity mid-save? The
  system must not show success until save is confirmed; failed saves show a
  recoverable error.
- How are inactive locations shown on stock movement pages? They MUST NOT appear
  in default dropdowns for new movements; optional “show inactive” is out of scope
  for v1 unless needed for support.
- Can two locations share the same display name? Allowed if codes differ; duplicate
  codes are never allowed.
- What if the only active location is deactivated? New stock movements MUST be
  blocked with guidance to activate or create a location.
- Default/system location (e.g., “Main Warehouse”): MAY exist from seed data;
  deactivation rules apply the same as for user-created locations.

## Requirements *(mandatory)*

### User Interface Constraints

- **UI-001**: Location management MUST use a dedicated page (not a modal wizard)
  for list and create/edit flows; inline forms or expandable rows are acceptable.
- **UI-002**: Modal dialogs MAY be used only for irreversible confirmations
  (e.g., deactivate location) and MUST NOT be the default pattern for data entry.

### Functional Requirements

- **FR-001**: System MUST provide a dedicated inventory UI route for storage
  location management accessible from inventory navigation.
- **FR-002**: System MUST list all storage locations with name, code, and active
  status.
- **FR-003**: System MUST allow administrators to create a location with a
  required name and unique code; new locations MUST be active by default.
- **FR-004**: System MUST validate create and update input and return clear,
  field-level error messages for duplicates and missing required fields.
- **FR-005**: System MUST allow administrators to update location name; code
  changes MUST be allowed only when the new code remains unique.
- **FR-006**: System MUST allow deactivation of a location; deactivated locations
  MUST NOT be offered for new stock-in, stock-out, or transfer operations.
- **FR-007**: System MUST prevent deactivation when the location has any available
  or reserved stock quantity across all products and variations.
- **FR-008**: New and updated locations MUST appear in location selectors on
  existing inventory pages (stock movement, transfer, variation detail) without
  requiring a full application restart.
- **FR-009**: System MUST preserve existing stock balances and movement history
  when a location is renamed or deactivated.
- **FR-010**: Location list MUST support identifying active vs inactive locations
  at a glance (visual indicator or filter for active only by default).

### Key Entities

- **Storage Location**: A named site where inventory is held (e.g., warehouse,
  store backroom). Attributes: human-readable name, unique short code, active flag.
- **Stock Balance** (reference): Per-product or per-variation quantities at a
  location; deactivation rules depend on whether any quantity remains.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Administrators can create a new storage location and see it in the
  list in under 1 minute without leaving the inventory section.
- **SC-002**: 100% of duplicate-code create/update attempts are rejected with an
  understandable message in acceptance testing.
- **SC-003**: After creating a location, it appears in stock movement location
  selectors on the first navigation to those pages (no manual cache clear).
- **SC-004**: Deactivation is blocked in 100% of test cases where the location
  still holds available or reserved stock.
- **SC-005**: At least 90% of administrators in a brief usability check can find
  the locations page from inventory navigation without documentation.

## Assumptions

- Target users are internal inventory/warehouse administrators, not end customers.
- Backend inventory service already exposes location read/create capabilities;
  update and deactivate MAY require API extensions planned in a later phase.
- Location codes are short alphanumeric identifiers suitable for dropdowns and
  reports (e.g., `WH-MAIN`, `STORE-01`).
- Physical deletion of locations is out of scope for v1; deactivation is the
  retirement mechanism.
- Role-based access control (who may manage locations) follows existing
  inventory UI access; no new permission model in v1 unless product requires it.
- English-only UI labels are sufficient for v1.
- Integration with variation stock tracking (per-location balances) is already
  in production; this feature only adds administration of the location catalog.
