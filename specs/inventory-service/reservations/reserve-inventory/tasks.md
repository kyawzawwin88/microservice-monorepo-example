# Tasks: Inventory Reservation (Event-Driven)

**Status**: migrated — all complete.

- [x] T001 `ReserveInventoryWorkflow` + `HandleOrderPaid` idempotency guard
- [x] T002 `CheckStockActivity` / `UpdateInventoryStateActivity`
- [x] T003 `DeductInventoryWorkflow` + `HandleOrderDelivered`
- [x] T004 `ReleaseInventoryWorkflow` + `HandleOrderDeleted`
- [x] T005 `HandleOrderCreated` — no-op reservation (documented)
- [x] T006 Reservation HTTP index + correlation routes
- [x] T007 Unit tests for Check/Deduct/Release activities (partial)

## Gaps

- [ ] No Feature tests for full workflow replay scenarios
- [ ] ReserveInventoryWorkflow doc comment still says "OrderCreated" — code uses OrderPaid (doc drift in source)
