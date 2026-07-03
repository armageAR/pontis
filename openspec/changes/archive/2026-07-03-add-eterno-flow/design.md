## Context

The O Eterno state must preserve institutional history while preventing access, search visibility, contact, and active publication exposure.

## Goals / Non-Goals

**Goals:**
- Add administrative mark/revert flow.
- Exclude O Eterno users from active community interactions.
- Preserve profile history.

**Non-Goals:**
- No hard deletion.
- No public memorial page in V1.

## Decisions

- Use existing masonic/user status fields with consistent checks.
- Superadmin can mark/revert; workshop admin can mark members in their workshop.
- Close pending contact requests and unpublish active publications.

## Risks / Trade-offs

- Incomplete exclusion across endpoints -> Mitigate with tests on login, people, explore, contact, and publications.
