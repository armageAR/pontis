## 1. Backend Authorization

- [x] 1.1 Add a shared review authorization helper for sensitive identity change requests.
- [x] 1.2 Update `ChangeRequestController::index` so Superadmin sees all requests, Admin de Taller sees requests from Hermanos in administered Talleres, and regular Hermanos see only their own requests.
- [x] 1.3 Update approve, reject, and require-info actions to allow Superadmin or authorized Admin de Taller reviewers.
- [x] 1.4 Ensure forbidden Admin de Taller actions on unrelated requests do not mutate the request or target user.

## 2. Notifications and Audit

- [x] 2.1 Notify Superadmins and authorized Admin de Taller users when a sensitive identity change request is created.
- [x] 2.2 Deduplicate notification recipients across Superadmin/Admin roles and multiple Taller memberships.
- [x] 2.3 Preserve audit logging with the actual reviewer id for approval, rejection, and require-info actions.

## 3. Frontend

- [x] 3.1 Update change request page reviewer logic so authorized Admin de Taller users can review backend-returned scoped requests.
- [x] 3.2 Keep regular Hermano cancellation behavior unchanged.
- [x] 3.3 Ensure review modal actions work for Admin de Taller and Superadmin reviewers.

## 4. Documentation

- [x] 4.1 Update project permissions/decisions/workflows documentation that currently says only Superadmin approves sensitive identity changes.
- [x] 4.2 Document the scoped exception: Admin de Taller can approve name, last name, DNI, and masonic registration changes only for Hermanos in their administered Talleres.

## 5. Verification

- [x] 5.1 Add backend tests for Admin de Taller listing scoped requests.
- [x] 5.2 Add backend tests for Admin de Taller approving, rejecting, and requiring info on scoped requests.
- [x] 5.3 Add backend tests that Admin de Taller cannot see or resolve unrelated requests.
- [x] 5.4 Add backend tests for notification recipient selection and deduplication.
- [x] 5.5 Add frontend tests or coverage for reviewer controls shown to Admin de Taller and hidden from regular Hermanos.
- [x] 5.6 Run affected backend tests.
- [x] 5.7 Run affected frontend checks.
