## ADDED Requirements

### Requirement: Degree and position declarations require validation
The system SHALL mark self-declared degrees and positions as declared until validated by an authorized administrator.

#### Scenario: Hermano creates own degree
- **WHEN** a Hermano creates a degree for themself
- **THEN** the degree MUST be saved as declared
- **AND** it MUST be visible as pending validation to the owner and authorized admins

#### Scenario: Admin creates degree
- **WHEN** an authorized admin creates a degree for a Hermano
- **THEN** the degree MUST be saved as validated

### Requirement: Validated records are firm
The system SHALL prevent direct user edits or deletion of validated degrees and positions.

#### Scenario: Hermano edits validated record
- **WHEN** a Hermano attempts to edit their own validated degree or position
- **THEN** the system MUST reject the direct edit
- **AND** the record MUST remain unchanged

### Requirement: Workshop-scoped validation
The system SHALL allow workshop admins to validate declarations tied to their authorized workshops.

#### Scenario: Workshop admin validates declaration
- **WHEN** an Admin de Taller validates a declared record tied to their Taller
- **THEN** the system MUST mark the record as validated
- **AND** store validator and validation timestamp
