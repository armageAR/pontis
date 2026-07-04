## 1. Backend

- [x] 1.1 Include pending and correction-requested workshop join requests in the dashboard pending validation count for Superadmin and scoped Admin de Taller users.
- [x] 1.2 Decide whether Validaciones will reuse dashboard pending request data or use a dedicated scoped endpoint.
- [x] 1.3 If needed, add a scoped endpoint for pending/correction-requested join requests with the same Superadmin/Admin de Taller authorization rules.
- [x] 1.4 Preserve existing approve, reject, and request-correction endpoint behavior.

## 2. Frontend Data

- [x] 2.1 Add or reuse API helpers for loading scoped join requests in Administración → Validaciones.
- [x] 2.2 Ensure join request rows include Hermano, request date, requested Taller, membership status, correction notes, and action identifiers.

## 3. Validaciones UI

- [x] 3.1 Add a join request section/table to Administración → Validaciones.
- [x] 3.2 Show `Aceptar`, `Rechazar`, `Pedir corrección`, and `Ver` actions for each join request row.
- [x] 3.3 Move the existing join request detail/correction modal behavior from Panel into the Validaciones surface or a shared component.
- [x] 3.4 Remove resolved join request rows after successful approve/reject actions.
- [x] 3.5 Keep correction-requested rows visible until resolved or otherwise no longer pending reviewer action.
- [x] 3.6 Show an appropriate empty state when there are no join requests in the actor's scope.

## 4. Panel UI

- [x] 4.1 Remove the standalone `Solicitudes de ingreso` review section from Panel.
- [x] 4.2 Keep the `Administracion` box visible for Admin de Taller and Superadmin users.
- [x] 4.3 Ensure the red indicator appears when pending join requests exist, even if there are no grade, position, or identity validations.
- [x] 4.4 Ensure clicking the `Administracion` box opens Administración → Validaciones where join requests are visible.

## 5. Verification

- [x] 5.1 Add backend tests for pending validation count including join requests with Admin de Taller and Superadmin scope.
- [x] 5.2 Add frontend tests that Panel does not render `Solicitudes de ingreso` and does render the administration indicator.
- [x] 5.3 Add frontend tests for join request rows/actions in Administración → Validaciones.
- [x] 5.4 Add frontend tests for view details and request-correction modal behavior in Validaciones.
- [x] 5.5 Run affected backend tests.
- [x] 5.6 Run affected frontend checks.
