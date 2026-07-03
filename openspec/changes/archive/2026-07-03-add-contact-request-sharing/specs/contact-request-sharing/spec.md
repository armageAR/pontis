## ADDED Requirements

### Requirement: Requester selects shared data
The system SHALL let the requester choose which personal data blocks to share in each contact request.

#### Scenario: Requester creates contact request
- **WHEN** a Hermano creates a contact request
- **THEN** the system MUST persist the selected shared fields
- **AND** unselected fields MUST NOT be shown to the recipient

### Requirement: Identity reservation in requests
The system SHALL support contact requests where requester identity is reserved.

#### Scenario: Requester does not share identity
- **WHEN** the requester sends a request without identity sharing
- **THEN** the recipient view and notification MUST NOT reveal the requester name

### Requirement: Accepted request reveals only consented data
The system SHALL reveal only consented data when a contact request is accepted.

#### Scenario: Recipient accepts request
- **WHEN** a recipient accepts a contact request
- **THEN** both parties MUST see only the fields shared for the request
- **AND** rejected requests MUST NOT reveal additional data
