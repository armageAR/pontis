## Context

Pontis needs traceability for sensitive administrative and consent-related actions without turning audit logs into a repository of private content.

## Goals / Non-Goals

**Goals:**
- Add audit log storage and centralized logger.
- Record relevant decisions and actors.
- Provide Superadmin query UI/API.

**Non-Goals:**
- No audit of every page view or search.
- No storage of sensitive free-text content unless strictly necessary.

## Decisions

- Store actor, action, entity type/id, decision, metadata, and timestamp.
- Metadata must use identifiers and concise non-sensitive facts.
- Logger service is called from controllers/services that make decisions.

## Risks / Trade-offs

- Missing events due to manual calls -> Mitigate with service conventions and tests per workflow.
