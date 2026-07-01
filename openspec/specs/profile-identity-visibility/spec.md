# profile-identity-visibility

## Purpose

Reglas y experiencia de usuario para configurar la audiencia de la sección Identidad de un Hermano y su aparición en búsquedas sin revelar identidad. La audiencia (quién puede ver nombre, apellido, matrícula y email) y la aparición anónima son decisiones independientes que se combinan: quienes califican para la audiencia ven la identidad, y el resto de los Hermanos habilitados solo pueden encontrar al Hermano como resultado anónimo cuando esa opción está activada.

## Requirements

### Requirement: Identity audience and anonymous search appearance are independent
The system SHALL allow a Hermano to configure who can see the Identidad section independently from whether the Hermano appears in searches without revealing identity.

#### Scenario: Hermano enables anonymous appearance without changing identity audience
- **WHEN** a Hermano sets Identidad visibility to `my_workshops` and enables "Aparecer en busquedas sin revelar mi identidad"
- **THEN** the system MUST persist Identidad visibility as `my_workshops`
- **AND** the system MUST persist anonymous search appearance as enabled

#### Scenario: Anonymous appearance does not disable audience editing
- **WHEN** anonymous search appearance is enabled for Identidad
- **THEN** the profile page MUST keep the Identidad audience selector enabled
- **AND** changing the audience MUST NOT disable anonymous search appearance

### Requirement: Identity visibility is evaluated from the viewer perspective
The system SHALL decide whether to reveal identity in search results by evaluating the configured Identidad audience against the Hermano performing the search.

#### Scenario: Viewer qualifies for identity audience
- **WHEN** a searched Hermano has Identidad visibility set to `my_workshops`
- **AND** anonymous search appearance is enabled
- **AND** the searching Hermano shares a Taller with the searched Hermano
- **THEN** the result MUST show the searched Hermano's identity fields allowed by Identidad
- **AND** the result MUST NOT be marked as anonymous

#### Scenario: Viewer does not qualify but anonymous appearance is enabled
- **WHEN** a searched Hermano has Identidad visibility set to `my_workshops`
- **AND** anonymous search appearance is enabled
- **AND** the searching Hermano does not share a Taller with the searched Hermano
- **THEN** the searched Hermano MAY appear in eligible search results as an anonymous Hermano
- **AND** the result MUST NOT reveal name, last name, matricula, email, or other identity fields

#### Scenario: Viewer does not qualify and anonymous appearance is disabled
- **WHEN** a searched Hermano has Identidad visibility set to `my_workshops`
- **AND** anonymous search appearance is disabled
- **AND** the searching Hermano does not share a Taller with the searched Hermano
- **THEN** the searched Hermano MUST NOT appear due to Identidad
- **AND** the system MUST NOT reveal that the hidden identity matched

### Requirement: Hidden identity fields are not searchable
The system SHALL NOT use hidden identity fields to match searches for viewers who cannot see the Identidad section.

#### Scenario: Viewer searches by name without identity access
- **WHEN** a searching Hermano enters a name, last name, matricula, or email query
- **AND** a candidate Hermano's Identidad audience does not include the searching Hermano
- **THEN** the candidate MUST NOT match that query through hidden identity fields
- **AND** anonymous search appearance MUST NOT make hidden identity fields searchable

### Requirement: Profile page explains the combined behavior
The profile page SHALL present the Identidad audience and anonymous search appearance controls as separate decisions with explanatory text.

#### Scenario: User reviews identity privacy controls
- **WHEN** a Hermano opens the Identidad privacy controls in the profile page
- **THEN** the page MUST show an audience control for who can see Identidad
- **AND** the page MUST show a separate checkbox or toggle for appearing in searches without revealing identity
- **AND** the page MUST explain that viewers inside the selected audience see identity while other eligible viewers only see an anonymous result when the anonymous option is enabled

### Requirement: Profile data save action is placed at the page footer
The profile page SHALL place the button that saves user profile data at the bottom of the page aligned to the right.

#### Scenario: User finishes editing profile data
- **WHEN** a Hermano reaches the end of the editable profile data page
- **THEN** the page MUST show the save changes button at the footer aligned to the right
- **AND** the button MUST submit the user profile data changes

#### Scenario: User reviews upper profile sections
- **WHEN** a Hermano is editing or reviewing upper profile sections
- **THEN** the page MUST NOT rely on a primary profile-data save button embedded only near the top of the page

### Requirement: Existing anonymous identity settings are preserved safely
The system SHALL preserve the privacy intent of existing `identity = anonymous` settings without broadening identity exposure.

#### Scenario: Existing anonymous identity setting is migrated
- **WHEN** an existing Hermano has Identidad visibility stored as `anonymous`
- **THEN** the system MUST enable anonymous search appearance for that Hermano
- **AND** the system MUST assign a conservative Identidad audience no broader than `workshop` unless a previously selected audience is known
- **AND** the system MUST NOT reveal identity to all registered Hermanos solely because the previous value was `anonymous`
