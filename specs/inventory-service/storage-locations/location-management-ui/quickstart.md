# Quickstart: Storage Location Management (UI)

**Feature**: `location-management-ui` | **Branch**: `003-inventory-location-admin`

## Prerequisites

- Docker Compose running (`inventory-service`, MySQL, `ui`)
- Variation-stock migrations applied (`storage_locations` table exists)
- `git checkout 003-inventory-location-admin`

## 1. Seed default locations

```bash
docker compose exec inventory-service php artisan db:seed --class=StorageLocationSeeder
```

## 2. List locations (active — movement API)

```bash
curl -s http://localhost:8004/api/inventory/locations | jq .
```

## 3. List all locations (admin — after implementation)

```bash
curl -s "http://localhost:8004/api/inventory/locations?all=1" | jq .
```

## 4. Create location

```bash
curl -s -X POST http://localhost:8004/api/inventory/locations \
  -H "Content-Type: application/json" \
  -d '{"name":"Secondary Warehouse","code":"WH-SEC"}' | jq .
```

## 5. Update location (after implementation)

```bash
curl -s -X PUT http://localhost:8004/api/inventory/locations/2 \
  -H "Content-Type: application/json" \
  -d '{"name":"Secondary WH","code":"WH-SEC"}' | jq .
```

## 6. Deactivate empty location (after implementation)

```bash
curl -s -X POST http://localhost:8004/api/inventory/locations/2/deactivate | jq .
```

Expect `is_active: false`. Confirm `GET /inventory/locations` no longer includes id `2`.

## 7. Deactivate blocked when stock exists

Stock-in at location first, then:

```bash
curl -s -X POST http://localhost:8004/api/inventory/locations/1/deactivate | jq .
```

Expect **422** with message about remaining stock.

## 8. UI smoke (after UI phase)

1. Open UI → **Inventory** → **Manage locations** (or `/inventory/locations`).
2. Create a location; verify it appears in the table.
3. Open **Transfer stock** — new location appears in dropdowns.
4. Deactivate an empty location; confirm it disappears from transfer dropdowns but shows inactive on admin page.

## 9. Tests

```bash
docker compose exec inventory-service ./vendor/bin/phpunit --filter StorageLocation
cd ui && npx vitest run
```
