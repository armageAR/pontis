## ADDED Requirements

### Requirement: Authorized self-created masonic records are auto-validated
The system SHALL auto-validate self-created degree and position records when the actor is already authorized to validate records for the declared Taller.

#### Scenario: Workshop admin creates own degree for administered Taller
- **WHEN** an Admin de Taller creates a degree for themself with `workshop_id` set to a Taller where they are admin
- **THEN** the degree MUST be saved as validated
- **AND** the system MUST store the actor as validator with a validation timestamp
- **AND** the degree MUST NOT appear as pending validation

#### Scenario: Workshop admin creates own position for administered Taller
- **WHEN** an Admin de Taller creates a position for themself with `workshop_id` set to a Taller where they are admin
- **THEN** the position MUST be saved as validated
- **AND** the system MUST store the actor as validator with a validation timestamp
- **AND** the position MUST NOT appear as pending validation

#### Scenario: Workshop admin creates own masonic record for non-administered Taller
- **WHEN** an Admin de Taller creates a degree or position for themself with `workshop_id` set to a Taller where they are not admin
- **THEN** the record MUST follow the normal self-declaration flow
- **AND** the record MUST require validation by a Superadmin or an Admin de Taller for the declared Taller

#### Scenario: Superadmin creates own masonic record
- **WHEN** a Superadmin creates a degree or position for themself
- **THEN** the record MUST be saved as validated
- **AND** the system MUST store the Superadmin as validator with a validation timestamp

#### Scenario: Regular Hermano creates own masonic record
- **WHEN** a Hermano without validation authority for the declared Taller creates a degree or position for themself
- **THEN** the record MUST be saved as declared
- **AND** it MUST be visible as pending validation to authorized administrators
