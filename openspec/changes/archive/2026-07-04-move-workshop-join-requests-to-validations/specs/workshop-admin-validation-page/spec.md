## MODIFIED Requirements

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
- **WHEN** an Admin de Taller has one or more pending validation tasks in their administered Talleres, including masonic validations, sensitive identity requests, or workshop join requests
- **THEN** the `Administracion` box MUST show a red indicator in its upper-right corner

#### Scenario: Pending validations indicator is hidden
- **WHEN** an Admin de Taller has no pending validation tasks in their administered Talleres
- **THEN** the `Administracion` box MUST NOT show the red pending indicator

### Requirement: Workshop administration page lists pending masonic validations
The system SHALL provide an administration page where an Admin de Taller can review pending validations and join requests for Talleres they administer.

#### Scenario: Admin de Taller opens administration page
- **WHEN** an Admin de Taller opens the administration page
- **THEN** the page MUST show pending degree and position validations for Talleres where the actor is admin
- **AND** it MUST show pending or correction-requested workshop join requests for Talleres where the actor is admin
- **AND** each row MUST show the requesting Hermano, request date, requested Taller, and actions
- **AND** each row actions column MUST include `Aceptar`, `Rechazar`, and `Ver`

#### Scenario: Validation belongs to non-administered Taller
- **WHEN** a pending validation or join request belongs to a Taller where the actor is not admin
- **THEN** that item MUST NOT appear in the actor's administration table
- **AND** the actor MUST NOT be able to accept or reject it from this page

#### Scenario: No pending validations
- **WHEN** an Admin de Taller opens the administration page and has no pending validations or join requests
- **THEN** the page MUST show an empty state explaining that there are no pending validations

### Requirement: Validation actions resolve pending records
The system SHALL allow authorized Admin de Taller users to accept or reject pending validation records and workshop join requests from the administration table.

#### Scenario: Admin accepts validation from row action
- **WHEN** an Admin de Taller clicks `Aceptar` on a pending validation row for their Taller
- **THEN** the system MUST mark the validation as validated
- **AND** the row MUST be removed from the pending table after the action succeeds

#### Scenario: Admin rejects validation from row action
- **WHEN** an Admin de Taller clicks `Rechazar` on a pending validation row for their Taller
- **THEN** the system MUST mark the validation as rejected
- **AND** the row MUST be removed from the pending table after the action succeeds

#### Scenario: Admin accepts join request from row action
- **WHEN** an Admin de Taller clicks `Aceptar` on a pending workshop join request for their Taller
- **THEN** the system MUST approve the membership request
- **AND** the row MUST be removed from the pending table after the action succeeds

#### Scenario: Admin rejects join request from row action
- **WHEN** an Admin de Taller clicks `Rechazar` on a pending workshop join request for their Taller
- **THEN** the system MUST reject the membership request
- **AND** the row MUST be removed from the pending table after the action succeeds

#### Scenario: Admin requests correction for join request
- **WHEN** an Admin de Taller requests correction on a workshop join request for their Taller
- **THEN** the system MUST mark the membership request as requiring correction
- **AND** the row MUST remain visible as correction-requested until resolved or no longer pending reviewer action

## ADDED Requirements

### Requirement: Panel does not duplicate join request review
The Panel SHALL not render a standalone workshop join request review section when join requests are available in Administración → Validaciones.

#### Scenario: Admin de Taller has pending join requests
- **WHEN** an Admin de Taller opens the Panel and has pending workshop join requests
- **THEN** the Panel MUST show the `Administracion` box with the pending indicator
- **AND** the Panel MUST NOT show a separate `Solicitudes de ingreso` review section
- **AND** clicking the `Administracion` box MUST lead to the Validaciones surface where those join requests can be reviewed

#### Scenario: Superadmin has pending join requests
- **WHEN** a Superadmin opens the Panel and there are pending workshop join requests
- **THEN** the Panel MUST show the `Administracion` box with the pending indicator
- **AND** the Panel MUST NOT show a separate `Solicitudes de ingreso` review section
