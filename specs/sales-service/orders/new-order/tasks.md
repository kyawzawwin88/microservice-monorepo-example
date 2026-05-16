# Tasks: New Order & Order Lifecycle

**Status**: migrated — all tasks reflect **completed** legacy implementation.

## Phase 1: Core workflows & API

- [x] T001 `NewOrderWorkflow` — validate, create order, dispatch OrderCreated
- [x] T002 `OrderController@store` — validation, UUID correlation, 202 response
- [x] T003 `CompleteOrderWorkflow` — payment listener → status paid → OrderPaid event
- [x] T004 `DeliverOrderWorkflow` + PATCH `/orders/{id}/deliver`
- [x] T005 `DeleteOrderWorkflow` + DELETE with invoice guard
- [x] T006 `OrderController@retry` — failed order recovery
- [x] T007 List/show/correlation routes

## Phase 2: Event listeners

- [x] T008 `HandlePaymentCompleted` → CompleteOrderWorkflow
- [x] T009 `HandleInvoiceCreationFailed` / `HandlePaymentProcessingFailed` → order failed state
- [x] T010 `LogIncomingEvent` + EventLog model

## Phase 3: UI (`ui/`)

- [x] T011 `Orders.tsx` — create form, list, deliver, retry, delete, polling
- [x] T012 `api/sales.ts` client
- [x] T013 `Dashboard.tsx` — service health checks

## Gaps

- [ ] No PHPUnit tests in `sales-service/`
- [ ] No contract tests for event payloads
- [ ] Delete invoice guard uses blocking `file_get_contents` — fragile if invoice URL wrong
