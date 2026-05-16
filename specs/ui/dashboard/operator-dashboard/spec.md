# Feature Specification: Operator Dashboard

**Created**: 2026-05-16  
**Status**: migrated  
**Input**: Reverse-engineered from `ui/src/pages/Dashboard.tsx`

## Specification Placement

| Field | Value |
|-------|-------|
| **Module** | ui |
| **Domain** | dashboard |
| **Workflow / capability** | operator-dashboard |

## User Scenarios & Testing

### User Story 1 - Service health overview (P1)

As an operator, I want to see whether all four microservices are reachable.

**Acceptance Scenarios**:

1. **Given** dashboard loads, **When** health checks run, **Then** each service shows up/down with timestamp or error.
2. **Given** service down, **When** displayed, **Then** error message shown.

### User Story 2 - Architecture reference (P2)

Dashboard includes visual diagram of order → invoice → payment → inventory flow for training/demo.

## Requirements

- **FR-001**: Dashboard MUST call `/health` on each service via `salesApi`, `invoiceApi`, `paymentApi`, `inventoryApi`.
- **FR-002**: Route `/` MUST render Dashboard in `App.tsx`.
- **FR-003**: No mutations — read-only observability page.

## Cross-Module Scope

Read-only HTTP to all four backend health endpoints via Vite proxy.

## Success Criteria

- **SC-001**: Operator identifies unhealthy service within one page load.

## Assumptions

- Health endpoints return JSON with status field (per service implementation).
- Large inline diagram is acceptable for demo (no separate diagram asset).
