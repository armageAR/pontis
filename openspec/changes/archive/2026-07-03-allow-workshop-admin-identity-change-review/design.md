## Context

`ChangeRequestController` manages sensitive changes for `name`, `last_name`, `dni`, and `masonic_id`. Today:
- Superadmin sees all requests in `index`.
- Non-superadmin users see only their own requests.
- `approve`, `reject`, and `requireInfo` abort unless the actor is Superadmin.
- New requests notify Superadmins only.

The requested behavior extends review authority to Admin de Taller users, but only for requests made by Hermanos who belong to Talleres that the actor administers.

## Goals / Non-Goals

**Goals:**
- Let Admin de Taller users list and resolve identity change requests for Hermanos in their administered Talleres.
- Keep Superadmin global authority unchanged.
- Keep regular Hermano self-service unchanged.
- Notify authorized Admin de Taller users when relevant requests are created.
- Keep audit reviewer attribution accurate.

**Non-Goals:**
- No new request fields or lifecycle statuses.
- No change to which fields can be requested: only `name`, `last_name`, `dni`, `masonic_id`.
- No automatic approval for Admin de Taller self-requests unless explicitly covered by a future change.
- No broad access to requests from unrelated Talleres.

## Decisions

- Add a shared authorization helper for review scope, e.g. `canReview(ChangeRequest $request, User $actor)`.
  - Rationale: `index`, `approve`, `reject`, and `requireInfo` need the same rule.
  - Rule: Superadmin can review all; Admin de Taller can review when the request owner has at least one active membership in a Taller where the actor has active admin role.
- Scope Admin de Taller index queries by request owner membership, not by the actor's own memberships alone.
  - Rationale: the sensitive change request belongs to the Hermano, so visibility depends on the request owner's Taller membership.
- Keep regular users seeing only their own requests.
  - Rationale: this preserves current privacy and self-service behavior.
- Notify Superadmins and Admin de Taller users for the request owner's active Talleres.
  - Rationale: all authorized reviewers should know there is pending work.
  - Implementation should deduplicate recipients when a user has multiple qualifying roles or administers multiple relevant Talleres.
- Update frontend reviewer detection from `isSuperAdmin` to `canReviewChangeRequests`.
  - Rationale: Admin de Taller users need the same review actions in the existing page when the backend returns scoped requests.

## Risks / Trade-offs

- A Hermano can belong to multiple Talleres, so multiple Admin de Taller users may be able to review the same request. -> This is acceptable because each belongs to an authorized Taller context; first resolution wins through existing status checks.
- Existing docs explicitly say Admin de Taller cannot approve sensitive identity changes. -> Update docs/decisions with this scoped exception to avoid future contradictions.
- Admin de Taller access to DNI/matricula is sensitive. -> Limit payloads to requests returned by the scoped reviewer query and keep audit logs for every resolution.
