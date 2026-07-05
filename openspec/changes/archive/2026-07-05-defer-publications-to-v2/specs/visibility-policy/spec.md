## MODIFIED Requirements

### Requirement: Central visibility decisions
The system SHALL resolve profile visibility through a central policy service in V1, while publication visibility rules remain dormant until V2.

#### Scenario: Endpoint checks profile visibility
- **WHEN** an endpoint needs to decide whether a viewer can see a profile block
- **THEN** it MUST use the central visibility policy
- **AND** it MUST NOT duplicate visibility logic inline

#### Scenario: Endpoint would check publication visibility in V1
- **WHEN** an endpoint would expose publication visibility behavior
- **THEN** the system MUST block the publication endpoint as unavailable in V1
- **AND** it MUST NOT return publication visibility results to users

### Requirement: Visibility preview
The central policy SHALL support previews of visible profile or sensitive-action data in V1, but publication preview access SHALL remain unavailable until V2.

#### Scenario: User requests publication visibility preview in V1
- **WHEN** a Hermano requests a preview before publishing
- **THEN** the system MUST reject the request as unavailable in V1
- **AND** it MUST NOT return publication audience or author preview data
