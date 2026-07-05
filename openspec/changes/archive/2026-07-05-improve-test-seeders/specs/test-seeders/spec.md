## ADDED Requirements

### Requirement: Default superadmin credentials
The seed data SHALL include a default active Superadmin account for local and test access.

#### Scenario: Seed default superadmin
- **WHEN** database seeders are executed
- **THEN** the system MUST create or update `admin@pontis.com` as an active Superadmin
- **AND** the account MUST authenticate with password `password`

### Requirement: Deterministic workshop seed set
The seed data SHALL include exactly five deterministic real workshops for the demo/test dataset.

#### Scenario: Seed selected workshops
- **WHEN** database seeders are executed for the demo/test dataset
- **THEN** the system MUST create or update two workshops in Ciudad Autonoma de Buenos Aires
- **AND** one workshop in Provincia de Buenos Aires
- **AND** one workshop in Salta
- **AND** one workshop in Chubut

#### Scenario: Workshop data is self-contained
- **WHEN** the workshop seeder runs on a clean database
- **THEN** it MUST NOT require the crawler or preloaded workshop rows to have run first
- **AND** it MUST hardcode the selected real workshop data in the seeder

### Requirement: Deterministic users per workshop
The seed data SHALL create eight active users for each selected workshop.

#### Scenario: Seed workshop users
- **WHEN** demo users are seeded
- **THEN** each selected workshop MUST have exactly eight active users associated with active membership
- **AND** every seeded user MUST authenticate with password `password`
- **AND** every seeded user MUST have province and locality consistent with their primary workshop

### Requirement: Degree distribution per workshop
The seed data SHALL create a fixed degree distribution for each selected workshop.

#### Scenario: Seed degrees
- **WHEN** demo users are seeded for a workshop
- **THEN** six users MUST have current degree `maestro`
- **AND** one user MUST have current degree `companero`
- **AND** one user MUST have current degree `aprendiz`

#### Scenario: Seed degree history
- **WHEN** a seeded user has current degree `maestro`
- **THEN** the user MUST also have historical `aprendiz` and `companero` degree records before the current `maestro` record

#### Scenario: Seed companero history
- **WHEN** a seeded user has current degree `companero`
- **THEN** the user MUST also have a historical `aprendiz` degree record before the current `companero` record

### Requirement: Master positions per workshop
The seed data SHALL assign positions only to seeded Maestros.

#### Scenario: Seed master positions
- **WHEN** demo users are seeded for a workshop
- **THEN** the six Maestros MUST receive exactly one current position each
- **AND** the positions MUST be Venerable Maestro, Primer Vigilante, Segundo Vigilante, Maestro de Ceremonias, Experto, and Tesorero

#### Scenario: Non-master positions are absent
- **WHEN** demo users are seeded for a workshop
- **THEN** the Companero and Aprendiz users MUST NOT receive position records

### Requirement: Visibility coverage per workshop
The seed data SHALL provide privacy coverage cases in every selected workshop.

#### Scenario: Seed visibility presets
- **WHEN** demo users are seeded for a workshop
- **THEN** at least one user MUST be incognito
- **AND** at least one user MUST expose no profile blocks
- **AND** at least one user MUST expose all profile blocks
- **AND** the remaining users MUST use varied intermediate visibility presets

### Requirement: Seeder idempotency
The seeders SHALL be safe to run repeatedly without duplicating demo records.

#### Scenario: Re-run seeders
- **WHEN** the seeders are executed more than once
- **THEN** the system MUST update existing seeded workshops and users by stable unique keys
- **AND** it MUST NOT duplicate users, memberships, degrees, positions, or visibility settings for the deterministic dataset
