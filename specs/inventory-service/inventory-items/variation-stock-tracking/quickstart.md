# Quickstart: Variation Stock Tracking (local)

**Feature**: `variation-stock-tracking` | **Branch**: `001-variation-stock-tracking`

## Prerequisites

- Docker Compose stack running (`inventory-service`, MySQL, API gateway, `ui`)
- On feature branch: `git checkout 001-variation-stock-tracking`

## 1. Run migrations (after implementation)

```bash
docker compose exec inventory-service php artisan migrate
docker compose exec inventory-service php artisan db:seed --class=StorageLocationSeeder
```

## 2. Create variation item (API)

```bash
curl -s -X POST http://localhost:8004/api/inventory \
  -H "Content-Type: application/json" \
  -d '{
    "product_name": "T-Shirt",
    "sku": "TSHIRT-BASE",
    "unit_price": 19.99,
    "has_variations": true,
    "dimensions": [
      { "name": "Size", "values": ["S", "M", "L"] },
      { "name": "Color", "values": ["Red", "Blue"] }
    ],
    "initial_location_id": 1,
    "initial_quantity_per_variation": 0
  }' | jq .
```

Expect **6 variations** in response.

## 3. Stock-in one variation

```bash
curl -s -X POST http://localhost:8004/api/inventory/stock-in \
  -H "Content-Type: application/json" \
  -d '{
    "inventory_variation_id": 1,
    "storage_location_id": 1,
    "quantity": 50
  }' | jq .
```

## 4. Transfer between locations

```bash
curl -s -X POST http://localhost:8004/api/inventory/transfers \
  -H "Content-Type: application/json" \
  -d '{
    "inventory_variation_id": 1,
    "source_location_id": 1,
    "destination_location_id": 2,
    "quantity": 20
  }' | jq .
```

## 5. Stock report

```bash
curl -s "http://localhost:8004/api/inventory/1/stock-report" | jq .
```

Verify aggregated total equals sum of variation balances (SC-003).

## 6. Legacy regression

```bash
curl -s http://localhost:8004/api/inventory/all | jq .
```

Confirm pre-seeded items without `has_variations` still list `quantity_available`.

## 7. UI smoke (after UI phase)

1. Open Inventory → item detail route (not modal).
2. Expand variation row → stock-in inline form.
3. Navigate to **Stock Transfer** page; complete transfer without modal wizard.

## 8. Tests

```bash
docker compose exec inventory-service php artisan test --testsuite=Unit
cd ui && npm test
```

All new Actions/Activities must have corresponding unit tests before merge (constitution).
