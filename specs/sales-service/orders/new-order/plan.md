# Implementation Plan: New Order & Order Lifecycle

**Branch**: *(legacy)* | **Date**: 2026-05-16 | **Spec**: [spec.md](./spec.md)  
**Status**: migrated (reverse-engineered)

## Summary

Sales service is the **orchestration entry point** for the demo monorepo. HTTP API creates orders via durable workflows; events fan out to invoice, payment, and inventory. UI provides order CRUD-style operations with polling.

## Technical Context

**Language/Version**: PHP 8.2+, Laravel 11  
**Primary Dependencies**: laravel-workflow, Spatie model states, database event bus  
**Storage**: MySQL `sales_db` — `orders`, `event_logs`  
**Testing**: Not present in `sales-service/` at migration time  
**Target Platform**: Docker Compose :8001  
**Project Type**: Event-driven microservice  

## Constitution Check

- [x] **Module**: `sales-service` (UI cross-module documented)
- [x] **Spec path**: `specs/sales-service/orders/new-order/`
- [x] **Spec reuse**: New folder — distinct from payment/invoice/inventory specs
- [x] **Single responsibility**: One activity per class under `app/Activities/`
- [ ] **Unit tests**: None migrated — gap documented
- [x] **Stack**: Laravel only in this module

## Project Structure

```text
sales-service/
├── app/
│   ├── Http/Controllers/OrderController.php
│   ├── Workflows/
│   │   ├── NewOrderWorkflow.php
│   │   ├── CompleteOrderWorkflow.php
│   │   ├── DeliverOrderWorkflow.php
│   │   └── DeleteOrderWorkflow.php
│   ├── Activities/
│   ├── Events/
│   ├── Listeners/
│   └── Models/Order.php
├── routes/api.php
└── (no tests/)

ui/src/
├── pages/Orders.tsx
├── pages/Dashboard.tsx
└── api/sales.ts
```

**Structure Decision**: Activities-based orchestration (no `app/Services/*Action` in sales-service). ~36 PHP files in `app/`.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Cross-module UI | Operator creates/views orders in browser | API-only insufficient for demo UX |
