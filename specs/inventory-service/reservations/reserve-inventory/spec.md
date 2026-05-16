# Feature Specification: Inventory Reservation (Event-Driven)

**Created**: 2026-05-16  
**Status**: migrated  
**Input**: Reverse-engineered from `inventory-service/` workflows and activities

## Specification Placement

| Field | Value |
|-------|-------|
| **Module** | inventory-service |
| **Domain** | reservations |
| **Workflow / capability** | reserve-inventory |

**Relationship to other specs**: `variation-stock-tracking` covers HTTP stock-in/out/transfer and variation balances. **This spec** covers event-driven reservation lifecycle tied to orders.

## User Scenarios & Testing

### User Story 1 - Reserve on payment (P1)

When order is paid, stock is reserved idempotently per `correlation_id`.

**Acceptance Scenarios**:

1. **Given** `OrderPaid` event, **When** `HandleOrderPaid` runs, **Then** `ReserveInventoryWorkflow` starts (skip if reservation exists).
2. **Given** insufficient stock, **When** CheckStockActivity runs, **Then** workflow fails, reservation marked failed.
3. **Given** `OrderCreated` only, **When** inventory receives it, **Then** no reservation (logging only).

### User Story 2 - Deduct on delivery (P1)

When order delivered, reserved quantities are deducted from on-hand stock.

**Acceptance Scenarios**:

1. **Given** `OrderDelivered`, **When** `DeductInventoryWorkflow` runs, **Then** `DeductStockActivity` reduces stock for line items.

### User Story 3 - Release on order delete (P2)

When order deleted, reserved stock is released.

**Acceptance Scenarios**:

1. **Given** `OrderDeleted`, **When** `ReleaseInventoryWorkflow` runs, **Then** `ReleaseStockActivity` restores available stock.

## Requirements

- **FR-001**: Reservation MUST NOT occur at order creation — only after `OrderPaid`.
- **FR-002**: Activities MUST be idempotent on `correlation_id`.
- **FR-003**: `CheckStockActivity` / `DeductStockActivity` / `ReleaseStockActivity` support variation-aware stock where implemented.
- **FR-004**: HTTP `/reservations` endpoints expose reservation list and correlation lookup.

## Workflows & Activities

| Workflow | Trigger | Activities |
|----------|---------|------------|
| `ReserveInventoryWorkflow` | `OrderPaid` | CheckStock, UpdateInventoryState, ClearInventoryCache |
| `DeductInventoryWorkflow` | `OrderDelivered` | DeductStock, ClearInventoryCache |
| `ReleaseInventoryWorkflow` | `OrderDeleted` | ReleaseStock, ClearInventoryCache |

## API Contract

| Method | Path |
|--------|------|
| GET | `/reservations` |
| GET | `/reservations/correlation/{correlationId}` |

## Success Criteria

- **SC-001**: Paid order reduces available stock via reservation without double-reserve on event redelivery.
- **SC-002**: Delivered order deducts reserved stock; deleted order releases it.

## Assumptions

- Line items on order JSON match inventory item identifiers used by activities.
- Manual stock movements (HTTP) and reservation workflows share underlying stock models.
