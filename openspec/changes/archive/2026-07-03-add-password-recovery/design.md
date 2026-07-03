## Context

Users currently have no self-service password recovery. The flow must be public but resistant to enumeration and abuse.

## Goals / Non-Goals

**Goals:**
- Add forgot/reset password endpoints.
- Use expiring one-time tokens.
- Add neutral responses and rate limiting.
- Revoke active tokens after reset.

**Non-Goals:**
- No MFA recovery flow in this change.

## Decisions

- Use Laravel password reset token infrastructure where possible.
- Return the same forgot-password response regardless of email existence.
- Allow reset for suspended/inactive accounts but preserve their access restrictions.

## Risks / Trade-offs

- Email enumeration -> Mitigate with neutral responses and rate limits.
