# Feature Specification: Storage Location Physical Address

**Feature Branch**: `007-location-country-postal`  
**Created**: 2026-05-16  
**Status**: Draft  
**Input**: User description: "location address should have country and postal code"

## Specification Placement *(mandatory)*

Per project constitution, this spec MUST live at:

`specs/inventory-service/storage-locations/location-address/spec.md`

| Field | Value |
|-------|-------|
| **Module** | inventory-service (data) + ui (operator interface) |
| **Domain** | storage-locations |
| **Workflow / capability** | location-address |
| **Placement** | Extend existing — builds on `specs/inventory-service/storage-locations/location-management-ui/spec.md` |

### When to create vs extend

This feature **extends** storage location management by adding a physical address
with **country** and **postal code** as distinct attributes, plus multi-line street
or locality text. Name, code, and active status behavior from location-management-ui
remain unchanged unless explicitly amended here.

**Amendment (2026-05-16)**: Prior draft allowed only a single free-text address field
with structured country and postal code out of scope. This revision adds country and
postal code as required structured parts of the address model when operators record
location addresses.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Record Full Address When Creating a Location (Priority: P1)

As an inventory administrator, I want to enter street or locality details, country,
and postal code when I create a storage location so dispatch, receiving, and warehouse
staff have enough context for shipping labels and regional reporting.

**Why this priority**: New sites need complete address data from day one; country and
postal code are essential for carriers and tax or compliance checks.

**Independent Test**: Create a location with name, code, street lines, country, and
postal code; confirm all parts appear on the locations page.

**Acceptance Scenarios**:

1. **Given** the administrator is creating a location, **When** they provide a valid
   name, unique code, street or locality text, a recognized country, and a valid postal
   code, **Then** the location is saved and all address parts are visible in the
   location list.
2. **Given** the administrator is creating a location, **When** they leave all address
   fields empty, **Then** the location is still created successfully (address remains
   optional for backward compatibility and quick setup).
3. **Given** the administrator enters a postal code but leaves country empty, **When**
   they submit, **Then** the system rejects the input with a clear message explaining
   that country is required when a postal code is provided.
4. **Given** street text exceeds the maximum allowed length, or postal code fails format
   rules, **When** they submit, **Then** the system rejects the input with a clear
   message and does not save invalid data.

---

### User Story 2 - View Country and Postal Code on the Locations Page (Priority: P1)

As an inventory administrator, I want to see each location’s country and postal code
alongside street details in the location directory so I can distinguish sites in
different regions without opening external systems.

**Why this priority**: Country and postal code are the minimum structured data most
operations teams need for routing and audits.

**Independent Test**: Open the locations page for locations with full, partial, and no
address data; verify display behavior without editing other inventory flows.

**Acceptance Scenarios**:

1. **Given** a location has country and postal code on file, **When** the administrator
   views the locations list, **Then** country and postal code are shown with street or
   locality text (full text or sensibly truncated with full detail on expand or detail
   view).
2. **Given** a location has street text but no country or postal code (legacy record),
   **When** the administrator views the list, **Then** the UI shows available parts and
   indicates which structured fields are missing without treating the location as
   invalid.
3. **Given** a location has no address at all, **When** the administrator views the
   list, **Then** the UI indicates that no address is on file.
4. **Given** multiple locations exist, **When** the list is sorted by name, **Then**
   address display does not break sorting or active/inactive indicators.

---

### User Story 3 - Update or Clear Address Fields on an Existing Location (Priority: P2)

As an inventory administrator, I want to change or remove street, country, or postal
code when a warehouse moves or data was entered incorrectly.

**Why this priority**: Addresses change over time; editable structured fields avoid
stale shipping and receiving instructions.

**Independent Test**: Edit only address fields on an existing location and confirm
name, code, stock balances, and active status are unaffected.

**Acceptance Scenarios**:

1. **Given** an existing location, **When** the administrator updates street, country,
   or postal code and saves, **Then** the new values appear everywhere that location is
   shown.
2. **Given** an existing location with address data, **When** the administrator clears
   all address fields and saves, **Then** the location remains active with no address
   on file.
3. **Given** a location with stock balances, **When** only address fields are edited,
   **Then** stock quantities and deactivation rules are unchanged.
4. **Given** an administrator clears country while postal code remains, **When** they
   save, **Then** the system rejects the save until country is provided or postal code
   is also cleared.

---

### Edge Cases

- What happens when address text contains only whitespace? Treat as empty after
  trimming for street lines; country and postal code fields trimmed the same way.
- How are legacy locations without structured address handled? They continue to work
  for stock movements; missing country or postal code remain blank until an
  administrator adds them.
- Are addresses required for deactivation? No; deactivation rules from
  location-management-ui still apply based on stock only.
- Can street text contain international characters? Yes; validation is length-based for
  street lines, not restricted to ASCII.
- What if an operator selects a country that does not use postal codes in practice?
  Postal code may be left empty when country is set and no postal code was previously
  stored; if postal code is entered, country is required.
- Invalid or unknown country values? Reject with a clear message; only values from the
  supported country list are accepted.
- Is address shown on stock movement dropdowns? Out of scope for v1; selectors
  continue to show name and code only to keep dropdowns compact.
- Are map display and geocoding in scope? No.

## Requirements *(mandatory)*

### User Interface Constraints

- **UI-001**: Address entry MUST appear on the same dedicated locations page used for
  create and edit (not a separate wizard-only flow).
- **UI-002**: Street or locality input MUST support multiple lines in one control so
  operators can paste from existing warehouse records.
- **UI-003**: Country MUST be chosen from a searchable list of recognized countries
  (not free-typed arbitrary text) to reduce errors.
- **UI-004**: Postal code MUST have its own labeled input separate from street lines
  and country.

### Functional Requirements

- **FR-001**: System MUST store optional physical address parts for each storage
  location: multi-line street or locality text, country, and postal code, each
  associated with that location’s stable identifier.
- **FR-002**: System MUST accept all address parts on location create when provided,
  and persist them with the new location.
- **FR-003**: System MUST allow administrators to view street, country, and postal
  code for any location that has them on the location management screen.
- **FR-004**: System MUST allow administrators to update or clear any address part on an
  existing location without changing name, code, or active status unless those fields
  are explicitly edited.
- **FR-005**: System MUST trim leading and trailing whitespace from all address inputs
  before save; whitespace-only values MUST be stored as empty.
- **FR-006**: System MUST enforce a maximum street or locality length (assumed 500
  characters) and return a clear validation error when exceeded.
- **FR-007**: System MUST accept only country values from a maintained list of
  recognized countries (e.g., standard two-letter country codes with display names).
- **FR-008**: System MUST validate postal code format when provided: after trim, length
  between 2 and 20 characters, containing only letters, digits, spaces, and hyphens.
- **FR-009**: System MUST require country whenever a postal code is provided.
- **FR-010**: System MUST allow country without postal code (for regions or legacy data
  where postal code is unknown).
- **FR-011**: System MUST return all address parts when listing or reading locations for
  administration so the UI and integrations display them consistently.
- **FR-012**: Existing locations without address data MUST continue to support all
  current stock-in, stock-out, transfer, and deactivation behavior unchanged.
- **FR-013**: Renaming, code changes, and deactivation rules from location-management-ui
  MUST remain in effect; this feature does not relax duplicate-code or stock guards.

### Key Entities

- **Storage Location** (extended): A named inventory site. Existing attributes:
  human-readable name, unique code, active flag. **Address attributes** (all optional
  unless noted): multi-line street or locality text; country (from recognized list);
  postal code (validated text, required only when paired with cross-field rule FR-009).
- **Country** (reference): A recognized sovereign state or territory used for selection
  and validation, identified by a stable code and human-readable name.
- **Stock Balance** (unchanged): Deactivation and movement rules do not depend on
  whether address parts are present.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Administrators can add street, country, and postal code to a new location
  and see all parts on the locations page in under 2 minutes without leaving the
  inventory section.
- **SC-002**: 100% of invalid postal codes and unrecognized countries are rejected in
  acceptance testing with an understandable message.
- **SC-003**: After updating any address part, the new values are visible on the
  locations page on the next load without manual cache clearing.
- **SC-004**: In acceptance testing, editing address fields alone never changes stock
  quantities or deactivation eligibility for that location.
- **SC-005**: At least 90% of administrators in a brief usability check can identify
  which locations lack country or postal code from the locations list.
- **SC-006**: In acceptance testing, 100% of save attempts with postal code but no
  country are blocked with a clear explanation.

## Assumptions

- Target users are internal inventory/warehouse administrators.
- Street or locality remains a single multi-line text field; city and state are not
  separate required fields in v1 unless operators include them in the street block.
- Country selection uses a standard widely recognized country list; no custom
  user-defined countries in v1.
- Postal code validation is format-based (length and allowed characters), not
  country-specific regex libraries, unless planning adds per-country rules later.
- Address is informational for operations and reporting, not used for geocoding,
  maps, or automated carrier rating in v1.
- All address parts remain optional on create and update so existing seeded locations
  and quick code-only setup remain valid.
- English UI labels are sufficient for v1; address content may be in any language.
- Location management UI and inventory-service location APIs from
  location-management-ui are available; this feature extends those surfaces.
- Role-based access for managing locations is unchanged from location-management-ui.
