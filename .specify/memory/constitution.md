<!--
Sync Impact Report
- Version change: 1.2.0 → 1.3.0
- Added principles: VI. Persona-Based Scenarios & Regression Impact (NON-NEGOTIABLE)
- Added sections: System Personas & Roles (canonical catalog)
- Modified sections: Development Workflow & Quality Gates (persona scenarios, regression map in spec/plan/tasks)
- Templates: spec-template.md ✅ | plan-template.md ✅ | tasks-template.md ✅ | AGENTS.md ✅
- Follow-up TODOs: none
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

### VI. Persona-Based Scenarios & Regression Impact (NON-NEGOTIABLE)

Every feature specification, spec amendment, and materially scoped change MUST
include **persona-based scenarios** and a **regression impact map** before
implementation planning or coding begins.

#### Persona-based scenarios

For each affected persona (see **System Personas & Roles** below), the spec MUST
document at least one scenario when that persona's workflows, data, or risks are
touched — and MUST explicitly mark personas as **not impacted** when the change
does not reach them.

Each persona scenario MUST state:

- **Persona** — canonical role name from this constitution.
- **Goal** — what the persona is trying to accomplish in this scenario.
- **Given / When / Then** — acceptance-style steps the persona performs or observes.
- **Risk exercised** — which listed persona risk this scenario guards against.

Scenarios MUST reflect persona **responsibilities** and MUST NOT assign work
listed under **NOT responsible for** unless the feature explicitly changes
permissions or handoffs.

#### Regression impact map

The spec MUST include a **Regression Impact Map** that traces how the change
propagates beyond the primary workflow. At minimum, document:

| Impact type | Required when |
|-------------|---------------|
| **Field add/remove/rename** | Any API, model, UI form, or event payload field change |
| **Entity relationship change** | FK, optional/required, cardinality, soft-delete behavior |
| **Unit of measure (UOM) or quantity semantics** | Product, variation, stock, order line, or movement UOM |
| **State / workflow transition** | Order, invoice, payment, stock movement, or approval states |
| **Cross-module contract** | HTTP API, event schema, or shared UI field consumed elsewhere |
| **Permission / role boundary** | Who can view, create, edit, approve, or delete |

For each impacted area, list: **surface** (module, screen, API, event, report),
**personas affected**, **expected behavior before vs after**, and **regression
test** (unit, feature, integration, or manual QA reference).

Examples that MUST trigger cross-surface impact analysis:

- Adding or removing a field on product/variation → stock levels, reservations,
  sales order lines, procurement receipts, reports.
- Introducing UOM on product/variation → stock-in/out quantities, adjustments,
  reconciliation, oversell checks for Sales Representative.
- Changing invoice line structure → Finance Officer reconciliation, Operation
  Admin invoicing, payment matching.

`plan.md` MUST summarize the impact map in Constitution Check and reference
concrete modules/files. `tasks.md` MUST include regression verification tasks
for every **High** or **Critical** impact row.

**Rationale**: Inventory and order systems fail at integration boundaries.
Persona scenarios keep requirements grounded in real operators; the impact map
forces explicit regression thinking before code changes land.

## System Personas & Roles

Canonical personas for this platform. Feature specs MUST use these exact names.
Amend this catalog via constitution **MINOR** version when roles change.

### Operation Admin

| | |
|---|---|
| **Purpose** | Handles daily business operations and transactional setup. |
| **Responsibilities** | Create invoices; create quotations; record customer payments; create inventory items; manage customer records; view stock levels. |
| **NOT responsible for** | Physical stock movement; warehouse reconciliation; procurement approvals. |
| **Risks** | Incorrect invoice; wrong pricing; customer/accounting mismatch. |

### Warehouse Manager

| | |
|---|---|
| **Purpose** | Controls physical inventory movement. |
| **Responsibilities** | Stock-in; stock-out; inventory adjustment; stock reconciliation; damaged goods handling; warehouse transfers. |
| **NOT responsible for** | Invoicing; customer payments; financial approvals. |
| **Risks** | Negative stock; inventory mismatch; incorrect reconciliation. |

### Sales Representative

| | |
|---|---|
| **Purpose** | Drives customer sales and order creation. |
| **Responsibilities** | Create sales orders; create quotations; view product availability; apply discounts (limited); customer communication. |
| **NOT responsible for** | Stock adjustment; financial reconciliation; refund approval. |
| **Risks** | Overselling stock; wrong discount; incorrect order details. |

### Finance Officer

| | |
|---|---|
| **Purpose** | Handles accounting and payment verification. |
| **Responsibilities** | Verify payments; process refunds; reconcile invoices; generate financial reports; approve credit notes. |
| **NOT responsible for** | Physical inventory movement; warehouse operations. |
| **Risks** | Double refund; financial inconsistency; payment mismatch. |

### Procurement Officer

| | |
|---|---|
| **Purpose** | Handles supplier purchasing. |
| **Responsibilities** | Create purchase orders; manage suppliers; track incoming inventory; approve procurement requests. |
| **NOT responsible for** | Customer invoicing; stock adjustment after receiving. |
| **Risks** | Overstocking; wrong supplier pricing; procurement delays. |

### Inventory Auditor

| | |
|---|---|
| **Purpose** | Ensures inventory integrity and compliance. |
| **Responsibilities** | Review stock movement history; audit adjustments; verify reconciliation; investigate discrepancies. |
| **NOT responsible for** | Daily operations; order fulfillment. |
| **Risks** | Missed fraud; incorrect audit trail; missing stock history. |

### Store Manager / Branch Manager

| | |
|---|---|
| **Purpose** | Operational oversight for branch/store. |
| **Responsibilities** | Monitor sales performance; monitor inventory health; approve adjustments; review operational reports. |
| **NOT responsible for** | Technical configuration; detailed warehouse operations. |
| **Risks** | Operational inefficiency; approval bottlenecks. |

### Customer Support Officer

| | |
|---|---|
| **Purpose** | Handles post-sale customer issues. |
| **Responsibilities** | Process returns; initiate refund requests; handle order issues; view customer purchase history. |
| **NOT responsible for** | Financial approval; physical stock adjustment approval. |
| **Risks** | Unauthorized refunds; incorrect return workflow. |

### System Administrator

| | |
|---|---|
| **Purpose** | System-level configuration and governance. |
| **Responsibilities** | Manage permissions; configure workflows; manage integrations; audit access logs. |
| **NOT responsible for** | Daily operational transactions. |
| **Risks** | Over-permission; security exposure; workflow misconfiguration. |

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
3. **Persona & regression in spec** — Every `spec.md` (new or amended) MUST
   include **Persona Scenarios** and **Regression Impact Map** per Principle VI
   before `/speckit-plan` or `/speckit-implement`.
4. **Plan constitution check** — `plan.md` MUST include a Constitution Check
   section confirming module placement, domain/workflow split (or explicit reuse
   of an existing workflow), unit test plan, single-responsibility design for
   new functions, persona coverage, and high/critical regression items with
   planned tests.
5. **Local verification** — Before merge, run tests for every touched module:
   - Laravel service: `cd {service}/ && composer test` (or `php artisan test`)
   - UI: `cd ui/ && npm test`
   - After migrations: `bazelisk run //:migrate_all` or service-specific migrate
   - Full stack (manual QA): `docker-compose up -d` or `bazelisk run //:run_all`
6. **Formatting** — PHP: Laravel Pint (`./vendor/bin/pint`) in each service when
   PHP files change.
7. **Review** — Pull requests MUST verify: correct spec path (new or updated),
   persona scenarios and regression impact map present, unit tests for every
   new/changed function, one-responsibility functions, Laravel/React stack
   compliance.
8. **Complexity** — Violations of principles I–VI MUST be documented in plan
   Complexity Tracking with rejected simpler alternatives.
9. **CI** — No repository CI pipeline is configured; do not assume automated
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

**Version**: 1.3.0 | **Ratified**: 2026-05-16 | **Last Amended**: 2026-05-17
