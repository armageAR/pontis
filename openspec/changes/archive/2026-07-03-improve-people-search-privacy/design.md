## Context

Community people search currently includes sensitive identity fields in query matching and lacks profession-focused discovery. The change should remove inference risks while improving usefulness.

## Goals / Non-Goals

**Goals:**
- Remove email and masonic id from community search criteria.
- Add profession/occupation search respecting visibility.
- Preserve Superadmin admin search capability outside community search.

**Non-Goals:**
- No removal of admin user management search.
- No public search outside login.

## Decisions

- Split community `q` into allowed community criteria only.
- Add profession matching only when visible or anonymous appearance permits safe inclusion.
- Update placeholder/copy to remove email/matricula mention.

## Risks / Trade-offs

- Users may expect name/email lookup -> Mitigate by keeping admin lookup in admin area and improving profession search.
