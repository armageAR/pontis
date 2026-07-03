## ADDED Requirements

### Requirement: Sidebar footer shows authenticated user identity summary
The application sidebar footer SHALL show a structured summary for the authenticated user.

#### Scenario: User identity row is rendered
- **WHEN** an authenticated user opens the application with the sidebar visible
- **THEN** the lower-left sidebar section MUST show the user's first and last name on the top row aligned to the left
- **AND** the notification bell MUST appear on the same row aligned to the right

#### Scenario: User has no last name
- **WHEN** the authenticated user has no last name
- **THEN** the sidebar identity row MUST show the user's first name without leaving awkward extra punctuation or spacing

### Requirement: Sidebar footer shows principal Taller and masonic registration
The sidebar footer SHALL show the authenticated user's principal Taller and masonic registration below the identity row.

#### Scenario: Principal Taller is available
- **WHEN** the authenticated user has a principal Taller
- **THEN** the sidebar footer MUST show the Taller number and Taller name below the identity row
- **AND** the principal Taller text MUST fit on one line
- **AND** long Taller names MUST be truncated with an ellipsis at the end

#### Scenario: Principal Taller is missing
- **WHEN** the authenticated user has no principal Taller in the auth payload
- **THEN** the sidebar footer MUST not render an incorrect or stale principal Taller value

#### Scenario: Masonic registration is available
- **WHEN** the authenticated user has a masonic registration number
- **THEN** the sidebar footer MUST show only the masonic registration number below the principal Taller
- **AND** it MUST NOT prepend labels such as `Mat.` or `Matrícula`

#### Scenario: Masonic registration is missing
- **WHEN** the authenticated user has no masonic registration number
- **THEN** the sidebar footer MUST not show an empty masonic registration row

### Requirement: Sidebar footer shows administrative badges
The sidebar footer SHALL show role badges for administrative roles held by the authenticated user.

#### Scenario: Superadmin badge is shown
- **WHEN** the authenticated user has role `superadmin`
- **THEN** the sidebar footer MUST show a Superadmin badge

#### Scenario: Admin de Taller badge is shown
- **WHEN** the authenticated user administers one or more Talleres
- **THEN** the sidebar footer MUST show an Admin badge

#### Scenario: User has no administrative role
- **WHEN** the authenticated user is neither Superadmin nor Admin de Taller
- **THEN** the sidebar footer MUST not show administrative badges

### Requirement: Admin badge discloses administered Talleres
The Admin badge SHALL disclose the Talleres where the authenticated user is Admin de Taller.

#### Scenario: Admin badge hover shows Taller list
- **WHEN** the authenticated user hovers over the Admin badge
- **THEN** the sidebar footer MUST show a list of the names of all Talleres where the user is admin

#### Scenario: Admin badge focus shows Taller list
- **WHEN** the authenticated user focuses the Admin badge with keyboard navigation
- **THEN** the sidebar footer MUST show a list of the names of all Talleres where the user is admin

#### Scenario: Multiple administered Talleres
- **WHEN** the authenticated user administers more than one Taller
- **THEN** the Admin badge disclosure MUST list each administered Taller name

### Requirement: Auth payload supports sidebar summary
The authenticated user payload SHALL include the data required to render the sidebar summary.

#### Scenario: Authenticated user payload is returned
- **WHEN** the client receives the authenticated user payload from login, register, or `/me`
- **THEN** the payload MUST include `last_name`, `masonic_id`, `principal_workshop`, and `admin_workshops`
- **AND** `principal_workshop` MUST include only the principal Taller's id, number, and name when present
- **AND** `admin_workshops` MUST include only id, number, and name for Talleres where the user is admin
