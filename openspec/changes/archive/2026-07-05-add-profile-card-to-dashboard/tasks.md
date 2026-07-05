## 1. Backend Dashboard Data

- [x] 1.1 Define the explicit field list used to calculate profile completion.
- [x] 1.2 Add backend profile completion calculation for the authenticated user.
- [x] 1.3 Add `profile_completion` to `GET /api/dashboard` without removing existing dashboard fields.
- [x] 1.4 Add backend tests verifying `profile_completion.percent` is an integer from 0 through 100.

## 2. Frontend Dashboard Card

- [x] 2.1 Update the `DashboardData` TypeScript type to include profile completion.
- [x] 2.2 Add a "Mi Perfil" card to the dashboard card grid for every authenticated role.
- [x] 2.3 Make the card link to `/profile`.
- [x] 2.4 Render a completion percentage label and progress bar inside the card.
- [x] 2.5 Ensure the card is additive and does not remove existing profile links or dashboard cards.

## 3. Tests and Verification

- [x] 3.1 Add or update dashboard frontend tests to assert the "Mi Perfil" card renders after dashboard data loads.
- [x] 3.2 Add or update dashboard frontend tests to assert the card links to `/profile`.
- [x] 3.3 Add or update dashboard frontend tests to assert completion percentage and bar are shown.
- [x] 3.4 Run backend dashboard tests.
- [x] 3.5 Run frontend dashboard tests/typecheck.
