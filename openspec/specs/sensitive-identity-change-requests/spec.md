# sensitive-identity-change-requests

## Purpose

Reglas para crear, listar y resolver solicitudes de cambio sensible sobre identidad del Hermano: nombre, apellido, DNI y matrícula masónica. El Superadmin conserva alcance global y el Admin de Taller puede revisar solicitudes solo para Hermanos que pertenecen a sus Talleres administrados.

## Requirements

### Requirement: Sensitive identity change requests are scoped by reviewer authority
The system SHALL allow only authorized reviewers to list sensitive identity change requests for name, last name, DNI, and masonic registration.

#### Scenario: Superadmin lists all requests
- **WHEN** a Superadmin lists sensitive identity change requests
- **THEN** the system MUST return requests from all Hermanos

#### Scenario: Admin de Taller lists requests for administered Talleres
- **WHEN** an Admin de Taller lists sensitive identity change requests
- **THEN** the system MUST return only requests from Hermanos who belong to Talleres where the actor is admin

#### Scenario: Admin de Taller cannot list unrelated requests
- **WHEN** an Admin de Taller lists sensitive identity change requests
- **AND** a request belongs to a Hermano who does not belong to any Taller administered by the actor
- **THEN** that request MUST NOT be returned

#### Scenario: Regular Hermano lists requests
- **WHEN** a regular Hermano lists sensitive identity change requests
- **THEN** the system MUST return only that Hermano's own requests

### Requirement: Admin de Taller can resolve scoped sensitive identity requests
The system SHALL allow Admin de Taller users to approve, reject, or request more information for sensitive identity change requests from Hermanos in their administered Talleres.

#### Scenario: Admin de Taller approves scoped request
- **WHEN** an Admin de Taller approves a pending or requires-info request from a Hermano in an administered Taller
- **THEN** the system MUST apply the requested field change to the Hermano
- **AND** the request MUST be marked approved
- **AND** the actor MUST be stored as reviewer
- **AND** the resolution MUST be audited

#### Scenario: Admin de Taller rejects scoped request
- **WHEN** an Admin de Taller rejects a pending or requires-info request from a Hermano in an administered Taller
- **THEN** the request MUST be marked rejected
- **AND** the actor MUST be stored as reviewer
- **AND** the resolution MUST be audited

#### Scenario: Admin de Taller requests more information for scoped request
- **WHEN** an Admin de Taller requests more information for a pending or requires-info request from a Hermano in an administered Taller
- **THEN** the request MUST be marked requires-info
- **AND** the actor MUST be stored as reviewer
- **AND** the reviewer note MUST be stored and sent to the Hermano
- **AND** the action MUST be audited

#### Scenario: Admin de Taller cannot resolve unrelated request
- **WHEN** an Admin de Taller attempts to approve, reject, or request more information for a request from a Hermano outside their administered Talleres
- **THEN** the system MUST reject the action with forbidden access
- **AND** the request and target Hermano data MUST remain unchanged

### Requirement: Sensitive identity request notifications reach all authorized reviewers
The system SHALL notify authorized reviewers when a sensitive identity change request is created.

#### Scenario: Hermano creates request with administered Taller
- **WHEN** a Hermano creates a sensitive identity change request
- **AND** the Hermano belongs to one or more Talleres with Admin de Taller users
- **THEN** the system MUST notify Superadmins
- **AND** the system MUST notify Admin de Taller users for those Talleres
- **AND** duplicate notifications to the same reviewer MUST be avoided

#### Scenario: Hermano creates request without administered Taller
- **WHEN** a Hermano creates a sensitive identity change request
- **AND** no Admin de Taller is authorized through the Hermano's Talleres
- **THEN** the system MUST notify Superadmins

### Requirement: Change request reviewer UI supports Admin de Taller
The change request page SHALL expose reviewer controls to Admin de Taller users for scoped sensitive identity change requests.

#### Scenario: Admin de Taller opens change requests page
- **WHEN** an Admin de Taller opens the change requests page
- **THEN** the page MUST show scoped requests returned by the backend
- **AND** pending or requires-info requests MUST show review actions

#### Scenario: Regular Hermano opens change requests page
- **WHEN** a regular Hermano opens the change requests page
- **THEN** the page MUST NOT show reviewer actions
- **AND** the page MUST continue to allow cancellation of that Hermano's own pending or requires-info requests
