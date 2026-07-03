## ADDED Requirements

### Requirement: Publication preview before publish
The system SHALL show a visibility preview before publishing or republishing an offer or need publication.

#### Scenario: User clicks Publicar
- **WHEN** a Hermano clicks "Publicar" on a publication form
- **THEN** the system MUST show a preview before persisting active publication changes
- **AND** the preview MUST show publication data and visible author data

### Requirement: Anonymous preview masking
The preview SHALL show anonymous publication appearance when anonymous visibility is selected.

#### Scenario: User previews anonymous publication
- **WHEN** the selected visibility reserves identity
- **THEN** the preview MUST show the author as identity reserved
- **AND** MUST NOT show hidden identity fields as visible to readers

### Requirement: Draft save skips preview
Saving a draft SHALL NOT require the publication preview step.

#### Scenario: User clicks Guardar
- **WHEN** a Hermano saves a publication as draft
- **THEN** the system MUST save the draft without showing publish preview
