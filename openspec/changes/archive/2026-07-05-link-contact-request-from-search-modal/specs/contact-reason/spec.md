## ADDED Requirements

### Requirement: Search modal contact requires explicit reason selection
The contact request flow opened from the search profile modal SHALL require the requester to choose a reason before submitting.

#### Scenario: User opens contact flow from search modal
- **WHEN** a Hermano starts a contact request from the search profile modal
- **THEN** the reason field MUST require an explicit user selection
- **AND** the system MUST NOT submit the request with an implicit default reason

#### Scenario: User submits without selecting reason
- **WHEN** a Hermano submits the contact request from the search profile modal without selecting a reason
- **THEN** the system MUST reject the submission
- **AND** no contact request MUST be created
