# Tasks: Order List Pagination

## Phase 1: Backend (sales-service)

- [x] T001 Validate `page` query on `GET /orders` and return 422 for invalid values
- [x] T002 Clamp requested page to last valid page when beyond range
- [x] T003 Add `OrderListPaginationTest` feature tests + PHPUnit bootstrap

## Phase 2: UI (ui/)

- [x] T004 Sync page state from API `current_page` after each fetch
- [x] T005 Keep auto-poll on current page; only jump to page 1 after successful create
- [x] T006 Improve empty-state copy when a non-first page has no rows

## Phase 3: Verification

- [x] T007 Run sales-service PHPUnit and `ui` vitest
