## Context

Sensitive actions depend on visibility, consent, and audit rules. Users need a concise summary at the decision point rather than relying on hidden settings.

## Goals / Non-Goals

**Goals:**
- Show privacy summaries before sensitive actions.
- Reuse a common UI pattern.
- Prefer backend-resolved summaries when rules are complex.

**Non-Goals:**
- No long educational wizard.
- No change to the underlying visibility rules.

## Decisions

- Build a reusable privacy indicator component.
- Use compact summary text with an expandable detail.
- Add optional API support for resolved previews.

## Risks / Trade-offs

- Too much text near actions -> Mitigate with one-line summary and details toggle.
