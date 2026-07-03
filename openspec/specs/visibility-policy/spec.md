# visibility-policy

## Purpose

Servicio central de politicas de visibilidad para resolver que datos puede ver un Hermano segun viewer, subject, relacion de Talleres, aparicion anonima, publicaciones, contacto y previews de acciones sensibles.

## Requirements

### Requirement: Central visibility decisions
The system SHALL resolve profile and publication visibility through a central policy service.

#### Scenario: Endpoint checks profile visibility
- **WHEN** an endpoint needs to decide whether a viewer can see a profile block
- **THEN** it MUST use the central visibility policy
- **AND** it MUST NOT duplicate visibility logic inline

### Requirement: Anonymous appearance resolution
The central policy SHALL determine when a result can appear anonymously.

#### Scenario: Viewer cannot see identity but anonymous appearance is enabled
- **WHEN** the viewer does not qualify for identity visibility
- **AND** anonymous appearance is enabled
- **THEN** the policy MUST allow an anonymous result without revealing identity fields

### Requirement: Visibility preview
The central policy SHALL support previews of visible data before sensitive actions.

#### Scenario: User previews publication visibility
- **WHEN** a Hermano requests a preview before publishing
- **THEN** the policy MUST return the audience and fields that would be visible
