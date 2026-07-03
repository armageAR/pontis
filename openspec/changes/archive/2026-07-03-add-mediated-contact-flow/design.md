## Context

Pontis supports anonymous or identity-reserved results. Contact must be possible without revealing the hidden user's id or identity before consent.

## Goals / Non-Goals

**Goals:**
- Allow contact requests from anonymous results.
- Preserve hidden identity until acceptance.
- Support request for more information before acceptance.

**Non-Goals:**
- No live chat.
- No public directory of anonymous users.

## Decisions

- Store mediated requests against the real target internally, but expose a mediated reference externally.
- Notifications and API payloads use neutral wording until consent.
- Acceptance resolves shared data according to contact consent rules.

## Risks / Trade-offs

- Id leakage through URLs or payloads -> Mitigate with response serializers and opaque references.
- Complex state machine -> Keep states limited: pending, info_requested, accepted, rejected, cancelled, closed.
