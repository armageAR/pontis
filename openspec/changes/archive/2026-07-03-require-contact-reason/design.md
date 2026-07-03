## Context

Contact requests currently depend mainly on free text. A required reason creates context, discourages curiosity-driven contact, and helps recipients decide.

## Goals / Non-Goals

**Goals:**
- Require a reason category and message for contact requests.
- Preserve source context for recipient and audit.

**Non-Goals:**
- No automated approval of contact quality.

## Decisions

- Store reason type plus optional source entity references.
- Keep the message required with bounded length.
- Show reason prominently in received and sent request views.

## Risks / Trade-offs

- More friction to contact -> Mitigate with concise reason options and sensible defaults from source action.
