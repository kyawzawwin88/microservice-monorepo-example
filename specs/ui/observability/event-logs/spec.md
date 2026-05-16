# Feature Specification: Cross-Service Event Logs Viewer

**Created**: 2026-05-16  
**Status**: migrated  
**Input**: Reverse-engineered from `ui/src/pages/EventLogs.tsx` + `EventLogController` in each service

## Specification Placement

| Field | Value |
|-------|-------|
| **Module** | ui |
| **Domain** | observability |
| **Workflow / capability** | event-logs |

## Cross-Module Scope

Reads `GET /event-logs` from sales, invoice, payment, inventory (paginated, 50 per page on backend).

## User Scenarios & Testing

### User Story 1 - Inspect raw events per service (P1)

As an operator, I want to switch between services and view logged event payloads for debugging.

**Acceptance Scenarios**:

1. **Given** service tab selected, **When** page loads, **Then** paginated logs displayed (event name, payload, timestamp).
2. **Given** tab change, **When** user switches service, **Then** page resets to 1 and fetches that service's logs.
3. **Given** API error, **When** fetch fails, **Then** error banner shown.

## Requirements

- **FR-001**: UI MUST support four service tabs: sales, invoice, payment, inventory.
- **FR-002**: Each backend MUST persist incoming events to `event_logs` (via `LogIncomingEvent` listeners).
- **FR-003**: Refresh button re-fetches current page.

## API Contract

| Service | Path |
|---------|------|
| All | `GET /event-logs` (paginated) |

## Success Criteria

- **SC-001**: Operator can trace `OrderCreated` / `InvoiceCreated` payloads across services during demo.

## Assumptions

- Logs may contain PII (customer email) — demo environment only.
