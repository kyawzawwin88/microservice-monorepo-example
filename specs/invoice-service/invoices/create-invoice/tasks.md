# Tasks: Create Invoice from Order

**Status**: migrated — all complete.

- [x] T001 `CreateInvoiceWorkflow` with failure simulation branch
- [x] T002 `GenerateInvoiceActivity` — idempotent create
- [x] T003 `HandleOrderCreated` listener → workflow start
- [x] T004 Dispatch `InvoiceCreated` to payment queue
- [x] T005 Failure path → `InvoiceCreationFailed` to sales
- [x] T006 `InvoiceController` index/show/destroy/correlation
- [x] T007 `Invoices.tsx` + `api/invoices.ts`

## Gaps

- [ ] No PHPUnit tests
- [ ] No OpenAPI contract file under specs
