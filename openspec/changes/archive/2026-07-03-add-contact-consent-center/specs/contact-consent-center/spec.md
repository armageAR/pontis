## ADDED Requirements

### Requirement: Contact consent defaults
The system SHALL allow each Hermano to configure default data blocks to share when initiating contact.

#### Scenario: User saves contact defaults
- **WHEN** a Hermano selects default shared contact data blocks
- **THEN** the system MUST persist those defaults for that Hermano
- **AND** the defaults MUST NOT make the data publicly visible

#### Scenario: User starts contact request
- **WHEN** a Hermano starts a contact request
- **THEN** the form MUST preselect the Hermano's saved contact defaults
- **AND** the Hermano MUST be able to change the selection for that request

### Requirement: Contact channel preferences
The system SHALL allow each Hermano to configure preferred contact channels.

#### Scenario: User selects preferred channels
- **WHEN** a Hermano selects email, phone, WhatsApp, or in-flow response as preferred channels
- **THEN** the system MUST persist the preference
- **AND** the preference MUST be shown only where contact consent is relevant

### Requirement: Contact availability sources
The system SHALL allow each Hermano to configure from where they accept contact requests.

#### Scenario: User limits contact sources
- **WHEN** a Hermano disables contact requests from searches
- **THEN** search results MUST NOT offer direct contact for that Hermano
- **AND** other enabled contact sources MUST continue to work
