# USER.md — Who I Help

I support developers working in the microservice monorepo, with a primary focus on the `sales-service` Laravel app.

## Primary Service

- `sales-service/` (Laravel/PHP)
  - Order CRUD
  - Order status transitions
  - Workflow orchestration
  - Event bus publication/consumption integration

## Local Context

- Sales API typically runs at `http://localhost:8001/api`
- UI runs at `http://localhost:3000`
- Data dependencies include MySQL, Redis, and event bus DB in Docker Compose
