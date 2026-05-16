# Feature Specification: Process Payment for Invoice

**Created**: 2026-05-16  
**Status**: migrated  
**Input**: Reverse-engineered from `payment-service/` (distinct from `customer-refund` spec)

## Specification Placement

| Field | Value |
|-------|-------|
| **Module** | payment-service |
| **Domain** | payments |
| **Workflow / capability** | process-payment |

**Note**: `specs/payment-service/refunds/customer-refund/` covers return/refund flows; this spec covers the **original charge** pipeline.

## Cross-Module Scope

| Module | Role |
|--------|------|
| `payment-service/` | ProcessPaymentWorkflow |
| `ui/` | `Payments.tsx` |

## User Scenarios & Testing

### User Story 1 - Charge on invoice created (P1)

When invoice is created, payment is processed and sales notified on success.

**Acceptance Scenarios**:

1. **Given** `InvoiceCreated` event, **When** workflow runs, **Then** payment record created, `PaymentCompleted` to sales.
2. **Given** `simulate_failure` on invoice payload, **When** workflow runs, **Then** payment fails, `PaymentProcessingFailed` to sales.
3. **Given** duplicate correlation, **When** ChargePaymentActivity runs, **Then** idempotent behavior.

### User Story 2 - View/delete payments (P2)

Operator lists payments; delete guarded if order delivered.

## Requirements

- **FR-001**: `ProcessPaymentWorkflow` MUST charge, complete state, clear cache, dispatch `PaymentCompleted`.
- **FR-002**: Simulated gateway failure when `simulate_failure` true.
- **FR-003**: DELETE payment MUST block if related order delivered (sales service check).

## Workflows & Activities

| Workflow | Trigger |
|----------|---------|
| `ProcessPaymentWorkflow` | `InvoiceCreated` |

## API Contract

| Method | Path |
|--------|------|
| GET | `/payments` |
| GET | `/payments/{id}` |
| GET | `/payments/correlation/{correlationId}` |
| DELETE | `/payments/{id}` |

## Success Criteria

- **SC-001**: Successful invoice event yields completed payment and order moves to paid (via sales CompleteOrderWorkflow).

## Assumptions

- Simulated credit card charge — no external PSP integration.
- Refunds are out of scope for this workflow (see customer-refund spec).
