## ADDED Requirements

### Requirement: Search profile modal can initiate contact
The system SHALL let a Hermano initiate a mediated contact request from the profile modal opened by the Hermanos search flow.

#### Scenario: User opens profile from Hermanos search
- **WHEN** a Hermano opens another Hermano's profile modal from search results
- **AND** the profile allows contact requests
- **THEN** the modal MUST show a clear "Solicitar contacto" action

#### Scenario: User requests contact from search modal
- **WHEN** the user activates "Solicitar contacto" in the profile modal
- **THEN** the system MUST open the same contact request flow used by the full profile page
- **AND** the request MUST be sent with search context

#### Scenario: Contact request is sent from search modal
- **WHEN** the contact request is submitted successfully from the profile modal
- **THEN** the profile modal MUST remain open
- **AND** the modal MUST show a "Solicitud enviada" confirmation

### Requirement: Contact action respects request permission
The profile modal SHALL reflect when contact requests are not allowed.

#### Scenario: Profile does not allow contact requests
- **WHEN** a profile modal is opened for a Hermano with `can_request_contact` equal to false
- **THEN** the modal MUST show the "Solicitar contacto" action disabled
- **AND** hovering or focusing the disabled action MUST explain that the Hermano does not accept contact requests from search

### Requirement: Anonymous search result contact remains mediated
The profile modal SHALL support contact requests from anonymous or identity-reserved search results without revealing hidden identity.

#### Scenario: Anonymous profile allows contact
- **WHEN** a Hermano opens an anonymous profile modal from search results
- **AND** the profile allows contact requests
- **THEN** the modal MUST allow starting a contact request
- **AND** the UI MUST NOT reveal hidden identity fields while doing so
