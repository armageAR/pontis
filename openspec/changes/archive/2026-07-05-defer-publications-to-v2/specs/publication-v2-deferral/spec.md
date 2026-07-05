## ADDED Requirements

### Requirement: Publications are deferred from V1
The system SHALL exclude publication functionality from V1 while preserving existing implementation artifacts for future V2 work.

#### Scenario: User navigation does not expose publications
- **WHEN** any authenticated user views the application navigation
- **THEN** the system MUST NOT show links, tabs, menu entries, cards, or actions for publications, offers, needs, "Mis publicaciones", "Lo que ofrezco", or "Lo que necesito"

#### Scenario: Public visitor cannot discover publications
- **WHEN** an unauthenticated visitor uses the public site
- **THEN** the system MUST NOT expose publication listings, publication search, publication creation, or publication detail pages

### Requirement: Direct publication access is blocked
The system SHALL block direct access to publication routes and endpoints for all roles in V1.

#### Scenario: Authenticated user requests publication API
- **WHEN** a Hermano, Admin de Taller, or Superadmin calls an API endpoint for services, needs, publication exploration, or publication preview
- **THEN** the system MUST reject the request as unavailable in V1
- **AND** the request MUST NOT create, update, delete, publish, suspend, republish, preview, or list publications

#### Scenario: Authenticated user opens publication route directly
- **WHEN** a Hermano, Admin de Taller, or Superadmin navigates directly to a frontend publication route
- **THEN** the system MUST prevent access to the publication screen
- **AND** the system MUST NOT render publication data or publication actions

### Requirement: Publication data and code are preserved
The system SHALL keep existing publication code and database structures unless a later V2 migration explicitly changes them.

#### Scenario: V1 deferral is implemented
- **WHEN** the publication module is disabled for V1
- **THEN** existing migrations, tables, models, controllers, frontend components, and tests MAY remain in the repository
- **AND** implementation MUST NOT destructively drop publication data as part of this change

### Requirement: V2 naming is documented but not migrated
The system SHALL treat `offers / needs` as the target domain naming for future publication work without renaming current technical resources in this change.

#### Scenario: Developer reviews publication documentation
- **WHEN** a developer reads the V1 publication documentation
- **THEN** the documentation MUST state that `offers` maps to "Lo que ofrezco" and `needs` maps to "Lo que necesito"
- **AND** the documentation MUST state that current `services` code remains dormant until a future V2 migration
