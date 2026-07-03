## ADDED Requirements

### Requirement: Admin can mark O Eterno
The system SHALL allow authorized admins to mark a Hermano as O Eterno.

#### Scenario: Superadmin marks O Eterno
- **WHEN** a Superadmin marks a Hermano as O Eterno
- **THEN** the system MUST persist the state
- **AND** UI labels MUST display "O Eterno"

### Requirement: O Eterno blocks access and interaction
The system SHALL prevent O Eterno users from logging in or participating in active contact flows.

#### Scenario: O Eterno user attempts login
- **WHEN** a user marked O Eterno attempts to log in
- **THEN** the system MUST deny access

#### Scenario: Contact request targets O Eterno user
- **WHEN** a Hermano attempts to contact a user marked O Eterno
- **THEN** the system MUST reject the contact request

### Requirement: O Eterno preserves history but hides active discovery
The system SHALL preserve historical records while excluding O Eterno users from search, explore, and active publications.

#### Scenario: Search runs after O Eterno marking
- **WHEN** community search or explore is queried
- **THEN** O Eterno users and their active publications MUST NOT appear
- **AND** historical degrees, positions, and memberships MUST remain stored
