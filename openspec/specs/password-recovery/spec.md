# password-recovery Specification

## Purpose
TBD.

## Requirements

### Requirement: Forgot password request
The system SHALL provide a public forgot-password flow with neutral responses.

#### Scenario: User requests reset
- **WHEN** a visitor submits an email to forgot-password
- **THEN** the system MUST return a neutral response
- **AND** MUST NOT reveal whether the email exists

### Requirement: Password reset token
The system SHALL use expiring one-time reset tokens.

#### Scenario: User resets password with valid token
- **WHEN** a user submits a valid token, email, and new password
- **THEN** the system MUST update the password
- **AND** invalidate the token and active sessions/tokens

### Requirement: Reset preserves account restrictions
The system SHALL not change account access status during password reset.

#### Scenario: Suspended user resets password
- **WHEN** a suspended user completes password reset
- **THEN** the password MUST be changed
- **AND** login restrictions for suspended status MUST remain
