## ADDED Requirements

### Requirement: V1 excludes publications
Pontis V1 SHALL exclude publication functionality from its active product scope.

#### Scenario: V1 scope is evaluated
- **WHEN** V1 functionality is reviewed
- **THEN** publications, offers, needs-as-publications, publication exploration, publication creation, and publication lifecycle management MUST be treated as out of scope
- **AND** existing dormant implementation MUST NOT make those features visible or accessible to users

#### Scenario: V1 user capabilities are presented
- **WHEN** the system presents what an active Hermano can do in V1
- **THEN** it MUST NOT include managing publications, offers, needs-as-publications, or publication visibility as active capabilities
