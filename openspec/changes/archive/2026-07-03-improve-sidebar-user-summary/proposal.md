## Why

The sidebar footer currently shows only a notification bell, the user's first name, and logout. It should provide a compact identity and role summary so the user can see who is logged in, their principal Taller, their masonic registration, and their administrative badges without leaving the application menu.

## What Changes

- Redesign the lower-left sidebar section into a structured user summary.
- Show the user's first and last name at the top of the section, aligned left.
- Place the notification bell on the same top row, aligned right.
- Show the user's principal Taller below the name row as `number + name`.
- If the principal Taller name is too long for one line, truncate it with an ellipsis.
- Show only the user's masonic registration number below the principal Taller.
- Show role badges below the masonic registration when applicable, including Superadmin and Admin de Taller badges.
- When hovering or focusing the Admin badge, show a list of the Taller name(s) where the user is admin.
- Extend the authenticated user payload as needed so the shell can render last name, masonic registration, principal Taller, and administered Talleres.
- Preserve existing notification bell and logout behavior.

## Capabilities

### New Capabilities
- `sidebar-user-summary`: Authenticated sidebar footer identity, masonic context, notification placement, and role badge behavior.

### Modified Capabilities

## Impact

- Backend/API: `/me`, login, and register user payloads need additional non-sensitive fields required by the shell.
- Frontend types: authenticated user type needs last name, masonic id, principal Taller, and administered Talleres.
- Frontend UI: `AppLayout`, sidebar styles, notification bell placement, admin badge tooltip/popover.
- Tests: backend payload tests and frontend layout/component tests for truncation, badge visibility, and admin Taller list behavior.
