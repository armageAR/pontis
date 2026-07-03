## ADDED Requirements

### Requirement: Panel exposes workshop administration entry point
The system SHALL show an administration box on the Panel for users who administer at least one Taller.

#### Scenario: Admin de Taller sees administration box
- **WHEN** an active Admin de Taller opens the Panel
- **THEN** the Panel MUST show a box titled `Administracion`
- **AND** the box MUST show the text `tareas de administracion en tu taller`
- **AND** clicking the box MUST navigate to the administration page for Admin de Taller tasks

#### Scenario: User without workshop admin role does not see administration box
- **WHEN** an active Hermano who is not Admin de Taller and not Superadmin opens the Panel
- **THEN** the Panel MUST NOT show the `Administracion` box

#### Scenario: Pending validations indicator appears
- **WHEN** an Admin de Taller has one or more pending masonic validations in their administered Talleres
- **THEN** the `Administracion` box MUST show a red indicator in its upper-right corner

#### Scenario: Pending validations indicator is hidden
- **WHEN** an Admin de Taller has no pending masonic validations in their administered Talleres
- **THEN** the `Administracion` box MUST NOT show the red pending indicator

### Requirement: Workshop administration page lists pending masonic validations
The system SHALL provide an administration page where an Admin de Taller can review pending masonic validations for Talleres they administer.

#### Scenario: Admin de Taller opens administration page
- **WHEN** an Admin de Taller opens the administration page
- **THEN** the page MUST show a table of pending degree and position validations for Talleres where the actor is admin
- **AND** each row MUST show the requesting Hermano, request date, requested Taller, and actions
- **AND** each row actions column MUST include `Aceptar`, `Rechazar`, and `Ver`

#### Scenario: Validation belongs to non-administered Taller
- **WHEN** a pending validation belongs to a Taller where the actor is not admin
- **THEN** that validation MUST NOT appear in the actor's administration table
- **AND** the actor MUST NOT be able to accept or reject it from this page

#### Scenario: No pending validations
- **WHEN** an Admin de Taller opens the administration page and has no pending masonic validations
- **THEN** the page MUST show an empty state explaining that there are no pending validations

### Requirement: Validation actions resolve pending records
The system SHALL allow authorized Admin de Taller users to accept or reject pending masonic validations from the administration table.

#### Scenario: Admin accepts validation from row action
- **WHEN** an Admin de Taller clicks `Aceptar` on a pending validation row for their Taller
- **THEN** the system MUST mark the validation as validated
- **AND** the row MUST be removed from the pending table after the action succeeds

#### Scenario: Admin rejects validation from row action
- **WHEN** an Admin de Taller clicks `Rechazar` on a pending validation row for their Taller
- **THEN** the system MUST mark the validation as rejected
- **AND** the row MUST be removed from the pending table after the action succeeds

### Requirement: Validation detail modal is reusable
The system SHALL use a shared validation detail modal for viewing masonic validation requests and performing available validation actions.

#### Scenario: Admin views validation details
- **WHEN** an Admin de Taller clicks `Ver` on a pending validation row
- **THEN** the system MUST open a modal showing all available details for that validation
- **AND** the modal MUST include the requesting Hermano, request date, requested Taller, record type, requested masonic data, notes, and validation status
- **AND** the modal MUST provide the same accept and reject options available from the table row

#### Scenario: Superadmin uses validation detail modal
- **WHEN** a Superadmin views a pending masonic validation detail
- **THEN** the system MUST use the same shared modal component used for Admin de Taller validation details
- **AND** the modal MUST preserve Superadmin's existing available validation actions
