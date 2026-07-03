# my-publications-page

## Purpose

Definir el comportamiento de la página "Mis Publicaciones" del Hermano: presentar la sección de servicios ofrecidos bajo el título "Lo que ofrezco", ofrecer filtros y control de elementos por página en formato compacto, mostrar la paginación al pie del listado (elementos por página a la izquierda, números de página clicables a la derecha), permitir que un usuario registrado publique sin autorización administrativa previa y eliminar la acción y el estado de "Pedir corrección" del flujo de publicaciones.

## Requirements

### Requirement: My publications offer section title
The Mis Publicaciones page SHALL label the user's offered services section as "Lo que ofrezco" instead of "Mis Servicios".

#### Scenario: User opens my publications page
- **WHEN** a registered user opens the Mis Publicaciones page
- **THEN** the services or offered-items section MUST show the title "Lo que ofrezco"
- **AND** the page MUST NOT show "Mis Servicios" as that section title

### Requirement: My publications filters are compact
The Mis Publicaciones page SHALL render the status filter and items-per-page control in a compact size.

#### Scenario: User views publication filters
- **WHEN** a registered user views the filters in Mis Publicaciones
- **THEN** the status filter MUST use compact control styling
- **AND** the items-per-page control MUST use compact control styling
- **AND** the controls MUST remain readable and usable

#### Scenario: User changes compact status filter
- **WHEN** a registered user selects a publication status in the compact status filter
- **THEN** the listing MUST reload or update using the selected status
- **AND** the compact visual size MUST NOT remove the filter behavior

### Requirement: My publications pagination footer
The Mis Publicaciones page SHALL show pagination controls at the bottom of the publication listing.

#### Scenario: User views a paginated publication list
- **WHEN** a registered user views Mis Publicaciones with paginated results
- **THEN** the bottom of the listing MUST show the items-per-page control on the left
- **AND** the bottom of the listing MUST show clickable page numbers on the right
- **AND** the bottom of the listing MUST show controls to move to the previous and next page when applicable

#### Scenario: User clicks a page number
- **WHEN** a registered user clicks a page number in the bottom pagination
- **THEN** the listing MUST load that page
- **AND** the selected page MUST be visibly identified

#### Scenario: User changes items per page
- **WHEN** a registered user changes the items-per-page value in the bottom-left control
- **THEN** the listing MUST reload using that page size
- **AND** pagination MUST reflect the new number of pages

### Requirement: Registered users can publish without authorization
The system SHALL allow a registered user to create a publication without requiring administrative authorization before it becomes published.

#### Scenario: Registered user creates an offered publication
- **WHEN** a registered user creates a publication for something they offer
- **THEN** the system MUST create the publication successfully
- **AND** the publication MUST NOT be saved as pending authorization
- **AND** the publication MUST be available in the user's Mis Publicaciones listing immediately

#### Scenario: Registered user creates a need publication
- **WHEN** a registered user creates a publication for a need or request
- **THEN** the system MUST create the publication successfully
- **AND** the publication MUST NOT be saved as pending authorization
- **AND** the publication MUST be available in the user's Mis Publicaciones listing immediately

### Requirement: Publication correction request state is removed
The publication workflow SHALL NOT expose or create a "pedir correccion" action or correction-requested state.

#### Scenario: User views publication actions
- **WHEN** a registered user views actions for a publication in Mis Publicaciones
- **THEN** the page MUST NOT show a "Pedir correccion" action
- **AND** the page MUST NOT show a "Requiere correccion" state as a selectable workflow state

#### Scenario: Publication is created or updated
- **WHEN** a registered user creates or updates a publication
- **THEN** the system MUST NOT transition the publication to a correction-requested state
- **AND** the system MUST NOT require correction notes for publication approval
