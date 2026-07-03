# mediated-contact-flow Specification

## Purpose
TBD.

## Requirements

### Requirement: Anonymous result contact
The system SHALL allow a Hermano to request contact from an anonymous or identity-reserved result without revealing the target identity.

#### Scenario: User contacts anonymous result
- **WHEN** a Hermano starts contact from an anonymous result
- **THEN** the system MUST create a contact request for the real target internally
- **AND** the response shown to the requester MUST NOT reveal the target's identity or user id

### Requirement: Mediated request recipient view
The recipient SHALL see the message, context, and only requester-shared data.

#### Scenario: Recipient opens mediated request
- **WHEN** the recipient views a mediated contact request
- **THEN** the recipient MUST see the request context
- **AND** the recipient MUST see only the requester data explicitly shared for that request

### Requirement: Mediated acceptance
The system SHALL reveal data only after acceptance and only according to consent.

#### Scenario: Recipient accepts mediated request
- **WHEN** the recipient accepts the request
- **THEN** the system MUST reveal only consented data to each party
- **AND** the system MUST NOT reveal unshared contact or identity fields
