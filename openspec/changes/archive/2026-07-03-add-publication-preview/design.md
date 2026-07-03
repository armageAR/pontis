## Context

Publishing and republishing can expose publication data and author context. A preview step lets the author confirm visible data before activation.

## Goals / Non-Goals

**Goals:**
- Add preview before publish/republication.
- Show publication fields and author visibility result.
- Persist only after confirmation.

**Non-Goals:**
- No preview required for draft save.
- No change to publication lifecycle states.

## Decisions

- Use a modal or step after clicking Publicar.
- Resolve author visibility via frontend rules initially or backend preview if central policy is available.
- Confirm action performs the existing publish request.

## Risks / Trade-offs

- Preview drift from backend rules -> Mitigate by integrating with central visibility policy when available.
