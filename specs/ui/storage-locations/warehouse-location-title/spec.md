# Feature Specification: Warehouse Location Page Title

**Feature Branch**: `004-warehouse-location-title`  
**Created**: 2026-05-16  
**Status**: Draft  
**Input**: User description: "change storage locations title in manage location to Warehouse Location"

## Specification Placement *(mandatory)*

Per project constitution, this spec MUST live at:

`specs/ui/storage-locations/warehouse-location-title/spec.md`

| Field | Value |
|-------|-------|
| **Module** | ui |
| **Domain** | storage-locations |
| **Workflow / capability** | warehouse-location-title |

This workflow is a focused labeling change on the existing location-management screen reached from Inventory via **Manage locations**. It does not change location data, APIs, or business rules.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Recognize the locations screen by warehouse terminology (Priority: P1)

As an inventory administrator, I want the main heading on the manage-locations screen to say **Warehouse Location** so the page matches how our team refers to physical sites.

**Why this priority**: The page title is the primary orientation cue when opening the screen from Inventory; inconsistent wording ("Storage locations") causes confusion with warehouse operations vocabulary.

**Independent Test**: Navigate from Inventory → Manage locations and confirm the page heading displays **Warehouse Location** exactly.

**Acceptance Scenarios**:

1. **Given** the administrator is on the Inventory list, **When** they choose **Manage locations**, **Then** the manage-locations screen shows **Warehouse Location** as its primary page heading.
2. **Given** the administrator opens the manage-locations screen directly (e.g., via bookmark), **When** the page loads, **Then** the primary page heading still reads **Warehouse Location**.
3. **Given** the locations list is loading, empty, or populated, **When** the page is visible, **Then** the heading remains **Warehouse Location** regardless of list state.

---

### User Story 2 - Unchanged navigation to reach the screen (Priority: P2)

As an inventory administrator, I want to reach the same manage-locations screen using the existing **Manage locations** control on Inventory so my workflow does not change.

**Why this priority**: Only the on-page title changes; entry points and routes should remain familiar.

**Independent Test**: From Inventory, use **Manage locations** and land on the same screen with the updated heading.

**Acceptance Scenarios**:

1. **Given** the administrator is on Inventory, **When** they click **Manage locations**, **Then** they arrive on the manage-locations screen (same destination as before) with heading **Warehouse Location**.

### Edge Cases

- Long or narrow viewports: the heading **Warehouse Location** must remain fully visible without truncation that hides the label (standard responsive heading behavior).
- Screen readers: the primary heading announced for the page must match **Warehouse Location**.
- Other screens that mention "storage location" in body copy, errors, or dropdowns are unchanged unless explicitly listed in scope.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The manage-locations screen (opened from Inventory via **Manage locations**) MUST display **Warehouse Location** as its primary page heading, replacing the previous **Storage locations** heading text.
- **FR-002**: The heading MUST use the exact capitalization and wording **Warehouse Location** (singular **Location**, title case on both words).
- **FR-003**: The **Manage locations** link label on the Inventory screen MUST remain **Manage locations** (navigation label unchanged).
- **FR-004**: Location list behavior, create/edit/deactivate flows, and all backend location concepts MUST remain unchanged; this feature is labeling only.
- **FR-005**: Supporting text on the manage-locations screen (subtitle, empty states, form labels, error messages) MAY continue to use "storage location" phrasing unless a future change requests broader copy alignment.

### Key Entities

- **Manage-locations screen**: Operator UI for viewing and administering warehouse/storage sites; identified by route used today from Inventory → Manage locations.
- **Page heading**: The single primary title at the top of that screen (previously "Storage locations").

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: In a walkthrough test, 100% of reviewers opening Inventory → Manage locations see **Warehouse Location** as the primary heading within 2 seconds of page load.
- **SC-002**: Zero functional regressions in create, edit, deactivate, or list locations compared to pre-change behavior (same actions available, same outcomes).
- **SC-003**: Accessibility check: the page’s top-level heading text exposed to assistive technology equals **Warehouse Location**.

## Assumptions

- "Manage location" in the request refers to the manage-locations screen reached from the **Manage locations** action on Inventory, not a rename of that navigation link.
- Only the primary page heading (`h1`-level title) is in scope; subtitle ("Manage warehouses and sites…"), empty-state copy ("No storage locations yet"), and API/domain terminology stay as-is for this change.
- No browser tab title or global navigation item exists today for this page; if one is added later, it should follow the same **Warehouse Location** label for consistency.
- Depends on the existing location-management UI delivered under `storage-locations` / `location-management-ui`; no new routes or permissions are required.
