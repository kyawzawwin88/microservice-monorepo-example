# Feature Specification: Replace Location Deactivate with Delete

**Feature Branch**: `006-location-delete`  
**Created**: 2026-05-16  
**Status**: Draft  
**Input**: User description: "change deactivate action of location to delete"

## Specification Placement *(mandatory)*

Per project constitution, this spec MUST live at:

`specs/inventory-service/storage-locations/location-delete/spec.md`

| Field | Value |
|-------|-------|
| **Module** | inventory-service (data) + ui (operator interface) |
| **Domain** | storage-locations |
| **Workflow / capability** | location-delete |
| **Placement** | Extend existing — replaces deactivate behavior defined in `specs/inventory-service/storage-locations/location-management-ui/spec.md` (User Story 4, FR-006, FR-007, FR-009, FR-010, UI-002) |

This amendment retires the **deactivate** (inactive flag) model in favor of **delete** (remove from the active catalog). Operators no longer manage an inactive list; removed locations disappear from administration and movement selectors while historical stock activity remains reportable.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Delete an Unused Storage Location (Priority: P1)

As an inventory administrator, I want to delete a storage location that is no longer needed so my location list only shows sites that are in use, without maintaining a separate inactive state.

**Why this priority**: Deactivate created ongoing clutter (inactive badges, filters, and confusion about whether a site still exists). Delete matches the mental model of "remove this warehouse from the catalog."

**Independent Test**: Delete a location with zero available and reserved stock; confirm it no longer appears on the locations page or in movement selectors, and that its code can be used for a new location.

**Acceptance Scenarios**:

1. **Given** a location with zero available and reserved stock across all products and variations, **When** the administrator confirms delete, **Then** the location is removed from the locations list and from all selectors for new stock-in, stock-out, and transfer operations.
2. **Given** a location was deleted, **When** the administrator creates a new location using the same code, **Then** the system accepts the code because the previous record no longer occupies it.
3. **Given** the administrator initiates delete, **When** the confirmation step is shown, **Then** the action is clearly labeled as delete (not deactivate) and explains that the location will be removed from the catalog.

---

### User Story 2 - Block Delete When Stock Remains (Priority: P1)

As an inventory administrator, I want delete to be blocked when a location still holds stock so inventory is not orphaned or left without a valid site reference.

**Why this priority**: Same business rule as the former deactivate guard; deleting a stocked location would break operational integrity.

**Independent Test**: Attempt to delete a location that still has available or reserved quantity; verify the action is rejected with actionable guidance.

**Acceptance Scenarios**:

1. **Given** a location has any available or reserved stock quantity, **When** the administrator attempts delete, **Then** the system blocks the action with a clear message that stock must be transferred or cleared first.
2. **Given** delete was blocked due to stock, **When** the administrator views the locations page, **Then** the location remains listed unchanged (not partially removed or marked deleted).

---

### User Story 3 - Preserve Historical Stock Activity After Delete (Priority: P2)

As an inventory administrator or auditor, I want past stock movements that referenced a deleted location to remain understandable in reports so warehouse history is not lost when a site is removed from the catalog.

**Why this priority**: Delete removes the site from day-to-day operations, but compliance and troubleshooting still require readable history.

**Independent Test**: Delete a location that had prior movements but zero current stock; open stock history or reports and confirm past entries still identify the location by name or code.

**Acceptance Scenarios**:

1. **Given** a location had stock movements in the past and now has zero available and reserved stock, **When** the administrator deletes it, **Then** historical movement and report entries continue to show the location identity (name and/or code) as it existed at the time of those movements.
2. **Given** a location was deleted, **When** an administrator views stock reports filtered by time range that includes that site, **Then** rows tied to that site remain visible with correct location labeling.

---

### User Story 4 - Remove Inactive-State UX (Priority: P2)

As an inventory administrator, I want the locations page to stop showing active/inactive distinctions for retired sites so I am not asked to manage locations that no longer exist in the catalog.

**Why this priority**: Delete replaces deactivate; there is no inactive inventory to filter or badge.

**Independent Test**: After deleting all test locations that were previously "inactive," confirm the list has no inactive indicators and no deactivate-only flows remain.

**Acceptance Scenarios**:

1. **Given** the administrator opens the locations page, **When** the list is displayed, **Then** only locations that exist in the catalog are shown (no inactive badge or filter for retired deactivate state).
2. **Given** the administrator views row actions for a location, **When** they choose to retire a site, **Then** the only retirement action offered is delete (deactivate is not shown).

---

### Edge Cases

- What happens when the administrator loses network connectivity mid-delete? The system must not show success until delete is confirmed; failed attempts show a recoverable error and the location remains listed.
- What if the only remaining catalog location is deleted? New stock movements must be blocked with guidance to create a location first.
- Default or seeded locations (e.g., "Main Warehouse"): same delete and stock-guard rules as user-created locations; confirmation copy should not imply special immunity.
- Location with movement history but zero current stock: delete is allowed after confirmation; historical labels must remain intact (User Story 3).
- Concurrent delete and stock-in to the same location: the system must reject whichever operation would leave inconsistent state (delete blocked if stock exists at commit time).

## Requirements *(mandatory)*

### User Interface Constraints

- **UI-001**: Location management continues to use the dedicated locations page; delete uses a confirmation dialog (replacing the deactivate confirmation modal).
- **UI-002**: All user-visible copy, buttons, and success or error messages MUST use **delete** terminology, not deactivate or inactive.

### Functional Requirements

- **FR-001**: System MUST replace the deactivate retirement action with delete for storage locations in the inventory administration UI.
- **FR-002**: System MUST remove a location from the catalog when delete succeeds (it MUST NOT remain in the list as inactive).
- **FR-003**: System MUST prevent delete when the location has any available or reserved stock quantity across all products and variations, with the same practical outcome as the former deactivate stock guard.
- **FR-004**: System MUST require explicit confirmation before delete; accidental one-click removal is not allowed.
- **FR-005**: Deleted locations MUST NOT appear in location selectors for new stock-in, stock-out, or transfer operations.
- **FR-006**: System MUST allow reuse of a location code after that location has been successfully deleted.
- **FR-007**: System MUST preserve readable location identity on historical stock movements and reports after delete (name and/or code visible on past records).
- **FR-008**: System MUST remove deactivate-specific behavior from the product surface: no deactivate action, no inactive status badge for retired sites, and no admin list filter whose sole purpose is inactive locations.
- **FR-009**: Create and update location behavior from location-management-ui remains unchanged except where it referenced inactive or deactivate semantics.
- **FR-010**: Stock balances at a location MUST be unchanged by delete attempts that are blocked; successful delete is only permitted when balances are zero.

### Key Entities

- **Storage Location**: A named site in the inventory catalog (name, unique code). Delete removes it from the catalog when guards pass; it is not retained as inactive.
- **Stock Balance**: Per-product or per-variation quantity at a location; delete is blocked while any available or reserved quantity remains.
- **Stock Movement** (reference): Historical record of stock activity; must retain location identity for reporting after the catalog entry is deleted.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: In acceptance testing, 100% of delete attempts on locations with remaining stock are blocked with an understandable message.
- **SC-002**: In acceptance testing, 100% of delete attempts on locations with zero available and reserved stock remove the location from the admin list within one page refresh.
- **SC-003**: After a successful delete, the location code is available for a new location on the first create attempt without manual data cleanup.
- **SC-004**: In acceptance testing, 100% of sampled historical movement rows for a deleted location still display correct location name or code.
- **SC-005**: In a brief usability check, at least 90% of administrators can find the delete action on the locations page without documentation, and none report a deactivate option still present.

## Assumptions

- Target users are internal inventory/warehouse administrators with existing access to location management.
- The prior deactivate implementation (API and UI) is the baseline to replace; this feature is a behavioral change, not net-new location management.
- Delete means remove from the operational catalog, not purge all historical stock records.
- Locations with zero current stock but past movements may be deleted; operators accept that the site will not appear in the catalog but history remains.
- Role-based access for who may delete locations follows existing inventory UI permissions.
- English-only UI labels are sufficient for this change.
- Related specs (`location-address`, variation stock tracking) continue to depend on valid location selectors; only catalog membership rules change.
