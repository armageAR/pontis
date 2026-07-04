## Context

The dashboard endpoint currently returns `pending_requests` for workshop join requests, and `DashboardPage` renders them in a dedicated "Solicitudes de ingreso" section. The same Panel also has an "Administracion" card that navigates to `/administracion`, where the Validaciones tab already groups degree, position, and sensitive identity reviews.

This change makes Administración → Validaciones the single review surface for Admin de Taller authorization tasks, including workshop join requests.

## Goals / Non-Goals

**Goals:**
- Display pending/correction-requested workshop join requests in Administración → Validaciones.
- Remove the standalone join-request review section from Panel content.
- Keep the Panel "Administracion" card and red indicator as the entry point.
- Include workshop join requests in the pending count that drives the red indicator.
- Preserve existing join approval, rejection, and correction behavior.

**Non-Goals:**
- No change to membership statuses or join-request lifecycle.
- No change to who can approve join requests: Admin de Taller for their Talleres and Superadmin globally.
- No change to the user's own membership notification flow.
- No new backend table or migration.

## Decisions

- Reuse the existing join request action endpoints.
  - Rationale: `/admin/workshops/{workshop}/join-requests/{user}/approve|reject|request-correction` already represents the domain actions and authorization boundary.
- Move the UI that currently lives in `DashboardPage` into the Validaciones surface, likely as a `JoinRequestValidationSection` or shared component.
  - Rationale: this reduces duplicate review surfaces and keeps all pending validation work together.
- Include pending and correction-requested join requests in the dashboard pending validation count.
  - Rationale: the red indicator should reflect all tasks available in Administración → Validaciones.
- Keep dashboard membership notifications for the requesting user separate.
  - Rationale: those notifications inform the requester about outcomes and are not reviewer tasks.

## Risks / Trade-offs

- Dashboard may still need to fetch pending join requests for other reasons. -> Prefer moving detailed pending join request fetches to the Validaciones page and keeping dashboard lightweight with count/indicator only.
- Reusing the existing dashboard `pending_requests` payload in Validaciones can couple that page to dashboard data. -> If needed, add a dedicated endpoint for scoped join requests while preserving the action endpoints.
- The empty state must account for multiple validation sections. -> Show section-level empty states or an aggregate empty state only when all validation categories are empty.
