## Context

Several endpoints return related user records. Privacy must be enforced by API payloads, not only by UI rendering.

## Goals / Non-Goals

**Goals:**
- Send only fields authorized for the request context.
- Separate admin payloads from community payloads.
- Add API-level privacy tests.

**Non-Goals:**
- No encryption redesign.
- No removal of legitimate admin access.

## Decisions

- Introduce response resources/serializers for sensitive entities.
- Deny by default for sensitive fields.
- Treat notifications as payloads subject to the same minimum-data rule.

## Risks / Trade-offs

- Frontend may depend on broad payloads -> Mitigate by updating consumers alongside resources.
