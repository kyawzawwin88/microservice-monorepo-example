# Feature Specification: Waterline Workflow Monitor (UI Shell)

**Created**: 2026-05-16  
**Status**: migrated  
**Input**: Reverse-engineered from `ui/src/pages/Waterline.tsx`

## Specification Placement

| Field | Value |
|-------|-------|
| **Module** | ui |
| **Domain** | observability |
| **Workflow / capability** | waterline |

## User Scenarios & Testing

### User Story 1 - Monitor workflows per service (P1)

As a developer/operator, I want to embed each service's Waterline dashboard to inspect durable workflow runs.

**Acceptance Scenarios**:

1. **Given** Waterline page, **When** user selects service tab, **Then** iframe loads `http://localhost:{port}/waterline`.
2. **Given** external link, **When** "Open in New Tab" clicked, **Then** same URL opens in new browser tab.

## Requirements

- **FR-001**: Tabs for sales (:8001), invoice (:8002), payment (:8003), inventory (:8004).
- **FR-002**: iframe MUST remount on service change (`key={activeService}`).
- **FR-003**: Backend MUST expose Waterline via `laravel-workflow/waterline` (service provider per app).

## Cross-Module Scope

Embeds each service's Waterline UI — no direct API from React except iframe navigation.

## Success Criteria

- **SC-001**: User views running/completed workflow instances for NewOrder / CreateInvoice / etc. when workers are up.

## Assumptions

- Browser can reach `localhost` ports (local dev); Docker host networking may require URL adjustment outside local machine.
- Waterline is third-party OSS linked in page subtitle.
