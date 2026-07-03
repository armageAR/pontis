## Why

Admin de Taller users can validate pending masonic degree and position changes for their Talleres, but they need an explicit workspace from the Panel to find and resolve those pending validations. The dashboard should also surface when there is pending administrative work so admins do not need to search for it manually.

## What Changes

- Add an "Administracion" box to the Panel for users who administer at least one Taller.
- The box text SHALL be "tareas de administracion en tu taller".
- When the admin has pending masonic validations in their Talleres, show a red indicator in the upper-right of the Panel box.
- Clicking the box opens the workshop-admin administration page.
- The administration page shows a table of all pending degree and position validations for the admin's Talleres.
- Each row shows the requesting Hermano, request date, requested Taller, and actions.
- Row actions include accept, reject, and view.
- View opens a reusable validation detail modal showing all validation details and the same additional approve/reject controls available to Superadmin.
- The page and data remain workshop-scoped: Admin de Taller users only see validations for Talleres where they are admin; Superadmin behavior remains unchanged.

## Capabilities

### New Capabilities
- `workshop-admin-validation-page`: Navigation, pending indicator, list view, and detail modal for Admin de Taller validation work.

### Modified Capabilities

## Impact

- Frontend: `DashboardPage`, dashboard API types, administration route/page behavior, validation list/table UI, shared validation detail modal component.
- Backend/API: dashboard payload needs a pending validation count or equivalent summary for the authenticated actor.
- Existing validation endpoints for degrees and positions can be reused for list, accept, and reject actions because they already apply Superadmin/Admin de Taller scoping.
- Tests: backend dashboard count scoping; frontend/component behavior for Panel indicator, table rows, row actions, and modal reuse.
