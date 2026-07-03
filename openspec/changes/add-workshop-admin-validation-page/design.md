## Context

Pontis already has backend endpoints for pending masonic validations:
- `GET /admin/degree-validations`
- `POST /admin/degrees/{degree}/validate`
- `POST /admin/degrees/{degree}/reject`
- `GET /admin/position-validations`
- `POST /admin/positions/{userPosition}/validate`
- `POST /admin/positions/{userPosition}/reject`

Those endpoints are already role-scoped: Superadmin sees global pending records, and Admin de Taller sees only records tied to Talleres where they are admin. The frontend currently has an administration route and a `DegreeValidationPage`, but the Panel does not expose a dedicated administration card with a pending-work indicator for Admin de Taller users.

## Goals / Non-Goals

**Goals:**
- Add a Panel entry point for Admin de Taller administration work.
- Show a red pending indicator on that Panel box when the actor has pending degree or position validations in their Talleres.
- Provide a table-oriented administration page for pending masonic validations.
- Reuse a shared validation detail modal for row "Ver" actions and admin/superadmin validation controls.
- Keep data and actions scoped to the authenticated actor's administrative authority.

**Non-Goals:**
- No change to validation authorization rules.
- No new validation lifecycle states.
- No changes to degree progression or position period behavior.
- No exposure of validation data to users who are not Superadmin or Admin de Taller.
- No replacement of Superadmin administration workflows beyond sharing the modal component where applicable.

## Decisions

- Extend the dashboard payload with a pending masonic validation count.
  - Rationale: the Panel can render the red indicator without issuing extra validation-list requests.
  - Alternative considered: let the frontend call both validation endpoints from the Panel. Rejected because it duplicates page data loading and makes the dashboard depend on detail pagination.
- Use existing validation list/action endpoints for the administration page.
  - Rationale: authorization and scoping are already enforced in the backend.
  - Alternative considered: create a combined endpoint for table rows. This may be useful later, but is unnecessary unless pagination or mixed sorting across degrees and positions becomes complex.
- Normalize degree and position records into one frontend row model for the table.
  - Rationale: the UI needs one table with consistent columns and actions while preserving the underlying record type for accept/reject calls.
- Extract a reusable validation detail modal component.
  - Rationale: the "Ver" action and Superadmin validation detail should share markup, fields, and action behavior.
  - Alternative considered: duplicate modal markup per page. Rejected to avoid divergent approve/reject behavior and detail presentation.
- Route Admin de Taller users to the existing `/administracion` page, with the validations tab/view available and appropriate defaulting.
  - Rationale: the route already exists and can host both Superadmin and workshop-admin administration contexts.

## Risks / Trade-offs

- Combining degree and position validations client-side can produce local ordering differences if each endpoint paginates independently. -> For V1, fetch enough pending records for admin tasks or preserve endpoint order per type; consider a combined backend endpoint if pagination becomes a real limit.
- The existing validation page may not currently have a detail modal. -> Extract or create a shared `ValidationDetailModal` and wire both the new table "Ver" action and existing Superadmin flow through it.
- Dashboard pending count could become stale after resolving validations in the administration page. -> Refresh on Panel load and remove the indicator naturally on next dashboard fetch; no live update is required.
