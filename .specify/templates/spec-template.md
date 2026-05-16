# Feature Specification: [FEATURE NAME]

**Feature Branch**: `[###-feature-name]`  
**Created**: [DATE]  
**Status**: Draft  
**Input**: User description: "$ARGUMENTS"

## Specification Placement *(mandatory)*

Per project constitution, this spec MUST live at:

`specs/{module}/{domain}/{workflow}/spec.md`

| Field | Value |
|-------|-------|
| **Module** | [inventory-service \| invoice-service \| payment-service \| sales-service \| ui] |
| **Domain** | [e.g., billing, reconciliation, stock-movement] |
| **Workflow / capability** | [e.g., stock-in, stock-out — one workflow per folder] |
| **Placement** | [New workflow folder \| Extend existing — cite path if extending] |

### When to create vs extend

Before creating a new workflow directory, search `specs/{module}/` for an existing
domain/workflow that already owns this capability.

- **Extend** the existing `spec.md` when the change is an enhancement, UI work,
  API extension, or phased delivery within the same workflow/capability.
- **Create** a new workflow folder only for a distinct capability (per constitution
  domain/workflow split), a different domain, or materially separate ownership.

If extending, link the existing path above and summarize what this amendment adds.

If the workflow is large, downstream `tasks.md` MAY be split into phased sections
or `tasks/` subfolders; do not combine unrelated workflows in one directory.

## User Scenarios & Testing *(mandatory)*

<!--
  IMPORTANT: User stories should be PRIORITIZED as user journeys ordered by importance.
  Each user story/journey must be INDEPENDENTLY TESTABLE - meaning if you implement just ONE of them,
  you should still have a viable MVP (Minimum Viable Product) that delivers value.
  
  Assign priorities (P1, P2, P3, etc.) to each story, where P1 is the most critical.
  Think of each story as a standalone slice of functionality that can be:
  - Developed independently
  - Tested independently
  - Deployed independently
  - Demonstrated to users independently
-->

### User Story 1 - [Brief Title] (Priority: P1)

[Describe this user journey in plain language]

**Why this priority**: [Explain the value and why it has this priority level]

**Independent Test**: [Describe how this can be tested independently - e.g., "Can be fully tested by [specific action] and delivers [specific value]"]

**Acceptance Scenarios**:

1. **Given** [initial state], **When** [action], **Then** [expected outcome]
2. **Given** [initial state], **When** [action], **Then** [expected outcome]

---

### User Story 2 - [Brief Title] (Priority: P2)

[Describe this user journey in plain language]

**Why this priority**: [Explain the value and why it has this priority level]

**Independent Test**: [Describe how this can be tested independently]

**Acceptance Scenarios**:

1. **Given** [initial state], **When** [action], **Then** [expected outcome]

---

### User Story 3 - [Brief Title] (Priority: P3)

[Describe this user journey in plain language]

**Why this priority**: [Explain the value and why it has this priority level]

**Independent Test**: [Describe how this can be tested independently]

**Acceptance Scenarios**:

1. **Given** [initial state], **When** [action], **Then** [expected outcome]

---

[Add more user stories as needed, each with an assigned priority]

### Edge Cases

<!--
  ACTION REQUIRED: The content in this section represents placeholders.
  Fill them out with the right edge cases.
-->

- What happens when [boundary condition]?
- How does system handle [error scenario]?

## Requirements *(mandatory)*

<!--
  ACTION REQUIRED: The content in this section represents placeholders.
  Fill them out with the right functional requirements.
-->

### Functional Requirements

- **FR-001**: System MUST [specific capability, e.g., "allow users to create accounts"]
- **FR-002**: System MUST [specific capability, e.g., "validate email addresses"]  
- **FR-003**: Users MUST be able to [key interaction, e.g., "reset their password"]
- **FR-004**: System MUST [data requirement, e.g., "persist user preferences"]
- **FR-005**: System MUST [behavior, e.g., "log all security events"]

*Example of marking unclear requirements:*

- **FR-006**: System MUST authenticate users via [NEEDS CLARIFICATION: auth method not specified - email/password, SSO, OAuth?]
- **FR-007**: System MUST retain user data for [NEEDS CLARIFICATION: retention period not specified]

### Key Entities *(include if feature involves data)*

- **[Entity 1]**: [What it represents, key attributes without implementation]
- **[Entity 2]**: [What it represents, relationships to other entities]

## Database Migrations *(Laravel services — include when schema changes)*

<!--
  Omit this section for ui/-only features with no schema impact.
-->

| Migration | Table / change | Notes |
|-----------|----------------|-------|
| `[timestamp]_[name].php` | [table] | [columns, indexes, FKs] |

- Backfill or data migration steps: [if any]
- Rollback risk: [if any]

## API Contract *(include when exposing or changing HTTP APIs)*

<!--
  Detail endpoints in spec; full contract MAY live in plan.md contracts/ for /speckit-plan.
  UI consumes APIs via ui/src/api/ and Vite proxy /svc/{service}.
-->

| Method | Path | Request | Response | Errors |
|--------|------|---------|----------|--------|
| [GET/POST/...] | [/inventory/...] | [body/query] | [shape] | [4xx/5xx] |

## Workflows & Activities *(Laravel services — include when using laravel-workflow)*

<!--
  Omit for CRUD-only or UI-only features.
-->

| Workflow | Activities | Trigger | Dispatched event |
|----------|------------|---------|------------------|
| [WorkflowName] | [Activity1, Activity2] | [API/event] | [EventName] |

- **Correlation ID**: [how idempotency is preserved]
- **State transitions**: [Spatie states affected]

## Cross-Module Scope *(include when both ui/ and *-service/ change)*

| Module | Changes |
|--------|---------|
| `[inventory-service]` | [API, models, migrations] |
| `ui/` | [pages, api client, routes] |

- Primary spec module: [which module owns spec.md path]
- Integration: [HTTP via /svc/inventory | events | both]

## Success Criteria *(mandatory)*

<!--
  ACTION REQUIRED: Define measurable success criteria.
  These must be technology-agnostic and measurable.
-->

### Measurable Outcomes

- **SC-001**: [Measurable metric, e.g., "Users can complete account creation in under 2 minutes"]
- **SC-002**: [Measurable metric, e.g., "System handles 1000 concurrent users without degradation"]
- **SC-003**: [User satisfaction metric, e.g., "90% of users successfully complete primary task on first attempt"]
- **SC-004**: [Business metric, e.g., "Reduce support tickets related to [X] by 50%"]

## Assumptions

<!--
  ACTION REQUIRED: The content in this section represents placeholders.
  Fill them out with the right assumptions based on reasonable defaults
  chosen when the feature description did not specify certain details.
-->

- [Assumption about target users, e.g., "Users have stable internet connectivity"]
- [Assumption about scope boundaries, e.g., "Mobile support is out of scope for v1"]
- [Assumption about data/environment, e.g., "Existing authentication system will be reused"]
- [Dependency on existing system/service, e.g., "Requires access to the existing user profile API"]
