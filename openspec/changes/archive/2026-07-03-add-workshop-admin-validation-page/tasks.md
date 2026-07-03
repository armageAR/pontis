## 1. Backend

- [x] 1.1 Extend the dashboard response with a pending masonic validation count scoped to the authenticated actor.
- [x] 1.2 Ensure the pending count includes declared degree and position records for administered Talleres and global records for Superadmin.
- [x] 1.3 Add backend feature tests for dashboard pending validation count scoping.

## 2. Frontend Data

- [x] 2.1 Update dashboard API types to include the pending masonic validation count.
- [x] 2.2 Add or reuse API helpers for loading pending degree and position validations and accepting/rejecting each type.
- [x] 2.3 Normalize pending degree and position records into a shared table row model with record type, Hermano, request date, Taller, details, and actions.

## 3. Panel Entry Point

- [x] 3.1 Show the `Administracion` Panel box for Admin de Taller and Superadmin users.
- [x] 3.2 Set the box text to `tareas de administracion en tu taller`.
- [x] 3.3 Navigate from the box to the administration page.
- [x] 3.4 Show a red upper-right pending indicator on the box when the dashboard pending validation count is greater than zero.

## 4. Administration Page

- [x] 4.1 Add the Admin de Taller validation table to the administration page.
- [x] 4.2 Display pending validation rows with Hermano, request date, requested Taller, and actions columns.
- [x] 4.3 Implement `Aceptar` and `Rechazar` row actions and remove resolved rows after successful action.
- [x] 4.4 Show a clear empty state when there are no pending validations.

## 5. Shared Detail Modal

- [x] 5.1 Extract or create a reusable masonic validation detail modal component.
- [x] 5.2 Wire the table `Ver` action to open the shared modal.
- [x] 5.3 Show all available validation details in the modal, including Hermano, request date, Taller, record type, requested data, notes, and status.
- [x] 5.4 Provide accept and reject actions from the modal using the same handlers as the row actions.
- [x] 5.5 Reuse the modal in the Superadmin validation flow where validation details are shown.

## 6. Verification

- [x] 6.1 Add frontend tests or component coverage for Panel box visibility and pending indicator behavior.
- [x] 6.2 Add frontend tests or component coverage for table rendering, empty state, and modal actions.
- [x] 6.3 Run affected backend tests.
- [x] 6.4 Run affected frontend checks.
