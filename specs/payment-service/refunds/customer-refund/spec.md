# Feature Specification: Customer Refund on Returned Items

**Feature Branch**: `002-customer-refund`  
**Created**: 2026-05-16  
**Status**: Draft  
**Input**: task-98722 — Enhance the payment service to support customer refund
workflows when purchased items are returned. Eligible returns trigger refund
requests after validation of the original payment, refundable amount calculation,
and processing back to the original payment method where supported. Track refund
status, support partial refunds, prevent duplicate refunds per returned item, and
update order/payment records after success or failure.

## Specification Placement *(mandatory)*

Per project constitution, this spec MUST live at:

`specs/payment-service/refunds/customer-refund/spec.md`

| Field | Value |
|-------|-------|
| **Module** | payment-service |
| **Domain** | refunds |
| **Workflow / capability** | customer-refund |

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Refund After Return Approval (Priority: P1)

As a customer service agent, I want a refund to be initiated only after a
returned item has been physically received and approved so customers are not
refunded before the business accepts the return.

**Why this priority**: Refund-before-receipt is a financial and fraud risk;
stakeholders explicitly require receive-and-approve gating.

**Independent Test**: Attempt refund with return status "pending receipt" (must
fail); approve return (must allow refund request creation).

**Acceptance Scenarios**:

1. **Given** a return request in "submitted" or "in transit" status, **When** a
   refund is requested, **Then** the system rejects the request with a clear
   reason that the return is not yet received and approved.
2. **Given** a return marked "received and approved" for one or more line items,
   **When** the agent initiates a refund, **Then** the system accepts the refund
   request and records it with status "pending" or equivalent initial state.
3. **Given** a return approval is revoked before refund processing completes,
   **When** processing runs, **Then** the refund is cancelled or blocked with an
   auditable reason.

---

### User Story 2 - Validate Payment and Calculate Refund Amount (Priority: P1)

As a finance operator, I want the system to validate the original payment
transaction and compute the correct refundable amount so refunds match what the
customer paid for the returned goods.

**Why this priority**: Incorrect amounts create reconciliation failures and
customer disputes.

**Independent Test**: For a completed payment of $100 for two items, approve
return of one item priced $40; confirm refundable amount is $40 (plus applicable
tax/shipping rules per policy) and original payment is located.

**Acceptance Scenarios**:

1. **Given** a completed original payment exists for the order, **When** a refund
   is calculated for approved returned items, **Then** the refundable amount
   equals the sum of eligible returned line amounts per business rules (item
   price, discounts, tax, fees as defined in policy).
2. **Given** no matching completed payment, **When** a refund is requested,
   **Then** the system rejects the request and does not create a payable refund.
3. **Given** a partial return (some quantity or subset of lines), **When** refund
   is calculated, **Then** only the returned portion is included in the refundable
   amount (partial refund).
4. **Given** a full return of all items on the order, **When** refund is
   calculated, **Then** the refundable amount equals the remaining refundable
   balance on that payment (full refund).

---

### User Story 3 - Process Refund to Original Payment Method (Priority: P1)

As a customer, I want my refund sent back through my original payment method when
supported so I receive funds in the same way I paid.

**Why this priority**: Core customer expectation and card-network best practice.

**Independent Test**: Complete refund for a card payment; confirm gateway refund
reference, customer notification (if in scope), and payment record shows refunded
portion.

**Acceptance Scenarios**:

1. **Given** original payment method supports automated refund, **When** refund
   processing runs, **Then** funds are requested from the payment provider to the
   original method and refund status moves to "completed" on provider success.
2. **Given** original payment method does not support automated refund, **When**
   refund is approved, **Then** the system marks refund as requiring manual
   settlement and records instructions for operators without marking completed
   prematurely.
3. **Given** provider returns a transient failure, **When** retry policy allows,
   **Then** the system retries with idempotency and does not double-refund on
   success.
4. **Given** provider returns a permanent failure, **When** processing ends,
   **Then** refund status is "failed" with reason, order/payment records reflect
   failure, and operators can see audit details.

---

### User Story 4 - Prevent Duplicate Refunds (Priority: P1)

As a finance operator, I want the system to block duplicate refunds for the same
returned item so we do not pay customers twice for one return.

**Why this priority**: Duplicate refunds are high-severity financial errors;
explicit test coverage required per review.

**Independent Test**: Complete refund for return line A; attempt second refund for
same line A; confirm rejection. Attempt refund for different line B on same
order; confirm allowed if eligible.

**Acceptance Scenarios**:

1. **Given** a successful refund already recorded for a specific returned item
   (or return line identifier), **When** another refund is requested for that
   same item, **Then** the system rejects with duplicate-refund error.
2. **Given** a failed refund for a returned item, **When** a new refund is
   requested with the same idempotency key or return line reference, **Then**
   the system either retries the same logical refund or rejects duplicate per
   policy, but never creates two successful refunds for one item.
3. **Given** partial refunds on the same order for different return lines,
   **When** each line is refunded once, **Then** cumulative refunded amount never
   exceeds the original payment refundable balance.

---

### User Story 5 - Refund Status Tracking and Record Updates (Priority: P2)

As a customer service agent, I want to see refund status and updated order/payment
records so I can answer customer inquiries and reconcile accounts.

**Why this priority**: Operational visibility after processing mechanics exist.

**Independent Test**: Trigger refund; observe status transitions pending →
processing → completed/failed; verify order and payment views show refunded
amount and status.

**Acceptance Scenarios**:

1. **Given** a refund request, **When** processing progresses, **Then** status
   is visible as one of: pending, processing, completed, failed, or
   manual-pending (unsupported method).
2. **Given** successful refund, **When** viewing the original payment record,
   **Then** refunded amount and remaining balance are shown and order status
   reflects refund completion per integration contract.
3. **Given** failed refund, **When** viewing records, **Then** failure reason is
   stored and order/payment states are not incorrectly marked as fully refunded.
4. **Given** any refund state change, **When** audit is reviewed, **Then** an
   immutable audit entry exists with actor, timestamp, amount, return reference,
   and outcome.

---

### Edge Cases

- What if return is approved for more quantity than was purchased? Validation
  MUST reject over-refund quantities.
- What if currency or amount rounding differs between order and payment? Refund
  amount MUST use the payment's currency and consistent rounding rules.
- What if original payment is still pending or failed? Refund MUST NOT proceed;
  only completed/settled payments are refundable.
- What if multiple payments exist for one order? Refund MUST attach to the
  payment that funded the returned lines (or explicit payment selection rule).
- What if customer received partial refund earlier? Remaining refundable balance
  MUST decrease accordingly.
- Concurrent duplicate requests for same return line? Only one successful refund
  MUST win; others MUST fail safely (idempotency).
- Chargeback already filed on transaction? Refund request MUST be blocked or
  flagged for manual review (assumption: out of automated path until reviewed).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST allow refund requests only when the linked return is
  in "received and approved" status (or equivalent business-approved state).
- **FR-002**: System MUST validate that an original payment transaction exists,
  is in a refundable completed state, and matches the order/return context.
- **FR-003**: System MUST calculate refundable amount from approved returned
  items, supporting partial refunds (subset of lines or partial quantities) and
  full refunds (all remaining eligible balance).
- **FR-004**: System MUST process refunds through the original payment method
  when the provider supports it; otherwise MUST route to manual settlement
  workflow without falsely marking automated completion.
- **FR-005**: System MUST track refund lifecycle status (pending, processing,
  completed, failed, manual-pending) visible to authorized operators.
- **FR-006**: System MUST prevent duplicate successful refunds for the same
  returned item or return line identifier.
- **FR-007**: System MUST enforce that cumulative refunds for a payment never
  exceed the original refundable balance.
- **FR-008**: System MUST use idempotent refund requests so retries do not
  double-pay the customer.
- **FR-009**: System MUST update payment records with refunded amounts and
  outcomes after success or failure.
- **FR-010**: System MUST notify or emit events to update order status in the
  sales/order domain after refund success or failure per integration contract.
- **FR-011**: System MUST maintain audit logs for refund initiation, amount
  calculation, provider requests/responses, status changes, and outcomes.
- **FR-012**: System MUST support operator-initiated retry of failed refunds
  where provider policy allows, without violating duplicate-refund rules.

### Key Entities

- **Return**: Customer return request with line items, quantities, and approval
  status (received, approved, rejected).
- **Original Payment**: Completed charge tied to an order with amount, method,
  provider reference, and refundable balance.
- **Refund Request**: Business intent to refund specific return line(s) with
  calculated amount and idempotency key.
- **Refund Transaction**: Provider-facing refund attempt with status, reference,
  and failure reason.
- **Audit Log Entry**: Immutable record of refund-related actions for compliance
  and troubleshooting.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of refund attempts in acceptance tests with non-approved
  returns are rejected before any provider call.
- **SC-002**: 100% of duplicate refund attempts for the same return line after
  a successful refund are blocked in acceptance tests.
- **SC-003**: Partial and full refund test scenarios (per review checklist) pass
  with calculated amounts matching policy within $0.01 tolerance per currency.
- **SC-004**: Failed refund scenarios leave payment/order records consistent
  (no "completed" refund status without provider confirmation) in 100% of tests.
- **SC-005**: Operators can determine refund status and last update time within
  one screen or report view without external tools for 95% of inquiries.
- **SC-006**: Audit trail contains sufficient detail to reconstruct each refund
  decision for sample regulatory review (actor, time, amount, return ref, outcome)
  for 100% of processed refunds in test environment.

## Assumptions

- Return "received and approved" status is provided by the sales/order service
  (event or API); payment service does not own warehouse receipt workflows.
- Refundable amount policy follows returned line subtotals; tax and shipping
  refunds follow existing order policy defaults unless overridden in a later
  policy spec.
- Original payment method refund is preferred; manual fallback is acceptable for
  unsupported methods with operator visibility.
- Payment gateway integration details are defined in planning; this spec defines
  required behaviors only.
- Currency is single-currency per payment; multi-currency is out of scope for v1.
- Customer-facing notifications are optional for v1; operator visibility is
  required.
