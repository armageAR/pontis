## Context

Current profile degree and position management allows self-management. The desired institutional model is hybrid: self-declaration with administrative validation.

## Goals / Non-Goals

**Goals:**
- Add declared/validated lifecycle to degrees and positions.
- Restrict edits after validation.
- Allow workshop admins to validate records tied to their workshops.

**Non-Goals:**
- No change to degree catalog semantics.
- No public exposure of validation workflow state.

## Decisions

- Add validation status, validator id, and validated timestamp to both tables.
- Existing records migrate as validated.
- Records created by admins start validated; self-created records start declared.

## Risks / Trade-offs

- Admin workload increases -> Mitigate with notifications and filtered pending views.
