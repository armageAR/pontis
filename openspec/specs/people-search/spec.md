# people-search Specification

## Purpose
TBD.

## Requirements

### Requirement: Community search excludes sensitive identity criteria
The community people search SHALL NOT match ordinary users by email or masonic id.

#### Scenario: User searches by email
- **WHEN** a non-admin Hermano searches people by another user's email
- **THEN** community search MUST NOT return that user due to email matching

#### Scenario: User searches by masonic id
- **WHEN** a non-admin Hermano searches people by masonic id
- **THEN** community search MUST NOT return that user due to masonic id matching

### Requirement: Profession search respects visibility
The community people search SHALL allow profession or occupation search only according to profile visibility rules.

#### Scenario: Profession is visible
- **WHEN** a Hermano searches by a visible profession
- **THEN** matching Hermanos MAY appear according to identity and anonymous appearance rules

#### Scenario: Profession is hidden
- **WHEN** a Hermano searches by a profession hidden from them
- **THEN** the hidden profession MUST NOT reveal an identified result

### Requirement: Admin sensitive search remains administrative
The system SHALL allow sensitive lookup by email or masonic id only in Superadmin administrative user management.

#### Scenario: Superadmin searches admin users
- **WHEN** a Superadmin searches the admin user list by email or masonic id
- **THEN** the administrative search MAY match those fields
- **AND** community people search MUST remain restricted
