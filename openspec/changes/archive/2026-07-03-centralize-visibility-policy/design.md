## Context

Visibility rules are currently resolved in multiple controllers and helpers. A central policy reduces drift and provides one place to reason about privacy behavior.

## Goals / Non-Goals

**Goals:**
- Centralize viewer/subject visibility decisions.
- Provide filtered payload builders or rule outputs.
- Support previews for publish/contact actions.

**Non-Goals:**
- No broad permission engine for V1.
- No change in default privacy posture.

## Decisions

- Extend or replace `ProfileVisibility` with a domain service. (Implemented as `App\Support\VisibilityPolicy`; `ProfileVisibility` removed.)
- Keep policy methods explicit rather than rule-table driven.
- Start with People, PublicProfile, Explore, Contact, and notifications.
- Two privacy fixes shipped with the migration (justified by the "anonymous appearance MUST NOT reveal identity fields" requirement, intentionally not parity):
  1. Contact requests no longer reveal the requestee's real name to the requester (sent list, store response, reject notification) when the requestee's identity is not visible to them; acceptance is the consent that reveals it.
  2. Explore anonymous masking was silently broken: assigning the masked author as a model attribute was overridden by the loaded Eloquent relation during serialization, leaking the real author. The policy now unloads the relation before masking.

## Risks / Trade-offs

- Large refactor surface -> Mitigate by migrating endpoint families incrementally with tests.
