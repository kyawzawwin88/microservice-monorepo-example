---

description: "Task list template for feature implementation"
---

# Tasks: [FEATURE NAME]

**Input**: Design documents from `/specs/[###-feature-name]/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Unit tests are MANDATORY per constitution — every new or changed
function MUST have a corresponding unit test task before implementation is
complete.

**Organization**: Tasks are grouped by **module → domain → workflow**, then by
user story within the workflow. Large workflows MAY add a `tasks/` subfolder or
phased sections; never mix unrelated workflows in one task file.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

This monorepo uses **module-scoped paths** — adjust `{service}` and `{Domain}` from `plan.md`:

| Module | Production code | Tests |
|--------|-----------------|-------|
| `inventory-service/` (or other `*-service/`) | `app/Models/`, `app/Services/{Domain}/`, `app/Http/Controllers/`, `routes/api.php` | `tests/Unit/`, `tests/Feature/` |
| `ui/` | `src/pages/`, `src/components/`, `src/api/` | `*.test.ts` co-located or under `src/` |
| Schema | `database/migrations/` | — |

Cross-module features: group tasks by module (backend phase, then UI phase, or parallel with `[P]`).

<!-- 
  ============================================================================
  IMPORTANT: The tasks below are SAMPLE TASKS for illustration purposes only.
  
  The /speckit-tasks command MUST replace these with actual tasks based on:
  - User stories from spec.md (with their priorities P1, P2, P3...)
  - Feature requirements from plan.md
  - Entities from data-model.md
  - Endpoints from contracts/
  
  Tasks MUST be organized by user story so each story can be:
  - Implemented independently
  - Tested independently
  - Delivered as an MVP increment
  
  DO NOT keep these sample tasks in the generated tasks.md file.
  ============================================================================
-->

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Migrations, routes, and module scaffolding per `plan.md`

- [ ] T001 Add migration in `{service}/database/migrations/` [if schema change]
- [ ] T002 Run migrations: `bazelisk run //:migrate_all` or `docker-compose` + `php artisan migrate`
- [ ] T003 [P] Register routes in `{service}/routes/api.php`
- [ ] T004 [P] Add API client methods in `ui/src/api/` [if UI module involved]

**Checkpoint**: Schema and routing ready

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Shared models, Actions, or UI shell that ALL user stories depend on

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

Examples (delete/adapt per feature):

- [ ] T005 Create or extend Eloquent model in `{service}/app/Models/`
- [ ] T006 [P] Add base `*Action` in `{service}/app/Services/{Domain}/`
- [ ] T007 [P] Add page route in `ui/src/App.tsx` and nav link [if UI]
- [ ] T008 Unit tests for foundational Actions in `tests/Unit/Services/{Domain}/`

**Checkpoint**: Foundation ready — user story work can begin

---

## Phase 3: User Story 1 - [Title] (Priority: P1) 🎯 MVP

**Goal**: [Brief description of what this story delivers]

**Independent Test**: [How to verify this story works on its own]

### Unit tests for User Story 1 (MANDATORY) ⚠️

> **NOTE: Add one unit test per new/changed function. Write tests FIRST; ensure
> they FAIL before implementation.**

- [ ] T010 [P] [US1] Unit test for [Action] in `{service}/tests/Unit/Services/{Domain}/[Action]Test.php`
- [ ] T011 [P] [US1] Feature test for [endpoint] in `{service}/tests/Feature/[Name]Test.php`
- [ ] T012 [P] [US1] Vitest for [helper] in `ui/src/.../[name].test.ts` [if UI]

### Implementation for User Story 1

- [ ] T013 [P] [US1] Implement `[Name]Action` in `{service}/app/Services/{Domain}/`
- [ ] T014 [US1] Wire controller method in `{service}/app/Http/Controllers/`
- [ ] T015 [US1] Build UI in `ui/src/pages/` or `ui/src/components/` [if UI]
- [ ] T016 [US1] Add validation and error responses (422/409/etc.)

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - [Title] (Priority: P2)

**Goal**: [Brief description of what this story delivers]

**Independent Test**: [How to verify this story works on its own]

### Unit tests for User Story 2 (MANDATORY) ⚠️

- [ ] T018 [P] [US2] Unit test in `{service}/tests/Unit/...`
- [ ] T019 [P] [US2] Feature test in `{service}/tests/Feature/...`

### Implementation for User Story 2

- [ ] T020 [P] [US2] Implement `[Name]Action` in `{service}/app/Services/{Domain}/`
- [ ] T021 [US2] Controller + routes for US2
- [ ] T022 [US2] UI components for US2 in `ui/src/` [if UI]
- [ ] T023 [US2] Integrate with User Story 1 (if needed)

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently

---

## Phase 5: User Story 3 - [Title] (Priority: P3)

**Goal**: [Brief description of what this story delivers]

**Independent Test**: [How to verify this story works on its own]

### Unit tests for User Story 3 (MANDATORY) ⚠️

- [ ] T024 [P] [US3] Unit test in `{service}/tests/Unit/...`
- [ ] T025 [P] [US3] Feature test in `{service}/tests/Feature/...`

### Implementation for User Story 3

- [ ] T026 [P] [US3] Implement `[Name]Action` in `{service}/app/Services/{Domain}/`
- [ ] T027 [US3] Controller + routes for US3
- [ ] T028 [US3] UI for US3 in `ui/src/` [if UI]

**Checkpoint**: All user stories should now be independently functional

---

[Add more user story phases as needed, following the same pattern]

---

## Phase N: Persona & Regression Verification *(mandatory — constitution Principle VI)*

**Purpose**: Validate persona scenarios and Regression Impact Map (RI-*) from spec.md

- [ ] TXXX Execute persona scenarios for [Persona 1] — [manual QA or automated path]
- [ ] TXXX Execute persona scenarios for [Persona 2] — [manual QA or automated path]
- [ ] TXXX [P] Regression test for RI-001 ([High/Critical]) in `{service}/tests/...` or `ui/...`
- [ ] TXXX [P] Regression test for RI-002 ([High/Critical]) in `{service}/tests/...`
- [ ] TXXX Confirm unaffected personas still behave as documented in spec

**Checkpoint**: All High/Critical RI rows have passing tests or documented QA evidence

---

## Phase N+1: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [ ] TXXX [P] Documentation updates in docs/
- [ ] TXXX Code cleanup and refactoring
- [ ] TXXX Performance optimization across all stories
- [ ] TXXX [P] Run `cd {service} && composer test` for each touched Laravel service
- [ ] TXXX [P] Run `cd ui && npm test` if UI changed
- [ ] TXXX Run Laravel Pint in touched services: `./vendor/bin/pint`
- [ ] TXXX Run quickstart.md validation (Docker Compose or `bazelisk run //:run_all`)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3+)**: All depend on Foundational phase completion
  - User stories can then proceed in parallel (if staffed)
  - Or sequentially in priority order (P1 → P2 → P3)
- **Polish (Final Phase)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 2 (P2)**: Can start after Foundational (Phase 2) - May integrate with US1 but should be independently testable
- **User Story 3 (P3)**: Can start after Foundational (Phase 2) - May integrate with US1/US2 but should be independently testable

### Within Each User Story

- Tests (if included) MUST be written and FAIL before implementation
- Models before services
- Services before endpoints
- Core implementation before integration
- Story complete before moving to next priority

### Parallel Opportunities

- All Setup tasks marked [P] can run in parallel
- All Foundational tasks marked [P] can run in parallel (within Phase 2)
- Once Foundational phase completes, all user stories can start in parallel (if team capacity allows)
- All tests for a user story marked [P] can run in parallel
- Models within a story marked [P] can run in parallel
- Different user stories can be worked on in parallel by different team members

---

## Verification Commands

Run for every module touched before marking feature complete:

```bash
# Laravel service(s)
cd inventory-service && composer test
# or: php artisan test

# UI
cd ui && npm test

# Migrations (after schema changes)
bazelisk run //:migrate_all

# Full stack (manual QA)
docker-compose up -d
# or: bazelisk run //:run_all
```

## Parallel Example: User Story 1

```bash
# Tests in parallel [P]:
Task: "Unit test for RecordStockInAction in inventory-service/tests/Unit/Services/Stock/"
Task: "Feature test in inventory-service/tests/Feature/StockMovementTest.php"

# Implementation in parallel [P] when files differ:
Task: "RecordStockInAction in app/Services/Stock/"
Task: "Vitest for locationFormUtils in ui/src/components/inventory/"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: Test User Story 1 independently
5. Deploy/demo if ready

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 → Test independently → Deploy/Demo (MVP!)
3. Add User Story 2 → Test independently → Deploy/Demo
4. Add User Story 3 → Test independently → Deploy/Demo
5. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: User Story 1
   - Developer B: User Story 2
   - Developer C: User Story 3
3. Stories complete and integrate independently

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Verify tests fail before implementing
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Avoid: vague tasks, same file conflicts, cross-story dependencies that break independence
