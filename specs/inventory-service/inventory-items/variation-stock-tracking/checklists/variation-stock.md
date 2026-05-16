# Requirements Quality Checklist: Variation & Multi-Location Stock

**Purpose**: Unit tests for requirements writing — validate spec clarity, completeness, and consistency before implementation/PR review (NOT implementation verification).  
**Created**: 2026-05-16  
**Feature**: [spec.md](../spec.md) | **Plan**: [plan.md](../plan.md) | **Contract**: [inventory-variations-api.md](../contracts/inventory-variations-api.md)  
**Audience**: PR reviewer | **Depth**: Standard

**Focus areas**: Per-variation stock, storage locations & transfers, legacy compatibility, operator UI constraints, reservation integration.

---

## Requirement Completeness

- [ ] CHK001 Are variation dimension types (size, color, material) and combination rules (Cartesian product) explicitly required in the spec? [Completeness, Spec §FR-001, FR-002, Assumptions]
- [ ] CHK002 Is the requirement for a distinct business identifier per variation (SKU/code) defined with uniqueness scope? [Completeness, Spec §FR-009]
- [ ] CHK003 Are per-location stock balance requirements documented for both variations and legacy items? [Completeness, Spec §FR-003, FR-013]
- [ ] CHK004 Are stock-in, stock-out, and transfer movement types each defined with required fields (product, location(s), quantity)? [Completeness, Spec §FR-004, FR-005, FR-014, FR-016]
- [ ] CHK005 Are admin-managed storage location lifecycle requirements (create, list, deactivate) specified or marked as out of scope? [Gap, Spec Assumptions vs Clarifications — location ownership unresolved]
- [ ] CHK006 Are reporting requirements defined for variation-level, item-aggregated, and location-filtered views? [Completeness, Spec §FR-006, User Story 4]
- [ ] CHK007 Are legacy item requirements explicitly preserved for list, create, update, movement, and reservation flows? [Completeness, Spec §FR-007, FR-012, User Story 5]
- [ ] CHK008 Are UI interaction constraints (minimal modals) specified for all primary operator flows listed? [Completeness, Spec §UI-001, UI-002, FR-017]
- [ ] CHK009 Are reservation/order integration requirements documented for optional variation and location identifiers? [Completeness, Spec §FR-012, Assumptions]
- [ ] CHK010 Is conversion of legacy items to variation-based items explicitly excluded with rationale? [Completeness, Spec Edge Cases, Assumptions]

---

## Requirement Clarity

- [ ] CHK011 Is "received and approved" return status defined with unambiguous state names or equivalence rules? [Clarity, Gap — refund feature N/A; inventory return gate N/A unless cross-service return spec exists]
- [ ] CHK012 Is transferable quantity defined as available minus reserved (or other formula) without ambiguity? [Clarity, Spec Edge Cases, Assumptions]
- [ ] CHK013 Is "aggregated item-level stock" defined as sum across variations and/or locations with explicit inclusion of reserved quantities? [Clarity, Spec §FR-006, Edge Cases]
- [ ] CHK014 Are partial refund vs partial stock movement semantics distinguished if both domains coexist in monorepo? [Consistency, Gap — ensure inventory spec does not conflate with payment refunds]
- [ ] CHK015 Is the rule for generating variation SKUs (derivation from parent SKU) specified or delegated only to plan? [Clarity, Spec §FR-009 vs plan.md — risk of spec/plan drift]
- [ ] CHK016 Is "one product per transfer transaction" scope clearly bounded vs bulk multi-line transfers? [Clarity, Spec Edge Cases]
- [ ] CHK017 Are low-stock threshold criteria and alert behavior quantified or intentionally deferred? [Clarity, Gap, User Story 4 scenario 3]
- [ ] CHK018 Is the maximum practical variation count per item stated for reporting usability (SC-005)? [Clarity, Spec §SC-005]

---

## Requirement Consistency

- [ ] CHK019 Do stock-in/stock-out requirements consistently require location selection across variation and legacy paths? [Consistency, Spec §FR-004, FR-005, User Story 2 scenario 5]
- [ ] CHK020 Are transfer atomicity requirements aligned between edge cases and success criteria (SC-007)? [Consistency, Spec Edge Cases, §SC-007]
- [ ] CHK021 Do UI-001/UI-002 align with FR-017 without contradicting allowed confirmation modals? [Consistency, Spec §UI-001, UI-002, FR-017]
- [ ] CHK022 Are legacy quantity fields on inventory items consistent with per-location balance model in all user stories? [Consistency, Spec §FR-007, plan Complexity Tracking]
- [ ] CHK023 Do functional requirement IDs cover all user story acceptance scenarios without orphan scenarios? [Consistency, Traceability, Spec User Stories vs FR-001–FR-017]
- [ ] CHK024 Does the API contract in `contracts/` align with functional requirements (endpoints, payloads, error cases)? [Consistency, Contract vs Spec §FR-004–FR-016]

---

## Acceptance Criteria Quality

- [ ] CHK025 Can SC-002 (zero cross-variation leakage) be verified from requirements without implementation knowledge? [Measurability, Spec §SC-002]
- [ ] CHK026 Can SC-003 (aggregated equals sum) be applied to both single-location and all-locations views? [Measurability, Spec §SC-003]
- [ ] CHK027 Is SC-006 (transfer under 2 minutes, non-modal UI) testable as a requirements outcome vs usability study only? [Measurability, Spec §SC-006]
- [ ] CHK028 Are acceptance scenarios for User Story 3 (transfer) sufficient to prove FR-014 and FR-015 without gaps? [Acceptance Criteria Quality, User Story 3 vs FR-014, FR-015]
- [ ] CHK029 Does each P1 user story include an independent test description that maps to at least one success criterion? [Traceability, User Stories 1–3, 5 vs SC-001–SC-004, SC-007]

---

## Scenario Coverage

- [ ] CHK030 Are primary flows documented: create variations, stock-in, stock-out, transfer, report, legacy operations? [Coverage, User Stories 1–5]
- [ ] CHK031 Are alternate flows defined (add dimension value after creation, partial returns N/A for inventory)? [Coverage, User Story 1 scenario 3]
- [ ] CHK032 Are exception flows defined for insufficient stock at variation+location granularity? [Coverage, User Story 2 scenario 3, FR-005]
- [ ] CHK033 Are recovery/rollback requirements defined for failed transfers (partial DB state)? [Coverage, Spec Edge Cases — atomic transfer stated]
- [ ] CHK034 Are requirements defined for mixed catalog display (legacy vs variation items in one list)? [Coverage, User Story 5 scenario 2, FR-011]
- [ ] CHK035 Are concurrent reservation scenarios against the same variation balance addressed in requirements? [Coverage, Gap, Spec Edge Cases partial]

---

## Edge Case Coverage

- [ ] CHK036 Are duplicate variation combination rules specified (reject vs merge)? [Edge Case, Spec §FR-010, Edge Cases]
- [ ] CHK037 Are dimension value deletion rules defined when stock exists at any location? [Edge Case, Spec Edge Cases]
- [ ] CHK038 Is same source/destination transfer rejection specified? [Edge Case, Spec §FR-015, User Story 3 scenario 4]
- [ ] CHK039 Are historical movement requirements for legacy items defined without retroactive variation IDs? [Edge Case, Spec §FR-008, User Story 5 scenario 3]
- [ ] CHK040 Is behavior defined when only one variation is out of stock but siblings remain available? [Edge Case, Spec Edge Cases]
- [ ] CHK041 Are reserved-stock transfer restrictions documented for v1? [Edge Case, Spec Edge Cases, Assumptions]

---

## Non-Functional Requirements

- [ ] CHK042 Are performance expectations for variation generation and API latency captured in spec or only in plan? [Gap, plan.md Technical Context vs Spec — traceability risk]
- [ ] CHK043 Are audit/history retention requirements for stock movements specified beyond "movement history"? [Gap, Spec Key Entities, FR-016]
- [ ] CHK044 Are authorization/role requirements defined for admin location management vs operator movements? [Gap, Spec — admin catalog assumed in plan/research]
- [ ] CHK045 Are accessibility requirements for non-modal, inline UI flows specified or deferred? [Gap, Spec UI constraints]

---

## Dependencies & Assumptions

- [ ] CHK046 Is the dependency on sales/order service for variation_id and location_id in reservations documented with fallback behavior? [Dependency, Spec Assumptions, FR-012]
- [ ] CHK047 Is the default storage location for legacy migration explicitly required in spec assumptions? [Assumption, Spec Assumptions]
- [ ] CHK048 Does research.md decision on admin-managed locations need to be merged into spec clarifications? [Assumption, research.md §4 vs Spec Clarifications — gap]
- [ ] CHK049 Are out-of-scope items (legacy→variation conversion, bulk transfer) listed consistently in spec, plan, and tasks? [Consistency, Spec Edge Cases, tasks.md]

---

## Ambiguities & Conflicts

- [ ] CHK050 Is location management ownership (admin vs operator) resolved in spec text to avoid plan-only decisions? [Ambiguity, Clarifications session incomplete]
- [ ] CHK051 Does FR numbering order (FR-013 before FR-006) cause traceability confusion for reviewers? [Conflict risk, Spec FR section — documentation quality]
- [ ] CHK052 Are UI module requirements (ui/) explicitly bounded so inventory-service spec does not silently own all UX detail? [Ambiguity, Constitution module split vs Spec FR-017]

---

## Notes

- Use this checklist during PR review of **spec.md** changes and before `/speckit-implement`.
- Items marked `[Gap]` indicate likely spec amendments, not code defects.
- Pair with `checklists/requirements.md` (specify gate) — that file validates initial spec readiness; this file validates ongoing requirements quality by domain.
