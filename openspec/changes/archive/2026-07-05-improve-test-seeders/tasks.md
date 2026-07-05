## 1. Seeder Data Selection

- [x] 1.1 Confirm the five hardcoded workshops match the selected real crawler/base records from the design document.
- [x] 1.2 Normalize selected workshop provinces and localities, especially Buenos Aires and Chubut records whose crawler `province` may be empty.
- [x] 1.3 Ensure `PositionSeeder` contains `Maestro de Ceremonias` and all other required positions.

## 2. Seeder Implementation

- [x] 2.1 Update `SuperAdminSeeder` to create or update `admin@pontis.com` with role `superadmin`, status `active`, verified email, and password `password`.
- [x] 2.2 Update `WorkshopSeeder` to create or update exactly the five selected workshops using stable `number` keys.
- [x] 2.3 Rewrite or refactor `DemoUsersSeeder` to create exactly 8 active users per selected workshop.
- [x] 2.4 Ensure all seeded user passwords are `password` and all seeded user emails are deterministic.
- [x] 2.5 Assign each seeded user active membership in exactly their primary workshop unless explicitly required otherwise.
- [x] 2.6 Seed six Maestros, one Companero, and one Aprendiz per workshop.
- [x] 2.7 Seed historical degree progression for Maestros and Companeros.
- [x] 2.8 Seed one current position for each Maestro using the six required position names.
- [x] 2.9 Ensure Companero and Aprendiz users receive no positions.
- [x] 2.10 Seed visibility presets so every workshop has at least incognito, private/no-data, and fully visible users.
- [x] 2.11 Remove creation of demo `services` and `needs` publication rows from V1 demo seeders.

## 3. Idempotency and Cleanup

- [x] 3.1 Use stable unique keys for users, workshops, memberships, degrees, positions, and visibility settings.
- [x] 3.2 Clear and recreate deterministic demo relations for seeded users before reseeding.
- [x] 3.3 Avoid destructive deletion of non-demo users or non-demo workshops.

## 4. Tests

- [x] 4.1 Add seeder tests verifying superadmin credentials.
- [x] 4.2 Add seeder tests verifying exactly five selected workshops and 40 active demo users.
- [x] 4.3 Add seeder tests verifying per-workshop degree distribution.
- [x] 4.4 Add seeder tests verifying Maestro degree history and positions.
- [x] 4.5 Add seeder tests verifying visibility coverage per workshop.
- [x] 4.6 Add seeder tests verifying `services` and `needs` are not created by V1 demo seeders.

## 5. Verification

- [x] 5.1 Run the relevant backend test suite.
- [x] 5.2 Run seeders twice on a clean test database and verify counts remain stable.
- [x] 5.3 Manually verify login with `admin@pontis.com` / `password`.
