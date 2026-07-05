## ADDED Requirements

### Requirement: V1 seeders do not create publication demo data
The V1 demo/test seeders SHALL NOT create active or draft publication data for offers or needs.

#### Scenario: Demo seeders run in V1
- **WHEN** V1 seeders are executed
- **THEN** they MUST NOT create demo rows in publication resources such as `services` or `needs`
- **AND** they MUST NOT depend on publication categories for seeded user scenarios
