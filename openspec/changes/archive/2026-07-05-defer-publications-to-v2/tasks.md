## 1. Backend Access Blocking

- [x] 1.1 Identify all publication-related API routes: `services`, `needs`, `explore/services`, `explore/needs`, `profile/publication-preview`, and publication-only category routes if they have no other V1 use.
- [x] 1.2 Add a backend guard or route-level blocking strategy that rejects publication endpoints for every authenticated role in V1.
- [x] 1.3 Ensure blocked publication endpoints do not create, update, delete, publish, suspend, republish, preview, or list publication data.
- [x] 1.4 Preserve existing models, migrations, seeders, controllers, and data without destructive deletion.

## 2. Frontend Visibility Blocking

- [x] 2.1 Remove or hide navigation entries, cards, tabs, and links to `Mis publicaciones`, `Lo que ofrezco`, `Lo que necesito`, and publication exploration.
- [x] 2.2 Block direct frontend routes to publication screens so they do not render publication data or actions.
- [x] 2.3 Remove or hide publish, save draft, suspend, republish, delete publication, and publication preview actions from reachable V1 UI.
- [x] 2.4 Verify no V1 screen exposes contact actions that originate from offer or need publications.

## 3. Tests

- [x] 3.1 Add backend feature tests proving a Hermano, Admin de Taller, and Superadmin cannot access publication endpoints in V1.
- [x] 3.2 Add backend tests proving blocked endpoints do not mutate publication data.
- [x] 3.3 Update or quarantine existing publication lifecycle/publishing tests so V1 expectations reflect deferred functionality.
- [x] 3.4 Add frontend tests or route assertions proving direct publication routes are inaccessible and navigation no longer exposes publications.

## 4. Documentation

- [x] 4.1 Update `docs/publicaciones.md` to state that publications are deferred to V2 and not visible or accessible in V1.
- [x] 4.2 Document `offers / needs` as the target V2 naming, while noting current dormant implementation still uses `services / needs`.
- [x] 4.3 Review project documentation for V1 scope statements that still promise publications and update them to reference V2 deferral.

## 5. Verification

- [x] 5.1 Run backend tests covering publication blocking and core auth/access behavior.
- [x] 5.2 Run frontend typecheck/tests after removing visible publication routes.
- [x] 5.3 Manually verify with active user and admin accounts that publications cannot be reached through navigation or direct URLs.
- [x] 5.4 Confirm no database tables or existing publication records were dropped by the change.
