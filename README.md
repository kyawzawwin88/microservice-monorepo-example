# Microservice Monorepo Example

A demonstration of **event-driven microservice architecture** using Laravel, Bazel monorepo, durable workflows, and model states.

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        API Gateway                               │
│                    POST /api/orders                               │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Sales Service (:8001)                         │
│  NewOrderWorkflow:                                                │
│    1. ValidateOrderActivity (idempotency check)                  │
│    2. UpdateOrderStateActivity (state → requested)               │
│    3. ClearOrderCacheActivity                                     │
│    └─→ Dispatches OrderCreated event to Event Bus                │
└────────────────┬──────────────────────┬─────────────────────────┘
                 │                      │
     ┌───────────▼──────────┐  ┌───────▼──────────────┐
     │  Invoice Svc (:8002) │  │ Inventory Svc (:8004) │
     │  CreateInvoiceWF:    │  │ ReserveInventoryWF:   │
     │  1. GenerateInvoice  │  │ 1. CheckStock         │
     │  2. UpdateState      │  │ 2. UpdateState        │
     │  3. ClearCache       │  │ 3. ClearCache         │
     │  └→ InvoiceCreated   │  │ └→ InventoryReserved  │
     └──────────┬───────────┘  └───────────────────────┘
                │
                ▼
     ┌──────────────────────┐
     │ Payment Svc (:8003)  │
     │ ProcessPaymentWF:    │
     │ 1. ChargePayment     │
     │ 2. UpdateState       │
     │ 3. ClearCache        │
     │ └→ PaymentCompleted  │
     └──────────┬───────────┘
                │
                ▼
     ┌──────────────────────┐
     │   Sales Service      │
     │ CompleteOrderWF:     │
     │  → Order completed   │
     └──────────────────────┘
```

## Tech Stack

| Component | Technology |
|-----------|-----------|
| Framework | Laravel 11 |
| Workflow Engine | [Durable Workflow](https://durable-workflow.com) (laravel-workflow/laravel-workflow) |
| State Management | [Spatie Laravel Model States](https://spatie.be/docs/laravel-model-states/v2) |
| Event Bus | Laravel Database Queue (shared MySQL) |
| Build System | Bazel |
| Containers | Docker / Docker Compose |

## Services

| Service | Port | Database | Description |
|---------|------|----------|-------------|
| Sales | 8001 | sales_db | Order management, entry point for NewOrderWorkflow |
| Invoice | 8002 | invoice_db | Invoice generation from orders |
| Payment | 8003 | payment_db | Payment processing for invoices |
| Inventory | 8004 | inventory_db | Stock management and reservations |
| Event Bus | - | eventbus_db | Shared database for cross-service queue communication |

## Key Principles

### Every Feature is a Workflow
Business features are modeled as durable workflows that orchestrate multiple activities:
- **NewOrderWorkflow** → Validate → Create Order → Clear Cache → Dispatch Event
- **CreateInvoiceWorkflow** → Generate Invoice → Update State → Clear Cache → Dispatch Event
- **ProcessPaymentWorkflow** → Charge Payment → Update State → Clear Cache → Dispatch Event
- **ReserveInventoryWorkflow** → Check Stock → Update State → Clear Cache → Dispatch Event

### Correlation ID Tracking
Every record has a `correlation_id` (UUID) that traces the entire request across all services.

### State Management (Spatie Model States)
Every database record has a state machine with transitions:
- `requested` → Service received event/API request
- `completed` → Service finished processing
- `failed` → Error occurred (with `state_failure_description`)

### Idempotency
All workflow activities check `correlation_id` before creating records to prevent duplicate processing.

### Event Logging
Each service logs every incoming event with raw payload in the `event_logs` table.

## Quick Start

```bash
# Start all services
docker-compose up -d

# Wait for MySQL containers to be ready (~15 seconds)
sleep 15

# Run migrations for each service
docker-compose exec sales-service php artisan migrate --force
docker-compose exec invoice-service php artisan migrate --force
docker-compose exec payment-service php artisan migrate --force
docker-compose exec inventory-service php artisan migrate --force

# Run eventbus migration (from sales-service)
docker-compose exec sales-service php artisan migrate --database=eventbus --force

# Seed inventory data
docker-compose exec inventory-service php artisan db:seed --class="Database\Seeders\InventorySeeder" --force
```

## Testing the Flow

### Create an Order
```bash
curl -X POST http://localhost:8001/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "total_amount": 99.99,
    "items": [
      {"product_name": "Widget A", "quantity": 2, "unit_price": 49.99}
    ]
  }'
```

### Check Service Health
```bash
curl http://localhost:8001/api/health  # Sales
curl http://localhost:8002/api/health  # Invoice
curl http://localhost:8003/api/health  # Payment
curl http://localhost:8004/api/health  # Inventory
```

### Track by Correlation ID
```bash
# Replace {correlation_id} with the UUID from order creation response
curl http://localhost:8001/api/orders/correlation/{correlation_id}
curl http://localhost:8002/api/invoices/correlation/{correlation_id}
curl http://localhost:8003/api/payments/correlation/{correlation_id}
curl http://localhost:8004/api/reservations/correlation/{correlation_id}
```

### View Event Logs
```bash
curl http://localhost:8001/api/event-logs  # Sales events
curl http://localhost:8002/api/event-logs  # Invoice events
curl http://localhost:8003/api/event-logs  # Payment events
curl http://localhost:8004/api/event-logs  # Inventory events
```

## Bazel Build

```bash
# Build all service packages
bazel build //sales-service:sales_service_files
bazel build //invoice-service:invoice_service_files
bazel build //payment-service:payment_service_files
bazel build //inventory-service:inventory_service_files
```

## Project Structure

```
microservice-monorepo-example/
├── MODULE.bazel                    # Bazel module configuration
├── BUILD.bazel                     # Root BUILD file
├── .bazelrc                        # Bazel settings
├── docker-compose.yml              # Container orchestration
├── scripts/
│   └── setup.sh                    # Setup automation
│
├── sales-service/                  # Sales Microservice
│   ├── BUILD.bazel
│   ├── Dockerfile
│   ├── composer.json
│   ├── app/
│   │   ├── Activities/             # Workflow activities
│   │   │   ├── ValidateOrderActivity.php
│   │   │   ├── UpdateOrderStateActivity.php
│   │   │   └── ClearOrderCacheActivity.php
│   │   ├── Events/                 # Laravel events
│   │   │   ├── OrderCreated.php
│   │   │   └── PaymentCompleted.php
│   │   ├── Exceptions/
│   │   │   └── OrderProcessingException.php
│   │   ├── Listeners/
│   │   │   ├── LogIncomingEvent.php
│   │   │   └── HandlePaymentCompleted.php
│   │   ├── Models/
│   │   │   ├── Order.php
│   │   │   ├── EventLog.php
│   │   │   └── Concerns/HasCorrelationId.php
│   │   ├── States/
│   │   │   ├── OrderState.php
│   │   │   ├── RequestedState.php
│   │   │   ├── CompletedState.php
│   │   │   └── FailedState.php
│   │   └── Workflows/
│   │       ├── NewOrderWorkflow.php
│   │       └── CompleteOrderWorkflow.php
│   ├── config/
│   ├── database/migrations/
│   └── routes/api.php
│
├── invoice-service/                # Invoice Microservice
│   └── (similar structure)
│
├── payment-service/                # Payment Microservice
│   └── (similar structure)
│
└── inventory-service/              # Inventory Microservice
    └── (similar structure)
```

## Event Flow

1. **Sales Service** → `POST /api/orders` → `NewOrderWorkflow` → dispatches `OrderCreated`
2. **Invoice Service** ← listens `OrderCreated` → `CreateInvoiceWorkflow` → dispatches `InvoiceCreated`
3. **Payment Service** ← listens `InvoiceCreated` → `ProcessPaymentWorkflow` → dispatches `PaymentCompleted`
4. **Inventory Service** ← listens `OrderCreated` → `ReserveInventoryWorkflow` → dispatches `InventoryReserved`
5. **Sales Service** ← listens `PaymentCompleted` → `CompleteOrderWorkflow` → order state → `completed`

## Workflow UI (Waterline)

The durable-workflow package supports [Waterline](https://durable-workflow.com) UI for monitoring and triggering workflows. Once configured, you can:
- View active/completed/failed workflows
- Trigger workflows manually from the UI
- Inspect workflow execution history and activity results
- Retry failed workflows

<!-- to run remote browser in host -->

/Applications/Google\ Chrome\ Dev.app/Contents/MacOS/Google\ Chrome\ Dev --remote-debugging-port=18800 --user-data-dir="$HOME/openclaw-sales-manager-agent" --profile-directory="openclaw-sales-manager-agent"
(base) kyawzawwin@Kyaws-MacBook-Pro microservice-monorepo-example % 
