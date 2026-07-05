## ADDED Requirements

### Requirement: Dashboard profile quick access
The authenticated dashboard SHALL show a "Mi Perfil" quick-access card for every authenticated user.

#### Scenario: User opens dashboard
- **WHEN** an authenticated user opens the dashboard
- **THEN** the dashboard MUST show a card titled "Mi Perfil"
- **AND** the card MUST be visible for regular users, Admin de Taller users, and Superadmin users

#### Scenario: User opens own profile from dashboard
- **WHEN** the user activates the "Mi Perfil" dashboard card
- **THEN** the application MUST navigate to the user's own editable profile screen
- **AND** the destination MUST be `/profile`

#### Scenario: Existing profile links remain
- **WHEN** the "Mi Perfil" dashboard card is added
- **THEN** existing profile links elsewhere in the application MUST remain available

### Requirement: Dashboard profile completion
The authenticated dashboard SHALL show the user's profile completion percentage in the "Mi Perfil" card.

#### Scenario: Dashboard data includes profile completion
- **WHEN** the frontend requests dashboard data
- **THEN** the backend MUST include profile completion data for the authenticated user
- **AND** the completion percent MUST be an integer from 0 through 100

#### Scenario: User views profile completion card
- **WHEN** dashboard data has loaded
- **THEN** the "Mi Perfil" card MUST show a visual completion bar
- **AND** the card MUST show the completion percentage as text

#### Scenario: Profile completion is centrally calculated
- **WHEN** profile completion is displayed on the dashboard
- **THEN** the frontend MUST use the completion value provided by the dashboard data or an equivalent central backend source
- **AND** the frontend MUST NOT maintain an independent divergent completion formula
