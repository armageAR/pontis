## ADDED Requirements

### Requirement: Publication validity options
The Mis Publicaciones creation and edit forms SHALL let users choose publication validity using only 10, 30, 60, or 90 days.

#### Scenario: User opens the create form
- **WHEN** a registered user opens the creation form for "Lo que ofrezco" or "Lo que necesito"
- **THEN** the form MUST show validity options for 10 days, 30 days, 60 days, and 90 days
- **AND** the form MUST NOT allow choosing a validity longer than 90 days

#### Scenario: User publishes with selected validity
- **WHEN** a registered user publishes a publication with a selected validity
- **THEN** the system MUST set the publication date to the publish time
- **AND** the system MUST set the expiration date according to the selected validity
- **AND** the expiration date MUST NOT be more than 90 days after the publication date

### Requirement: Publication form required fields and categories
The publication form SHALL require title and description and SHALL populate category options from the existing category catalog.

#### Scenario: User opens publication form
- **WHEN** a registered user opens the creation or edit form for "Lo que ofrezco" or "Lo que necesito"
- **THEN** the category dropdown MUST be populated with existing system categories
- **AND** the form MUST require a title
- **AND** the form MUST require a description

#### Scenario: User submits without required fields
- **WHEN** a registered user submits the form without title or description
- **THEN** the system MUST reject the submission
- **AND** the publication MUST NOT be created or updated

### Requirement: Publication form actions define status
The publication form SHALL use action buttons instead of a status dropdown to determine publication state.

#### Scenario: User opens creation form
- **WHEN** a registered user opens the creation form for "Lo que ofrezco" or "Lo que necesito"
- **THEN** the form MUST NOT show a status dropdown
- **AND** the lower part of the form MUST show "Publicar", "Guardar", and "Cancelar" actions

#### Scenario: User clicks Publicar on creation
- **WHEN** a registered user completes the creation form and clicks "Publicar"
- **THEN** the system MUST create the publication in active state
- **AND** the publication MUST receive publication and expiration dates

#### Scenario: User clicks Guardar on creation
- **WHEN** a registered user completes the creation form and clicks "Guardar"
- **THEN** the system MUST create the publication in draft state
- **AND** the draft MUST remain visible in Mis Publicaciones
- **AND** the draft MUST NOT appear in published listings or search results

#### Scenario: User clicks Cancelar on creation
- **WHEN** a registered user opens the creation form and clicks "Cancelar"
- **THEN** the form MUST close
- **AND** no publication MUST be created

#### Scenario: User clicks Publicar on edit
- **WHEN** a registered user edits a publication and clicks "Publicar"
- **THEN** the system MUST save the edited data
- **AND** the system MUST set the publication state to active
- **AND** the system MUST update the publication and expiration dates according to the selected validity

#### Scenario: User clicks Guardar on edit
- **WHEN** a registered user edits a publication and clicks "Guardar"
- **THEN** the system MUST save the edited data
- **AND** the system MUST set or keep the publication in draft state

#### Scenario: User clicks Cancelar on edit
- **WHEN** a registered user edits a publication and clicks "Cancelar"
- **THEN** the form MUST close
- **AND** unsaved changes MUST NOT be persisted

### Requirement: Active publications expire automatically
The system SHALL stop publishing active publications after their expiration date.

#### Scenario: Active publication has not expired
- **WHEN** a publication is active
- **AND** its expiration date is in the future
- **THEN** the publication MUST be eligible to appear in published listings and search results according to its visibility settings

#### Scenario: Active publication reaches expiration date
- **WHEN** the current date and time is on or after an active publication's expiration date
- **THEN** the publication MUST NOT appear in published listings or search results
- **AND** the publication MUST remain visible in the owner's Mis Publicaciones screen
- **AND** the owner-facing state MUST be shown as "Vencida"

### Requirement: User can suspend own publication
The system SHALL allow a registered user to suspend their own active publication.

#### Scenario: User suspends active publication
- **WHEN** a registered user clicks "Suspender" on their own active publication
- **THEN** the system MUST set the publication state to suspended
- **AND** the publication MUST stop appearing in published listings and search results
- **AND** the publication MUST remain visible in the owner's Mis Publicaciones screen

#### Scenario: Suspended publication is edited
- **WHEN** a registered user edits their own suspended publication
- **THEN** the user MUST be able to modify the publication data
- **AND** clicking "Publicar" MUST activate the publication with new publication and expiration dates

### Requirement: User can republish expired publication
The system SHALL allow a registered user to republish their own expired publication without creating a duplicate publication.

#### Scenario: User starts republishing expired publication
- **WHEN** a registered user clicks "Republicar" on an expired publication
- **THEN** the system MUST open the publication form with the expired publication data prefilled
- **AND** the user MUST be able to modify the prefilled data before publishing

#### Scenario: User confirms republish
- **WHEN** a registered user clicks "Publicar" while republishing an expired publication
- **THEN** the system MUST update the existing publication
- **AND** the system MUST set the state to active
- **AND** the system MUST update the publication date to the republish time
- **AND** the system MUST update the expiration date according to the selected validity
- **AND** the system MUST NOT create a duplicate publication

### Requirement: Lifecycle applies to offer and need publications
The publication lifecycle SHALL apply equally to "Lo que ofrezco" and "Lo que necesito" publications.

#### Scenario: User manages an offer publication
- **WHEN** a registered user creates, saves, edits, suspends, expires, or republishes a "Lo que ofrezco" publication
- **THEN** the system MUST apply the publication lifecycle rules for validity, status, visibility, and form actions

#### Scenario: User manages a need publication
- **WHEN** a registered user creates, saves, edits, suspends, expires, or republishes a "Lo que necesito" publication
- **THEN** the system MUST apply the publication lifecycle rules for validity, status, visibility, and form actions
