# Agent Boundaries — Microservice Monorepo

This file defines **which directories each agent owns** and how agents coordinate
in this monorepo. Process rules live in `.specify/memory/constitution.md`; runtime
architecture in `README.md`.

## Modules

| Agent scope | Owns | MUST NOT |
|-------------|------|----------|
| **inventory** | `inventory-service/`, `specs/inventory-service/` | Import PHP from other services; duplicate stock logic in `ui/` |
| **sales** | `sales-service/`, `specs/` paths under `sales-service/` (when used) | Own inventory/invoice/payment tables |
| **invoice** | `invoice-service/`, `specs/invoice-service/` (when used) | Call payment DB directly |
| **payment** | `payment-service/`, `specs/payment-service/` | Own order lifecycle in sales DB |
| **ui** | `ui/`, `specs/ui/` | Implement business rules that belong in Laravel services; bypass `/svc/*` proxy |

`openclaw-*-agent/` workspaces are **out of scope** for feature delivery unless explicitly requested.

## Spec placement

All feature specs MUST follow:

```text
specs/{module}/{domain}/{workflow}/
```

- **Primary agent** = module in the spec path.
- **Secondary agent** (e.g., UI consuming new inventory APIs) = documented in spec **Cross-Module Scope** and `plan.md` Complexity Tracking.

Search existing specs before creating a new workflow folder (constitution Principle IV).

Every `spec.md` (new or amended) MUST include **Persona Scenarios** and a
**Regression Impact Map** per constitution Principle VI. Use canonical persona
names from `.specify/memory/constitution.md` (System Personas & Roles).

## Inter-agent communication

| Need | Mechanism |
|------|-----------|
| UI → backend | HTTP via Vite proxy `/svc/inventory`, `/svc/sales`, etc.; client code in `ui/src/api/` |
| Service → service | Laravel database queue on `eventbus_db`; durable workflows + activities |
| Shared trace | `correlation_id` on records and events |
| API contract | `specs/.../contracts/*.md` + `routes/api.php`; OpenAPI via Scramble where enabled |

Agents MUST NOT copy service code into another module. Propose a shared package only when two services need the same stable abstraction.

## Implementation patterns

| Layer | Location |
|-------|----------|
| HTTP | `{service}/app/Http/Controllers/` |
| Business logic | `{service}/app/Services/{Domain}/*Action.php` |
| Workflows | `{service}/app/Workflows/`, `app/Activities/` |
| Persistence | `{service}/app/Models/`, `database/migrations/` |
| Tests | `{service}/tests/Unit/`, `tests/Feature/`; `ui/**/*.test.ts` |

## Verification (per touched module)

```bash
cd inventory-service && composer test   # replace with active service
cd ui && npm test
bazelisk run //:migrate_all             # after migrations
docker-compose up -d                    # manual QA
```

## When work spans modules

1. One **primary** spec under the owning module path.
2. `plan.md` lists concrete paths in each module.
3. `tasks.md` groups phases by module or uses `[P]` for parallel backend/UI tasks.
4. Constitution Check in `plan.md` documents cross-module scope, persona coverage,
   and High/Critical regression items from the spec impact map.
