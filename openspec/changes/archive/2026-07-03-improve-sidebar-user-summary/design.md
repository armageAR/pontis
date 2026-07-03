## Context

`AppLayout` renders the persistent sidebar and currently places `NotificationBell`, the user's first name, and logout in the sidebar footer. The authenticated user payload returned by login/register/`/me` currently includes only id, name, email, role, status, and email verification timestamp, so the shell does not have enough data to show last name, principal Taller, masonic registration, or administered Talleres.

The profile workshops endpoint already exposes membership role and principal Taller information, but the sidebar should not need to fetch that endpoint separately on every layout mount. The shell can render from the authenticated user context if the auth payload includes a compact summary.

## Goals / Non-Goals

**Goals:**
- Redesign the sidebar footer into a compact identity summary.
- Keep the notification bell in the footer, aligned to the right of the name row.
- Show principal Taller, masonic registration, and applicable role badges.
- Expose Admin de Taller badge details on hover and keyboard focus.
- Extend the authenticated user payload with only the fields needed by the shell.

**Non-Goals:**
- No change to profile editing or Taller membership management.
- No new notification behavior.
- No change to authorization rules for admin or superadmin.
- No public exposure of this data outside the authenticated user's own shell.

## Decisions

- Extend `AuthController::userPayload` with `last_name`, `masonic_id`, `principal_workshop`, and `admin_workshops`.
  - Rationale: login, register, and `/me` all use this payload, so the shell receives consistent data after authentication and refresh.
  - Alternative considered: call `/profile/workshops` from `AppLayout`. Rejected because it introduces an extra request for every app shell render and duplicates session context.
- Represent principal and admin Talleres as compact objects: `id`, `number`, and `name`.
  - Rationale: those are the only fields needed by the sidebar.
- Use CSS single-line truncation for the principal Taller row.
  - Rationale: this guarantees long Taller names do not break the fixed sidebar width.
- Implement the Admin badge list as a hover/focus popover or tooltip anchored to the badge.
  - Rationale: the user explicitly requested disclosure on hover; focus support keeps it keyboard accessible.
- Keep logout in the footer below the requested identity summary unless implementation confirms a better existing location.
  - Rationale: the request changes the identity section but does not remove logout behavior.

## Risks / Trade-offs

- Auth payload grows slightly. -> Limit additions to self-context fields required by the shell.
- The current `NotificationBell` positions its dropdown based on its button; moving it into a horizontal row may require position checks. -> Verify the dropdown still opens inside the viewport on desktop and mobile.
- Hover-only disclosure can be inaccessible on touch devices. -> Also open the Admin Taller list on focus and allow the badge to expose a title/aria label with the same content.
