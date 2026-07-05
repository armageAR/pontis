# contact-reason Specification

## Purpose
TBD.

## Requirements

### Requirement: Contact request reason is required
The system SHALL require a reason when creating a contact request.

#### Scenario: User submits without reason
- **WHEN** a Hermano submits a contact request without a reason
- **THEN** the system MUST reject the request
- **AND** no contact request MUST be created

### Requirement: Contact request message
The system SHALL require a meaningful bounded message for contact requests.

#### Scenario: User submits short or empty message
- **WHEN** a Hermano submits a request with an empty or invalid message
- **THEN** the system MUST reject the request
- **AND** show validation feedback
