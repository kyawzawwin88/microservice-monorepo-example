<!--
Sync Impact Report
- Version change: 1.1.0 → 1.2.0
- Added sections: Project Identity, Naming & Code Conventions, Event-Driven Integration
- Modified sections: Development Workflow & Quality Gates (branch pattern, test commands, tooling)
- Templates: spec-template.md ✅ | plan-template.md ✅ | tasks-template.md ✅ | AGENTS.md ✅
-->
# Microservice Inventory Management & Control System Constitution

## Project Identity

| Attribute | Value |
|-----------|-------|
| **Repository** | `microservice-monorepo-example` |
| **Purpose** | Event-driven microservice demo for inventory, orders, invoices, and payments |
| **Architecture** | Monorepo — database-per-service Laravel microservices + React SPA |
| **Primary languages** | PHP (~81% LOC), TypeScript/React (~19% LOC) |
| **Backend** | PHP 8.2+, Laravel 11, Eloquent, MySQL 8 per service |
| **Frontend** | React 18, Vite 5, Tailwind CSS 3, TypeScript 5 |
| **Orchestration** | `laravel-workflow/laravel-workflow`, Spatie Laravel Model States |
| **Infrastructure** | Docker Compose, Redis 7, shared event-bus MySQL, Bazel (`bazelisk run //:run_all`) |
| **CI/CD** | Not configured in repository — local tests and PR review are current quality gates |

Service ports and responsibilities follow `README.md` (Sales :8001, Invoice :8002,
Payment :8003, Inventory :8004, UI via Vite :3000 with `/svc/*` proxy).

## Core Principles

### I. Microservice Module Boundaries

The monorepo MUST organize runtime code and specifications by service module. Each
module owns its data, APIs, workflows, and tests.

| Module | Service directory | Responsibility |
|--------|-------------------|----------------|
| Inventory | `inventory-service/` | Stock levels, reservations, stock movements |
| Invoice | `invoice-service/` | Invoice generation and lifecycle |
| Payment | `payment-service/` | Payment processing, reconciliation, billing |
| Sales & Orders | `sales-service/` | Orders, sales workflows, orchestration entry points |
| UI | `ui/` | React user interface for operators and customers |

Cross-module integration MUST use explicit contracts (events, HTTP APIs, shared
schemas). Shared logic MUST NOT be copied across services; extract shared
packages only when a second consumer exists and the abstraction is stable.

**Rationale**: Clear module ownership prevents coupling, enables independent
deployments, and matches the event-driven microservice architecture.

### II. Single Responsibility Per Function

Every function, method, or activity MUST perform exactly one task with one
clear responsibility. If a unit does more than one thing, it MUST be split.

Orchestration (workflows) composes single-purpose activities; activities MUST NOT
embed unrelated business rules or I/O concerns.

**Rationale**: Small units are easier to test, reason about, and reuse in
durable workflows without hidden side effects.

### III. Unit Test Coverage (NON-NEGOTIABLE)

Every function, method, and activity introduced or changed MUST have a
corresponding unit test before merge. Tests MUST assert behavior, not
implementation details. No production code ships without matching unit tests in
the same module's test suite (PHPUnit for Laravel services, Vitest for `ui/`).

**Rationale**: Mandatory unit tests enforce regressions safety and document
expected behavior at the smallest testable unit.

### IV. Specification Hierarchy (Module → Domain → Workflow → Task)

All feature specifications, plans, and task lists MUST be grouped strictly:

1. **Module** — one of: `inventory-service`, `invoice-service`,
   `payment-service`, `sales-service`, or `ui`.
2. **Domain** — a cohesive business area within the module (e.g.,
   `reconciliation` and `billing` are separate domains in `payment-service`;
   `stock-movement` in `inventory-service`).
3. **Workflow / capability** — one durable workflow or user-facing capability
   per folder (e.g., `stock-in` and `stock-out` are separate workflows, not one
   combined spec).
4. **Task** — when a workflow spec or implementation plan is too large, split
   work into named task documents or phased sections under the same workflow,
   never mixing unrelated workflows in one task file.

Canonical path pattern:

```text
specs/{module}/{domain}/{workflow}/
├── spec.md
├── plan.md
└── tasks.md          # or tasks/ when subdivided
```

#### Spec reuse (prefer update over new folder)

Agents and contributors MUST NOT create a new workflow folder by default. Before
adding `specs/{module}/{domain}/{workflow}/`:

1. **Search** existing specs under the same **module** (and likely **domain**).
2. **Extend** the existing `spec.md` (and related `plan.md` / `tasks.md`) when the
   request is an enhancement, UI addition, API extension, or phased delivery that
   belongs to the same workflow/capability and does not require a separate
   durable workflow or independent lifecycle.
3. **Create** a new workflow folder ONLY when the work is a distinct capability per
   the hierarchy above (e.g., `stock-in` vs `stock-out`), crosses into a different
   **domain**, or needs isolated planning/verification because scope and ownership
   differ materially from every existing workflow in that domain.

When extending an existing spec, record what changed (date, summary) in that
spec or in a short amendment note; do not duplicate requirements across sibling
workflow folders.

**Rationale**: Predictable structure lets agents and humans navigate the
monorepo without ambiguity, keeps specs aligned with service boundaries, and
avoids spec sprawl when work naturally belongs with an existing workflow.

### V. Technology Stack Adherence

Backend services MUST use PHP Laravel (current major version in repo). The UI
MUST use React with Tailwind CSS. New services or frontends MUST NOT introduce
alternate primary frameworks without a constitution amendment.

Workflow orchestration, model states, and event-driven patterns documented in
the repository README remain the default integration style unless a feature
spec justifies an exception in Complexity Tracking.

**Rationale**: A single stack reduces operational overhead and keeps skills and
tooling consistent across modules.

## Naming & Code Conventions

Detected patterns in this repository — new code SHOULD match:

| Area | Convention |
|------|------------|
| **Feature branches** | `NNN-kebab-description` (e.g., `007-location-country-postal`) |
| **Commit messages** | Conventional Commits preferred (`feat:`, `fix:`) |
| **PHP classes** | PascalCase; `declare(strict_types=1);` and PSR-12 per `.cursor/rules/.cursorrules` |
| **Service actions** | `app/Services/{Domain}/*Action.php` — one responsibility per class |
| **Workflow activities** | `app/Activities/*Activity.php` |
| **Workflows** | `app/Workflows/*Workflow.php` |
| **API routes** | `routes/api.php`; REST paths under service prefix (e.g., `/inventory/...`) |
| **Migrations** | `database/migrations/` with timestamp prefix |
| **PHP tests** | `tests/Unit/`, `tests/Feature/`; class suffix `*Test.php` |
| **UI pages** | PascalCase in `ui/src/pages/` (e.g., `StorageLocations.tsx`) |
| **UI tests** | Co-located `*.test.ts` next to utils or under `ui/src/` |
| **Spec folders** | kebab-case under `specs/{module}/{domain}/{workflow}/` |

## Event-Driven Integration

Cross-service features MUST follow patterns documented in `README.md`:

- **Database per service** — no shared tables across service boundaries.
- **Event bus** — Laravel database queue on shared `eventbus_db` for cross-service events.
- **Durable workflows** — business features orchestrated via `laravel-workflow`; activities stay single-purpose.
- **Correlation ID** — `correlation_id` (UUID) traces a request across services; activities MUST be idempotent on `correlation_id`.
- **Model states** — Spatie states (`requested`, `completed`, `failed`) on domain records.
- **UI access** — `ui/` calls services via Vite proxy `/svc/{sales,invoice,payment,inventory}` only; no direct cross-service PHP imports.

## Platform & Modules

This system is a **monorepo microservice inventory management and control
platform**. Services communicate via events and HTTP where appropriate; each
service maintains its own database. The UI consumes service APIs and reflects
order, inventory, invoice, and payment state.

Ports and responsibilities follow `README.md` unless superseded by an approved
feature spec for a given module.

## Development Workflow & Quality Gates

1. **Branching** — Feature branches use `NNN-kebab-description`. Spec Kit git
   extensions MAY enforce naming when enabled.
2. **Spec first** — No implementation plan or tasks until requirements are
   captured in `spec.md` at the correct path. Use an **existing** workflow spec
   when Principle IV reuse applies; create a new workflow folder only when reuse
   criteria are not met. Document the placement decision (existing vs new path) in
   the spec or plan Constitution Check.
3. **Plan constitution check** — `plan.md` MUST include a Constitution Check
   section confirming module placement, domain/workflow split (or explicit reuse
   of an existing workflow), unit test plan, and single-responsibility design for
   new functions.
4. **Local verification** — Before merge, run tests for every touched module:
   - Laravel service: `cd {service}/ && composer test` (or `php artisan test`)
   - UI: `cd ui/ && npm test`
   - After migrations: `bazelisk run //:migrate_all` or service-specific migrate
   - Full stack (manual QA): `docker-compose up -d` or `bazelisk run //:run_all`
5. **Formatting** — PHP: Laravel Pint (`./vendor/bin/pint`) in each service when
   PHP files change.
6. **Review** — Pull requests MUST verify: correct spec path (new or updated),
   unit tests for every new/changed function, one-responsibility functions,
   Laravel/React stack compliance.
7. **Complexity** — Violations of principles I–V MUST be documented in plan
   Complexity Tracking with rejected simpler alternatives.
8. **CI** — No repository CI pipeline is configured; do not assume automated
   gates until one is added and referenced here.

## Governance

This constitution supersedes ad-hoc conventions for Spec Kit artifacts and
feature delivery in this repository. Amendments require:

1. A documented proposal (what principle changes and why).
2. Semantic version bump of this file (`MAJOR` breaking, `MINOR` additive,
   `PATCH` clarifications only).
3. Propagation to `.specify/templates/*` and any active feature specs that
   conflict with the amendment.
4. `LAST_AMENDED_DATE` updated to the amendment date (ISO `YYYY-MM-DD`).

All `/speckit-*` command outputs MUST be checked for compliance before merge.
Runtime development guidance remains in `README.md` and module-local docs;
where they conflict with this constitution, this document wins for process and
quality gates.

**Version**: 1.2.0 | **Ratified**: 2026-05-16 | **Last Amended**: 2026-05-16
