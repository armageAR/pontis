# functional-audit Specification

## Purpose
TBD.

## Requirements

### Requirement: Sensitive actions are audited
The system SHALL record audit logs for relevant administrative and sensitive actions.

#### Scenario: Admin validates a user
- **WHEN** an admin approves, rejects, or requests correction for a user
- **THEN** the system MUST create an audit log with actor, action, target entity, decision, and timestamp

### Requirement: Audit logs avoid private content
Audit logs SHALL store references and minimal metadata, not sensitive content.

#### Scenario: Contact request is resolved
- **WHEN** a contact request is accepted or rejected
- **THEN** the audit log MUST reference the contact request id
- **AND** MUST NOT store private message content

### Requirement: Superadmin can query audit
The system SHALL allow only Superadmin users to query audit logs with basic filters.

#### Scenario: Non-superadmin queries audit
- **WHEN** a non-superadmin requests audit logs
- **THEN** the system MUST reject the request
