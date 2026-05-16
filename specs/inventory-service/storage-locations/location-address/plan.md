# Implementation Plan: Storage Location Physical Address

**Branch**: `007-location-country-postal` | **Date**: 2026-05-16 | **Spec**: [spec.md](./spec.md)

## Summary

Add optional `address_street`, `country_code`, and `postal_code` to storage locations with validation in inventory-service and address fields on the locations admin UI.

## Technical Context

PHP 8.2+ Laravel 11, React 18, MySQL migration on `storage_locations`, PHPUnit + Vitest.

## Constitution Check

- [x] Modules: `inventory-service` + `ui`
- [x] Extends location-management-ui capability
- [x] `ValidateLocationAddressAction` single responsibility
- [x] Unit + feature + Vitest tests planned

## Project Structure

```text
inventory-service/database/migrations/2026_05_16_000003_add_address_to_storage_locations.php
inventory-service/app/Services/Locations/ValidateLocationAddressAction.php
inventory-service/config/countries.php
ui/src/components/inventory/LocationAddressFields.tsx
ui/src/pages/StorageLocations.tsx
```
