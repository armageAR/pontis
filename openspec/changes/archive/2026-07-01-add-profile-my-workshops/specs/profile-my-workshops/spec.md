## ADDED Requirements

### Requirement: Profile shows my workshops table
The profile page SHALL include a "Mis Talleres" section that lists the Talleres related to the current user.

#### Scenario: User has active and pending workshop relationships
- **WHEN** a user opens the profile page
- **THEN** the "Mis Talleres" section MUST show a table of active workshop memberships and pending join requests
- **AND** each row MUST show the Taller identity, membership status, whether it is principal, and available actions

#### Scenario: User has no visible workshop relationships
- **WHEN** a user opens the profile page with no active memberships and no pending join requests
- **THEN** the "Mis Talleres" section MUST show an empty state
- **AND** the section MUST still provide the action to add a Taller

### Requirement: Profile can request joining a workshop
The profile page SHALL allow the user to request joining another Taller using the existing workshop approval process.

#### Scenario: User searches for a workshop to join
- **WHEN** the user opens the add Taller modal
- **AND** starts typing a Taller name or number
- **THEN** the system MUST search active Talleres matching the entered text
- **AND** the user MUST be able to select one returned Taller

#### Scenario: User has not selected a workshop
- **WHEN** the add Taller modal is open
- **AND** no Taller is selected
- **THEN** the "Solicitar unirse" button MUST be hidden or disabled

#### Scenario: User requests to join selected workshop
- **WHEN** the user selects a Taller and clicks "Solicitar unirse"
- **THEN** the system MUST initiate the existing join request workflow for that Taller
- **AND** the profile table MUST show that Taller with status "Pendiente de aprobacion"

#### Scenario: Join request already exists
- **WHEN** the user requests to join a Taller that already has a pending relationship for the same user
- **THEN** the system MUST NOT create a duplicate relationship
- **AND** the profile table MUST continue to show the pending status

### Requirement: Profile can leave a non-principal workshop
The profile page SHALL allow the user to leave a non-principal Taller after confirmation.

#### Scenario: User confirms leaving a non-principal workshop
- **WHEN** the user clicks the leave icon for a non-principal Taller
- **AND** confirms the action
- **THEN** the system MUST remove the user-Taller relationship
- **AND** the Taller MUST disappear from the "Mis Talleres" table unless another visible relationship remains

#### Scenario: User cancels leaving a workshop
- **WHEN** the user clicks the leave icon for a non-principal Taller
- **AND** cancels the confirmation
- **THEN** the system MUST NOT remove the user-Taller relationship
- **AND** the table MUST remain unchanged

### Requirement: Leaving a workshop removes associated positions
When a user leaves a Taller, the system SHALL remove the user's cargos associated with that Taller.

#### Scenario: User leaves workshop with positions
- **WHEN** the user leaves a non-principal Taller
- **AND** the user has cargo records associated with that Taller
- **THEN** the system MUST remove those cargo records
- **AND** those cargos MUST no longer appear in the user's profile

#### Scenario: User leaves workshop without positions
- **WHEN** the user leaves a non-principal Taller
- **AND** the user has no cargo records associated with that Taller
- **THEN** the system MUST remove the user-Taller relationship without error

### Requirement: Principal workshop cannot be removed
The system SHALL prevent a user from leaving the Taller marked as principal.

#### Scenario: User attempts to leave principal workshop
- **WHEN** the user attempts to leave the Taller marked as principal
- **THEN** the system MUST reject the action
- **AND** the user-Taller relationship MUST remain unchanged
- **AND** associated cargos MUST remain unchanged

#### Scenario: Profile renders principal workshop actions
- **WHEN** the "Mis Talleres" table shows the principal Taller
- **THEN** the leave action MUST be unavailable or disabled
- **AND** the UI MUST communicate that the principal Taller cannot be removed

### Requirement: Profile can change principal workshop
The profile page SHALL allow the user to mark another active Taller as principal with confirmation.

#### Scenario: User selects new principal workshop
- **WHEN** the user clicks the make-principal icon for an active non-principal Taller
- **AND** the user already has another principal Taller
- **THEN** the system MUST show a confirmation saying the current Taller will stop being principal and the selected Taller will become principal

#### Scenario: User confirms principal workshop change
- **WHEN** the user confirms the principal workshop change
- **THEN** the system MUST make the selected Taller the user's only principal Taller
- **AND** the previous principal Taller MUST no longer be marked principal
- **AND** the table MUST reflect the new principal Taller

#### Scenario: User cancels principal workshop change
- **WHEN** the user cancels the principal workshop confirmation
- **THEN** the system MUST NOT change any principal Taller flags

#### Scenario: Pending workshop cannot become principal
- **WHEN** the user attempts to mark a pending Taller as principal
- **THEN** the system MUST reject the action
- **AND** the pending Taller MUST remain non-principal

### Requirement: Principal workshop uniqueness is enforced
The system SHALL enforce that each user has at most one active principal Taller.

#### Scenario: Principal workshop is changed
- **WHEN** a principal Taller change is processed
- **THEN** the system MUST update the affected user memberships atomically
- **AND** after the operation exactly one active membership for that user MUST be marked as principal

#### Scenario: User has no active membership for selected workshop
- **WHEN** a principal Taller change is requested for a Taller where the user has no active membership
- **THEN** the system MUST reject the request
- **AND** existing principal Taller data MUST remain unchanged
