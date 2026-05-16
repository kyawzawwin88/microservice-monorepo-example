# Implementation Plan: [FEATURE]

**Branch**: `[###-feature-name]` | **Date**: [DATE] | **Spec**: [link]
**Input**: Feature specification from `specs/{module}/{domain}/{workflow}/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command.

## Summary

[Extract from feature spec: primary requirement + technical approach from research]

## Technical Context

Defaults for this monorepo — override only what differs for this feature:

**Language/Version**: PHP 8.2+ (Laravel 11) for `*-service/`; TypeScript 5 + React 18 for `ui/`  
**Primary Dependencies**: Eloquent, laravel-workflow, Spatie model states, React Router, Tailwind CSS, Vite  
**Storage**: MySQL per service (`sales_db`, `invoice_db`, `payment_db`, `inventory_db`); shared `eventbus_db` for queue events  
**Testing**: PHPUnit 11 (`tests/Unit/`, `tests/Feature/`); Vitest in `ui/` (`npm test`)  
**Target Platform**: Docker Compose locally; optional Railway deployment for UI  
**Project Type**: Monorepo — event-driven microservices + SPA  
**Performance Goals**: [domain-specific, e.g., list API &lt;200ms p95 local]  
**Constraints**: [e.g., database-per-service, idempotent activities, no hard delete]  
**Scale/Scope**: [e.g., operator UI, &lt;100 locations, single workflow]

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- [ ] **Module**: Work scoped to one primary module; cross-module work documented (see spec Cross-Module Scope).
- [ ] **Spec path**: `specs/{module}/{domain}/{workflow}/`
- [ ] **Spec reuse**: Existing workflow updated OR new folder justified (document path and why).
- [ ] **Domain / workflow split**: Cohesive domain; one capability per workflow folder.
- [ ] **Single responsibility**: New `*Action` / `*Activity` classes each do one thing; workflows orchestrate only.
- [ ] **Unit tests**: PHPUnit and/or Vitest planned for every new/changed function.
- [ ] **Stack**: Laravel + React/Tailwind only, unless justified in Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/{module}/{domain}/{workflow}/
├── spec.md
├── plan.md              # This file
├── research.md          # Phase 0
├── data-model.md        # Phase 1
├── quickstart.md        # Phase 1
├── contracts/           # Phase 1 API contracts
├── tasks.md             # Phase 2 (/speckit-tasks)
└── checklists/          # Optional (/speckit-checklist)
```

### Source Code (repository root)

Delete branches that do not apply. Expand the chosen branch with real paths.

```text
# Laravel service (inventory-service | sales-service | invoice-service | payment-service)
{service}/
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   ├── Services/{Domain}/*Action.php
│   ├── Activities/              # if workflow feature
│   └── Workflows/               # if workflow feature
├── database/migrations/
├── routes/api.php
└── tests/
    ├── Unit/
    └── Feature/

# React UI (ui/)
ui/
├── src/
│   ├── api/                     # service client modules
│   ├── pages/
│   ├── components/
│   └── App.tsx                  # routes
└── [*.test.ts]                  # Vitest co-located or under src/
```

**Structure Decision**: [Which modules change; cite concrete files from the tree above]

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| [e.g., ui + inventory-service] | [operator UI needs new APIs] | [API-only insufficient for acceptance criteria] |
