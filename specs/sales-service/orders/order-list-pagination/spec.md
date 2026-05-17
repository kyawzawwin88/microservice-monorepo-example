# Feature Specification: Order List Pagination

**Feature Branch**: `008-order-list-pagination`  
**Created**: 2026-05-17  
**Status**: Draft  
**Input**: User description: "enable pagination in order listing"

## Specification Placement *(mandatory)*

| Field | Value |
|-------|-------|
| **Module** | sales-service |
| **Domain** | orders |
| **Workflow / capability** | order-list-pagination |
| **Placement** | New workflow folder (enhancement to order listing; complements `specs/sales-service/orders/new-order/`) |

### When to create vs extend

The core order lifecycle is documented in `specs/sales-service/orders/new-order/spec.md` (FR-005 mentions paginated list). This workflow captures **operator-facing list navigation** when the order volume exceeds one page—behavior that must stay consistent with other paginated demo screens (Invoices, Payments, Event Logs).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Browse orders across pages (Priority: P1)

As an operator, I want to move between pages of the order list so I can find and act on orders when there are more than fit on one screen.

**Why this priority**: Without reliable pagination, large demo datasets make the Orders screen unusable.

**Independent Test**: Seed more than one page of orders, open the Orders screen, use pagination controls, and confirm each page shows a different set of rows with correct page indicators.

**Acceptance Scenarios**:

1. **Given** more orders exist than fit on one page, **When** the operator opens the Orders screen, **Then** the first page of orders is shown (newest first) and pagination controls indicate there are additional pages.
2. **Given** the operator is on page 1, **When** they go to the next page, **Then** a different set of orders is displayed and the current page indicator updates.
3. **Given** the operator is on page 2 or later, **When** they go to the previous page, **Then** the prior page of orders is displayed.
4. **Given** only one page of orders exists, **When** the operator views the list, **Then** pagination controls are not shown (no empty or confusing pager).

---

### User Story 2 - See new orders after creation (Priority: P1)

As an operator, I want newly created orders to appear in the list without manual hunting across pages so I can confirm the workflow started.

**Why this priority**: Order creation is the primary action on this screen; pagination must not hide success feedback.

**Independent Test**: From page 2+, create an order and verify the new order is visible on the first page with success messaging.

**Acceptance Scenarios**:

1. **Given** the operator is viewing page 2 or higher, **When** they successfully create an order, **Then** the list navigates to page 1 and the new order appears among the most recent entries.
2. **Given** auto-refresh runs after create/retry/deliver/delete, **When** updates occur, **Then** the list refreshes the **current** page without jumping pages unexpectedly (except the post-create jump to page 1 in scenario 1).

---

### User Story 3 - Refresh and stay oriented (Priority: P2)

As an operator, I want to manually refresh the list and still know which page I am on so I can reconcile UI state with backend data during demos.

**Why this priority**: Supports debugging and live demos when workflows update order state asynchronously.

**Independent Test**: On page 2, click Refresh; page number and row set remain consistent unless total pages shrank below current page.

**Acceptance Scenarios**:

1. **Given** the operator is on page N, **When** they click Refresh, **Then** page N is reloaded with up-to-date order data.
2. **Given** orders were deleted such that fewer pages remain, **When** the operator refreshes while on a page that no longer exists, **Then** they are shown the last valid page (not an empty list with no explanation).

---

### Edge Cases

- **Empty list**: No pagination controls; clear empty state message.
- **Exactly one full page**: Controls hidden; all orders visible.
- **Page out of range** (e.g., bookmarked `?page=99`): System shows the last valid page or page 1 with correct data—never a permanent blank table.
- **Concurrent state updates** during auto-poll: Rows on the current page update in place; page count may increase when new orders are added on page 1.
- **Actions on a row** (retry, deliver, delete): After success, current page refreshes; if the row disappears (delete), remaining rows on the page fill the view without breaking the pager.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The order list MUST return results in pages ordered by creation time (newest first).
- **FR-002**: Each page MUST contain a fixed maximum number of orders (default: 20 per page, consistent with other service list endpoints in this application).
- **FR-003**: The list response MUST include enough metadata for the client to know the current page, total pages, total order count, and items on the current page.
- **FR-004**: The Orders screen MUST request a specific page when loading or changing pages.
- **FR-005**: The Orders screen MUST provide Previous and Next navigation (and a readable “page X of Y” indicator when multiple pages exist), matching the interaction pattern used on Invoices and Payments.
- **FR-006**: Pagination controls MUST be disabled appropriately on the first and last page (no forward on last page, no back on first page).
- **FR-007**: After a successful order creation, the UI MUST show page 1 so the new order is visible without the operator changing pages manually.
- **FR-008**: Manual Refresh MUST reload the currently selected page unless an edge case forces correction to the last valid page (see edge cases).
- **FR-009**: Auto-refresh after order mutations MUST refresh the currently viewed page (except the post-create navigation to page 1).
- **FR-010**: When total pages is 1 or less, pagination controls MUST NOT be displayed.

### Key Entities

- **Order (list item)**: Customer name, email, status/state, correlation identifier, totals, and timestamps—summarized for table display.
- **Order list page**: A numbered slice of orders plus pagination metadata (current page, last page, total count, page size).

## API Contract *(include when exposing or changing HTTP APIs)*

| Method | Path | Request | Response | Errors |
|--------|------|---------|----------|--------|
| GET | `/orders` | Query: `page` (optional, positive integer, default 1) | Paginated collection: items for the requested page plus `current_page`, `last_page`, `per_page`, `total` | 422 if `page` is invalid |

## Cross-Module Scope *(include when both ui/ and *-service/ change)*

| Module | Changes |
|--------|---------|
| `sales-service/` | Ensure list endpoint honors `page` query and returns standard pagination metadata |
| `ui/` | Orders list page: page state, controls, post-create navigation, refresh/poll behavior |

- Primary spec module: sales-service (owns orders API)
- Integration: HTTP via `/svc/sales` proxy; client in `ui/src/api/sales.ts`

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: With 25+ orders in the system, 100% of test operators can reach any order by at most ⌈total ÷ page size⌉ page clicks from the Orders screen.
- **SC-002**: After creating an order from any page, operators see the new order on the first page within 5 seconds without manual page navigation.
- **SC-003**: Page transitions complete with updated data visible in under 2 seconds under normal demo load (single operator, typical development environment).
- **SC-004**: Pagination behavior on Orders is consistent with Invoices and Payments (same control labels, disabled states, and hide-when-single-page rule).

## Assumptions

- Default page size remains 20 orders, aligned with existing sales list endpoints unless a future spec changes global list sizing.
- Operators are internal demo users; no role-based filtering of which orders appear in the list.
- Sort order stays newest-first; filtering and search are out of scope for this workflow.
- Mobile layout may use simplified prev/next controls; full “page X of Y” may be desktop-only, consistent with the shared Pagination component pattern.
- Authentication and authorization are unchanged from the existing Orders feature.
