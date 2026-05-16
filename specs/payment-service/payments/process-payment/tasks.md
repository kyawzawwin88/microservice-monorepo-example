# Tasks: Process Payment for Invoice

**Status**: migrated — all complete.

- [x] T001 `ProcessPaymentWorkflow`
- [x] T002 `ChargePaymentActivity`
- [x] T003 Listener for InvoiceCreated
- [x] T004 Success → PaymentCompleted to sales
- [x] T005 Failure → PaymentProcessingFailed to sales
- [x] T006 PaymentController CRUD-ish API
- [x] T007 Payments.tsx UI

## Gaps

- [ ] No PHPUnit tests
- [ ] Overlaps conceptually with customer-refund — keep specs separate per domain
