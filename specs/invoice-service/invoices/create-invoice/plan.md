# Implementation Plan: Create Invoice from Order

**Date**: 2026-05-16 | **Spec**: [spec.md](./spec.md) | **Status**: migrated

## Summary

Invoice service listens for `OrderCreated`, runs `CreateInvoiceWorkflow`, and forwards `InvoiceCreated` to payment. Read-only HTTP API plus soft delete for operators.

## Technical Context

**Language/Version**: PHP 8.2+, Laravel 11  
**Storage**: MySQL `invoice_db`  
**Testing**: None in `invoice-service/` at migration  
**Port**: 8002  

## Constitution Check

- [x] Module: `invoice-service`
- [x] Spec path correct
- [ ] Unit tests — gap

## Project Structure

```text
invoice-service/
├── app/Workflows/CreateInvoiceWorkflow.php
├── app/Activities/{Generate,Update,Clear,Dispatch}*
├── app/Listeners/HandleOrderCreated.php
├── app/Http/Controllers/InvoiceController.php
└── routes/api.php

ui/src/pages/Invoices.tsx
```
