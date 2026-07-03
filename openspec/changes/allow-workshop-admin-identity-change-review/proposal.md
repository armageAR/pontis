## Why

Sensitive identity change requests for name, last name, DNI, and masonic registration are currently reviewable only by Superadmin. Admin de Taller users already validate institutional data for Hermanos in their Talleres, so they should also be able to review these identity changes when the requesting Hermano belongs to a Taller they administer.

## What Changes

- Allow Superadmin to continue listing and resolving all sensitive identity change requests.
- Allow Admin de Taller users to list pending/requires-info identity change requests only for Hermanos who belong to Talleres they administer.
- Allow Admin de Taller users to approve, reject, and request more information for those scoped requests.
- Prevent Admin de Taller users from seeing or resolving requests from Hermanos outside their administered Talleres.
- Keep regular Hermanos limited to their own requests.
- Notify both Superadmins and authorized Admin de Taller users when a new sensitive identity change request is created.
- Preserve audit logging for creation and resolution, with the actual reviewer recorded.
- Update project docs/decisions that currently say only Superadmin can approve these sensitive identity changes.

## Capabilities

### New Capabilities
- `sensitive-identity-change-requests`: Creation, listing, scoped review, notifications, and audit behavior for name, last name, DNI, and masonic registration change requests.

### Modified Capabilities

## Impact

- Backend: `ChangeRequestController` index/review authorization, notification recipient selection, and tests.
- Frontend: change request page role checks must treat authorized Admin de Taller users as reviewers, not only Superadmin.
- Dashboard/administration integration may need pending-count/list routing if identity changes are surfaced with other admin tasks.
- Documentation: update permissions/decisions/workflows references that say Admin de Taller cannot approve these identity-sensitive changes.
