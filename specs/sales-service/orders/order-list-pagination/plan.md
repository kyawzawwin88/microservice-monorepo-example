# Implementation Plan: Order List Pagination

**Branch**: `008-order-list-pagination` | **Date**: 2026-05-17 | **Spec**: [spec.md](./spec.md)  
**Status**: ready

## Summary

Harden paginated order listing for operators: backend honors and validates `page`, clamps out-of-range requests; UI keeps page context on refresh/poll/mutations and jumps to page 1 only after create.

## Technical Context

**Language/Version**: PHP 8.2+ (sales-service), TypeScript (ui)  
**Storage**: MySQL `sales_db.orders` — no schema changes  
**Testing**: PHPUnit feature tests (sales-service bootstrap); existing Playwright QA under `new-order/qa`  
**Project Type**: Cross-module (sales API + Orders UI)

## Constitution Check

- [x] **Module**: `sales-service` primary; `ui/` secondary (Cross-Module Scope in spec)
- [x] **Spec path**: `specs/sales-service/orders/order-list-pagination/`
- [x] **Extend related workflow**: Complements `orders/new-order` list behavior (FR-005)
- [x] **Stack**: Laravel + React; HTTP via `/svc/sales`

## Project Structure

```text
sales-service/
├── app/Http/Controllers/OrderController.php   # index: validate page, clamp
└── tests/Feature/OrderListPaginationTest.php  # new

ui/src/pages/
└── Orders.tsx                                 # page sync, poll on current page
```

## Complexity Tracking

| Item | Notes |
|------|-------|
| Brownfield | Pagination UI/API largely exist; this plan closes spec gaps (poll page, out-of-range, tests) |
