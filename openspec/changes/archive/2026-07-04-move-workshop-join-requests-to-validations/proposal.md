## Why

Workshop join requests currently appear as a separate "Solicitudes de ingreso" section on the Panel, but Admin de Taller users expect pending authorization work to be handled from the "Administracion" box and its Validaciones page. This split makes the workflow hard to find and inconsistent with degree, position, and identity-change reviews.

## What Changes

- Move workshop join request review from the Panel content into Administración → Validaciones.
- The Panel should keep the "Administracion" box as the entry point for Admin de Taller tasks.
- The red pending indicator on the "Administracion" box must include pending workshop join requests, in addition to pending degree, position, and sensitive identity validations.
- Administración → Validaciones must show pending/correction-requested join requests for the actor's scope.
- Each join request row/card must show the requesting Hermano, request date, requested Taller, and actions.
- Available actions must include accept, reject, request correction, and view details.
- Admin de Taller users must see only join requests for Talleres they administer; Superadmin keeps global scope.
- The standalone "Solicitudes de ingreso" section should be removed from the Panel to avoid duplicate review surfaces.

## Capabilities

### New Capabilities

### Modified Capabilities
- `workshop-admin-validation-page`: Include workshop join requests in the administration Validaciones surface and pending indicator, and remove the separate Panel review section.

## Impact

- Backend: dashboard pending count must include pending/correction-requested join requests in the actor's scope; existing join request approval/rejection/correction endpoints can be reused.
- Frontend: `DashboardPage` stops rendering the join-request review section and keeps only the administration card/indicator; `DegreeValidationPage` or a shared validation page gains the join request table/section.
- API/client: existing dashboard `pending_requests` payload may be reused for the Validaciones surface or replaced by a scoped endpoint; avoid fetching hidden Panel-only data if not needed.
- Tests: update dashboard tests, add Validaciones tests for join request rows/actions/details, and backend tests for pending count scope.
