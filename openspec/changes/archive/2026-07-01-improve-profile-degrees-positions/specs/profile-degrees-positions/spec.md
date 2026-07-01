## ADDED Requirements

### Requirement: Profile degree workshop selection uses only user's workshops
The profile degree form SHALL let the actor select only Talleres that belong to the target Hermano when creating or editing a degree record.

#### Scenario: User adds a degree from profile
- **WHEN** the degree form is opened from the profile page
- **THEN** the Taller selector MUST be a dropdown populated with the target Hermano's Talleres
- **AND** the form MUST NOT provide a global Taller search control

#### Scenario: Degree submitted with external workshop
- **WHEN** a degree create or update request includes a `workshop_id` for a Taller where the target Hermano does not belong
- **THEN** the system MUST reject the request
- **AND** the system MUST NOT create or update the degree record

### Requirement: Profile position workshop selection uses only user's workshops
The profile position form SHALL let the actor select only Talleres that belong to the target Hermano when creating or editing a position record.

#### Scenario: User adds a position from profile
- **WHEN** the position form is opened from the profile page
- **THEN** the Taller selector MUST be a dropdown populated with the target Hermano's Talleres
- **AND** the form MUST NOT provide a global Taller search control

#### Scenario: Position submitted with external workshop
- **WHEN** a position create or update request includes a `workshop_id` for a Taller where the target Hermano does not belong
- **THEN** the system MUST reject the request
- **AND** the system MUST NOT create or update the position record

### Requirement: Degrees progress in strict order
The system SHALL enforce the degree sequence `aprendiz` -> `companero` -> `maestro` for normal profile degree changes.

#### Scenario: First degree is aprendiz
- **WHEN** a Hermano has no degree history and a degree is added from the profile flow
- **THEN** the only valid degree value MUST be `aprendiz`

#### Scenario: Valid advancement from aprendiz to companero
- **WHEN** a Hermano's current degree is `aprendiz`
- **AND** a new degree is added with degree `companero`
- **THEN** the system MUST accept the new degree if all other validation passes
- **AND** the new current degree MUST become `companero`

#### Scenario: Valid advancement from companero to maestro
- **WHEN** a Hermano's current degree is `companero`
- **AND** a new degree is added with degree `maestro`
- **THEN** the system MUST accept the new degree if all other validation passes
- **AND** the new current degree MUST become `maestro`

#### Scenario: Degree skip is rejected
- **WHEN** a Hermano's current degree is `aprendiz`
- **AND** a new degree is submitted as `maestro`
- **THEN** the system MUST reject the request
- **AND** the Hermano's current degree MUST remain `aprendiz`

#### Scenario: Degree regression is rejected
- **WHEN** a Hermano's current degree is `maestro`
- **AND** a new degree is submitted as `companero`
- **THEN** the system MUST reject the request
- **AND** the Hermano's current degree MUST remain `maestro`

#### Scenario: Duplicate current degree is rejected
- **WHEN** a Hermano's current degree is `companero`
- **AND** a new degree is submitted as `companero`
- **THEN** the system MUST reject the request
- **AND** the system MUST NOT create a duplicate current degree record

### Requirement: Degree periods do not use manual end dates
The profile degree flow SHALL NOT allow users to enter or update a manual degree end date.

#### Scenario: User opens degree form
- **WHEN** the degree form is shown in the profile page
- **THEN** the form MUST show a required start date
- **AND** the form MUST NOT show a degree end date field

#### Scenario: Next degree starts
- **WHEN** a new valid degree starts for a Hermano
- **THEN** the previous degree MUST no longer be considered current
- **AND** the previous degree period MUST be treated as ending when the new degree starts

#### Scenario: Active Hermano has continuous degree state
- **WHEN** a Hermano is active
- **THEN** the system MUST be able to determine exactly one current degree
- **AND** the system MUST NOT represent the Hermano as having no current degree between historical degree records

### Requirement: Position periods keep optional end dates
The profile position flow SHALL keep optional end dates for cargos because a cargo can finish without another cargo beginning.

#### Scenario: User adds a position with no end date
- **WHEN** a position is added with a start date and no end date
- **THEN** the system MUST store the position as current or open-ended according to existing position rules

#### Scenario: User closes a position
- **WHEN** a position is updated with an end date after its start date
- **THEN** the system MUST preserve the historical position record
- **AND** the position MUST NOT grant active contextual permissions after its end date

### Requirement: Invalid profile degree and position actions explain the reason
The profile page SHALL show clear validation feedback when degree or position changes are rejected due to Taller membership or degree sequence.

#### Scenario: Degree sequence validation fails
- **WHEN** a submitted degree change is rejected because it skips, duplicates, or regresses the sequence
- **THEN** the profile page MUST show a message explaining the valid next degree

#### Scenario: Taller membership validation fails
- **WHEN** a submitted degree or position change is rejected because the Taller does not belong to the Hermano
- **THEN** the profile page MUST show a message explaining that the selected Taller must be one of the Hermano's Talleres
