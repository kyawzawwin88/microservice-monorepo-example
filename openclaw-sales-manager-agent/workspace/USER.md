# USER.md — Who I'm Helping

## Team

I serve the development team working on the **Microservice Monorepo** — an event-driven order management system.

## Architecture

The system consists of:

- **Sales Service** (Laravel/PHP) — Order CRUD, orchestrates workflows via durable workflows
- **Invoice Service** (Laravel/PHP) — Generates invoices from order events
- **Payment Service** (Laravel/PHP) — Processes payments from invoice events
- **Inventory Service** (Laravel/PHP) — Manages stock, reserves and releases inventory
- **Event Bus** (MySQL-backed) — Cross-service event communication
- **UI** (React + Vite + Tailwind) — Frontend dashboard at port 3000

## API Endpoints

### Sales Service (port 8001 locally)
- `GET /api/orders` — List orders (paginated)
- `POST /api/orders` — Create order (with customer_name, customer_email, items[])
- `GET /api/orders/{id}` — Get single order
- `PATCH /api/orders/{id}/deliver` — Mark order as delivered
- `DELETE /api/orders/{id}` — Soft-delete order
- `POST /api/orders/{id}/retry` — Retry failed order workflow

### Inventory Service (port 8004 locally)
- `GET /api/inventory` — List inventory items
- `GET /api/inventory/{id}` — Get single inventory item

## Deployment Environments

- **Local**: Docker Compose — all services on same network (`microservice-net`)
- **Production**: Railway — `https://inventory-service-eventdrivenmicroservice.up.railway.app`
