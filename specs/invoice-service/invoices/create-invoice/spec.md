# Feature Specification: Create Invoice from Order

**Created**: 2026-05-16  
**Status**: migrated  
**Input**: Reverse-engineered from `invoice-service/`

## Specification Placement

| Field | Value |
|-------|-------|
| **Module** | invoice-service |
| **Domain** | invoices |
| **Workflow / capability** | create-invoice |

## Cross-Module Scope

| Module | Role |
|--------|------|
| `invoice-service/` | Workflow + read/delete API |
| `ui/` | `Invoices.tsx` — list, delete, refresh |

Triggered by `OrderCreated` from sales; publishes `InvoiceCreated` to payment.

## User Scenarios & Testing

### User Story 1 - Auto-generate invoice (P1)

When an order is created, an invoice is generated idempotently for the same `correlation_id`.

**Acceptance Scenarios**:

1. **Given** `OrderCreated` on event bus, **When** listener runs, **Then** `CreateInvoiceWorkflow` starts.
2. **Given** invoice already exists for correlation, **When** workflow runs, **Then** idempotent skip (via GenerateInvoiceActivity).
3. **Given** `simulate_failure` on order, **When** random roll ≤5/10, **Then** invoice fails and `InvoiceCreationFailed` sent to sales.

### User Story 2 - View and delete invoices (P2)

Operator lists invoices and may soft-delete if no blocking payment.

**Acceptance Scenarios**:

1. **Given** paginated GET `/invoices`, **When** UI loads, **Then** invoices displayed with state badges.
2. **Given** related payment exists, **When** delete attempted, **Then** guard returns error (payment service check).

## Requirements

- **FR-001**: `CreateInvoiceWorkflow` MUST generate invoice, complete state, clear cache, dispatch `InvoiceCreated` to payment queue.
- **FR-002**: Failures MUST mark invoice failed and notify sales via `InvoiceCreationFailed`.
- **FR-003**: GET by id and correlation MUST be supported.
- **FR-004**: Invoice number and line items derived from order payload.

## Workflows & Activities

| Workflow | Trigger | Activities |
|----------|---------|------------|
| `CreateInvoiceWorkflow` | `OrderCreated` → `HandleOrderCreated` | GenerateInvoice, UpdateInvoiceState, ClearInvoiceCache, DispatchEventBus |

## API Contract

| Method | Path |
|--------|------|
| GET | `/invoices` |
| GET | `/invoices/{id}` |
| GET | `/invoices/correlation/{correlationId}` |
| DELETE | `/invoices/{id}` |

## Success Criteria

- **SC-001**: Every successful order creation produces at most one invoice per `correlation_id`.
- **SC-002**: UI shows invoice within refresh after order pipeline runs.

## Assumptions

- No manual invoice creation API — event-driven only.
- Failure simulation is demo-only (50% random in invoice when flag set).
