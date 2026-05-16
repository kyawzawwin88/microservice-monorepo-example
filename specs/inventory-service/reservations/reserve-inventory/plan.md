# Implementation Plan: Inventory Reservation (Event-Driven)

**Date**: 2026-05-16 | **Status**: migrated | **Spec**: [spec.md](./spec.md)

## Summary

Three durable workflows implement reserve → deduct → release driven by sales events. Complements HTTP stock APIs in `variation-stock-tracking`.

## Technical Context

PHP 8.2+, Laravel 11, MySQL `inventory_db`. **Tests exist** for stock activities (`inventory-service/tests/Unit/Activities/*`).

## Constitution Check

- [x] Module: inventory-service
- [x] Distinct domain from `inventory-items` and `storage-locations`
- [x] Some unit tests for Activities (partial coverage)

## Project Structure

```text
inventory-service/
├── app/Workflows/ReserveInventoryWorkflow.php
├── app/Workflows/DeductInventoryWorkflow.php
├── app/Workflows/ReleaseInventoryWorkflow.php
├── app/Activities/CheckStockActivity.php
├── app/Activities/DeductStockActivity.php
├── app/Activities/ReleaseStockActivity.php
├── app/Listeners/HandleOrderPaid.php
├── app/Listeners/HandleOrderDelivered.php
├── app/Listeners/HandleOrderDeleted.php
└── tests/Unit/Activities/*Stock*
```
