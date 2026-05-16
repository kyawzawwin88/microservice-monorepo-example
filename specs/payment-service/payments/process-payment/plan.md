# Implementation Plan: Process Payment for Invoice

**Date**: 2026-05-16 | **Status**: migrated | **Spec**: [spec.md](./spec.md)

## Summary

Payment service consumes `InvoiceCreated`, runs `ProcessPaymentWorkflow`, publishes `PaymentCompleted` or failure events to sales.

## Technical Context

PHP 8.2+, Laravel 11, MySQL `payment_db`, port :8003. No tests at migration.

## Constitution Check

- [x] Module: payment-service
- [x] Separate workflow from `refunds/customer-refund`
- [ ] Unit tests — gap

## Project Structure

```text
payment-service/
├── app/Workflows/ProcessPaymentWorkflow.php
├── app/Listeners/HandleInvoiceCreated.php
├── app/Http/Controllers/PaymentController.php
ui/src/pages/Payments.tsx
```
