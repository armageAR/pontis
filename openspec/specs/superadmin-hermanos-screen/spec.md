# superadmin-hermanos-screen Specification

## Purpose

Pantalla administrativa sensible para que el Superadmin consulte y gestione el listado de Hermanos con filtros, ordenamiento y controles de acceso reforzados.

## Requirements

### Requirement: Superadmin-only Hermanos administration screen
The system SHALL allow only Superadmin users to access the administrative Hermanos screen and its administrative user payload.

#### Scenario: Superadmin opens Hermanos administration
- **WHEN** an authenticated Superadmin opens the Hermanos administration screen
- **THEN** the system MUST render the screen and load the administrative Hermanos list

#### Scenario: Workshop admin is denied
- **WHEN** an authenticated Admin de Taller attempts to open the Hermanos administration screen or request its list payload
- **THEN** the system MUST deny access
- **AND** it MUST NOT return administrative user rows

#### Scenario: Regular user is denied
- **WHEN** an authenticated regular user attempts to open the Hermanos administration screen or request its list payload
- **THEN** the system MUST deny access
- **AND** it MUST NOT return administrative user rows

### Requirement: Hermanos filters include searchable Taller and Provincia
The system SHALL allow the Superadmin to filter the Hermanos list by a searchable Taller selector and by Provincia.

#### Scenario: Filter by Taller using searchable selector
- **WHEN** the Superadmin types a Taller name or number in the Taller filter and selects a Taller
- **THEN** the system MUST filter the Hermanos list to users related to that Taller
- **AND** the selected Taller MUST remain visible in the filter until cleared

#### Scenario: Clear Taller filter
- **WHEN** the Superadmin clears the selected Taller filter
- **THEN** the system MUST remove `workshop_id` from the active filters
- **AND** it MUST reload the Hermanos list from page 1

#### Scenario: Filter by Provincia
- **WHEN** the Superadmin selects a Provincia
- **THEN** the system MUST filter the Hermanos list to users whose profile province matches the selected Provincia
- **AND** it MUST reload the Hermanos list from page 1

#### Scenario: Combine filters
- **WHEN** the Superadmin selects Taller, Provincia, status, role, or search filters together
- **THEN** the system MUST apply all active filters to the same paginated query

### Requirement: Hermanos table columns and sorting
The system SHALL render the Hermanos table with the requested column order and sortable headers for every visible column.

#### Scenario: Table uses requested column order
- **WHEN** the Superadmin views the Hermanos table
- **THEN** the columns MUST appear in this order: Apellido/s, Nombre/s, Email, Talleres, Estado, Acciones
- **AND** the table MUST NOT show the old Registro column in this view

#### Scenario: Sort by data columns
- **WHEN** the Superadmin clicks Apellido/s, Nombre/s, Email, Talleres, or Estado
- **THEN** the system MUST sort the paginated Hermanos list by the clicked column
- **AND** clicking the same header again MUST toggle between ascending and descending order
- **AND** active filters MUST remain applied

#### Scenario: Sort by Acciones
- **WHEN** the Superadmin clicks the Acciones header
- **THEN** the system MUST apply a deterministic sort for the actions column
- **AND** clicking the same header again MUST toggle between ascending and descending order
- **AND** active filters MUST remain applied

### Requirement: Hermanos table row separators are continuous
The system SHALL render each row separator as a continuous horizontal line aligned across all table cells.

#### Scenario: Row line spans all cells
- **WHEN** the Hermanos table renders one or more rows
- **THEN** each divider between rows MUST span from the first column through Acciones without vertical offset
- **AND** the Nombre/s or Apellido/s cell layout MUST NOT break the alignment of the divider
