## 1. Backend Payload

- [x] 1.1 Extend `AuthController::userPayload` with `last_name` and `masonic_id`.
- [x] 1.2 Add `principal_workshop` to the auth payload with id, number, and name for the active principal Taller.
- [x] 1.3 Add `admin_workshops` to the auth payload with id, number, and name for active Talleres where the user has admin role.
- [x] 1.4 Add or update backend tests for login/register/`/me` payload shape.

## 2. Frontend Types

- [x] 2.1 Update the authenticated `User` TypeScript type with the new sidebar summary fields.
- [x] 2.2 Ensure `AuthContext` continues to hydrate the same payload after login, register, and refresh.

## 3. Sidebar Footer UI

- [x] 3.1 Replace the current sidebar footer user area with a structured summary layout.
- [x] 3.2 Render first and last name on the top row aligned left.
- [x] 3.3 Move the notification bell to the top row aligned right.
- [x] 3.4 Render principal Taller number and name on one line below the name row.
- [x] 3.5 Apply single-line ellipsis truncation to long principal Taller names.
- [x] 3.6 Render only the masonic registration number when present.
- [x] 3.7 Render Superadmin and Admin badges when applicable.
- [x] 3.8 Keep logout available without disrupting the requested summary hierarchy.

## 4. Admin Badge Disclosure

- [x] 4.1 Add hover disclosure for the Admin badge listing all administered Taller names.
- [x] 4.2 Add keyboard focus disclosure for the Admin badge listing all administered Taller names.
- [x] 4.3 Ensure multiple administered Talleres are all visible in the disclosure.

## 5. Verification

- [x] 5.1 Add frontend tests for rendering name, notification bell placement, principal Taller, masonic id, and badges.
- [x] 5.2 Add frontend tests for Admin badge hover/focus disclosure.
- [x] 5.3 Verify long Taller names are visually truncated with ellipsis in desktop and mobile sidebar widths.
- [x] 5.4 Run affected backend tests.
- [x] 5.5 Run affected frontend checks.
