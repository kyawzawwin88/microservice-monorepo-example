# Feature Specification: New Order & Order Lifecycle

**Feature Branch**: *(legacy — pre-spec-kit; no numbered branch)*  
**Created**: 2026-05-16  
**Status**: migrated  
**Input**: Reverse-engineered from `sales-service/` and `ui/src/pages/Orders.tsx`

## Specification Placement *(mandatory)*

| Field | Value |
|-------|-------|
| **Module** | sales-service |
| **Domain** | orders |
| **Workflow / capability** | new-order |
| **Placement** | New workflow folder (legacy core demo) |

## Cross-Module Scope

| Module | Role |
|--------|------|
| `sales-service/` | Workflows, API, event publishing |
| `ui/` | `Orders.tsx`, `Dashboard.tsx` (health + architecture diagram) |

Downstream: `invoice-service` (OrderCreated), `payment-service` (via invoice), `inventory-service` (OrderPaid, OrderDelivered, OrderDeleted).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Create Order (Priority: P1)

As an operator, I want to submit a new order with customer details and line items so the system orchestrates invoice and payment processing.

**Why this priority**: Entry point for the entire event-driven demo pipeline.

**Independent Test**: `POST /api/orders` returns 202 with `correlation_id` and `workflow_id`; order appears in list with status `submitted`.

**Acceptance Scenarios**:

1. **Given** valid payload, **When** POST `/orders`, **Then** `NewOrderWorkflow` starts, order record created (idempotent on `correlation_id`), `OrderCreated` dispatched to invoice queue.
2. **Given** duplicate `correlation_id` replay, **When** workflow re-runs validate step, **Then** existing order returned without duplicate create.
3. **Given** `simulate_failure: true`, **When** order created, **Then** downstream invoice/payment may fail independently (demo).

---

### User Story 2 - Retry Failed Order (Priority: P2)

As an operator, I want to retry a failed order so downstream services resume with idempotency.

**Independent Test**: `POST /orders/{id}/retry` on failed order re-dispatches `OrderCreated` without `simulateFailure`.

**Acceptance Scenarios**:

1. **Given** order `state` is `failed`, **When** retry, **Then** state reset to `requested` then `completed`, event re-published.
2. **Given** order not failed, **When** retry, **Then** 422.

---

### User Story 3 - Deliver Paid Order (Priority: P2)

As an operator, I want to mark a paid order as delivered so reserved inventory is deducted.

**Independent Test**: `PATCH /orders/{id}/deliver` on `status=paid` starts `DeliverOrderWorkflow` and publishes `OrderDelivered`.

**Acceptance Scenarios**:

1. **Given** `status` is `paid`, **When** deliver, **Then** 202 and `OrderDelivered` to inventory.
2. **Given** status not `paid`, **When** deliver, **Then** 422.

---

### User Story 4 - Delete Order (Priority: P3)

As an operator, I want to delete an order and release reserved stock when allowed.

**Independent Test**: `DELETE /orders/{id}` starts `DeleteOrderWorkflow` unless invoice exists.

**Acceptance Scenarios**:

1. **Given** no related invoice, **When** delete, **Then** soft-delete workflow and `OrderDeleted` to inventory.
2. **Given** invoice exists for correlation, **When** delete, **Then** 422.

### Edge Cases

- Invoice service unreachable during delete guard → deletion allowed (fail-open).
- UI polls order list every 3s for ~30s after mutations.
- Inventory items selected on create form; line items stored on order JSON.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST accept orders with `customer_name`, `customer_email`, `total_amount`, `items[]` (product_name, quantity, unit_price).
- **FR-002**: System MUST assign a UUID `correlation_id` per new order request.
- **FR-003**: `NewOrderWorkflow` MUST validate, create order (`submitted`), clear cache, dispatch `OrderCreated` to invoice, mark processing `completed`.
- **FR-004**: `CompleteOrderWorkflow` MUST run on `PaymentCompleted`, set business status `paid`, dispatch `OrderPaid` to inventory.
- **FR-005**: List/show orders MUST support pagination and correlation lookup.
- **FR-006**: Retry MUST only apply to `failed` state orders.
- **FR-007**: Deliver MUST only apply to `paid` business status.
- **FR-008**: Delete MUST block when related invoice exists (HTTP check).

## Workflows & Activities

| Workflow | Trigger | Key activities |
|----------|---------|----------------|
| `NewOrderWorkflow` | POST `/orders` | ValidateOrder, UpdateOrderState, ClearOrderCache, DispatchEventBus |
| `CompleteOrderWorkflow` | `PaymentCompleted` event | UpdateOrderStatus (paid), DispatchEventBus (OrderPaid) |
| `DeliverOrderWorkflow` | PATCH deliver | Update status delivered, DispatchEventBus (OrderDelivered) |
| `DeleteOrderWorkflow` | DELETE order | SoftDeleteOrder, DispatchEventBus (OrderDeleted) |

## API Contract

| Method | Path | Notes |
|--------|------|-------|
| GET | `/orders` | Paginated list |
| POST | `/orders` | 202, starts NewOrderWorkflow |
| GET | `/orders/{id}` | Single order |
| GET | `/orders/correlation/{correlationId}` | By correlation |
| POST | `/orders/{id}/retry` | Failed orders only |
| PATCH | `/orders/{id}/deliver` | Paid orders only |
| DELETE | `/orders/{id}` | With invoice guard |

## Success Criteria *(mandatory)*

- **SC-001**: Operator can create an order from UI and see it in the list within polling window.
- **SC-002**: End-to-end happy path reaches `paid` after payment event (with downstream services running).
- **SC-003**: Failed orders can be retried without duplicate order rows (idempotent downstream).

## Assumptions

- Event bus (shared MySQL queue) and all four services are running via Docker Compose.
- Business `status` (submitted/paid/delivered) is separate from Spatie `state` (requested/completed/failed).
- Stock is **not** reserved at order creation; reservation occurs on `OrderPaid` (inventory spec).
