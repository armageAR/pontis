## 1. Data Model

- [x] 1.1 Add `published_at` and `expires_at` fields to services and needs.
- [x] 1.2 Add or normalize lifecycle statuses for services and needs: `draft`, `active`, `suspended`, and `expired`.
- [x] 1.3 Update Service and Need model fillable fields and date casts for publication and expiration dates.
- [x] 1.4 Decide and implement migration handling for existing active records without expiration dates.

## 2. Backend Lifecycle

- [x] 2.1 Update service create/update validation to require title and description.
- [x] 2.2 Update need create/update validation to require title and description.
- [x] 2.3 Add backend validation that allowed validity days are only 10, 30, 60, or 90.
- [x] 2.4 Implement publish behavior for services: set active status, `published_at`, and `expires_at`.
- [x] 2.5 Implement publish behavior for needs: set active status, `published_at`, and `expires_at`.
- [x] 2.6 Implement save-draft behavior for services and needs.
- [x] 2.7 Implement suspend behavior for active services and needs.
- [x] 2.8 Implement republish behavior for expired services and needs by updating the existing record, not cloning it.
- [x] 2.9 Ensure published listing and search queries exclude draft, suspended, and expired publications.
- [x] 2.10 Ensure owner Mis Publicaciones queries include draft, active, suspended, and expired own publications.

## 3. Frontend Forms

- [x] 3.1 Remove the status dropdown from service and need creation/edit forms.
- [x] 3.2 Add validity options of 10, 30, 60, and 90 days to service and need forms.
- [x] 3.3 Require title and description in service and need forms.
- [x] 3.4 Ensure service and need category dropdowns load from the existing categories API.
- [x] 3.5 Add lower form actions for Publicar, Guardar, and Cancelar in creation and edit flows.
- [x] 3.6 Make Publicar send publish intent and validity days.
- [x] 3.7 Make Guardar send draft intent without publishing.
- [x] 3.8 Make Cancelar close the form without persisting unsaved changes.

## 4. Frontend Lifecycle Actions

- [x] 4.1 Show expired publications in Mis Publicaciones with state label "Vencida".
- [x] 4.2 Hide expired publications from user-facing published/search results.
- [x] 4.3 Add a Suspender action for active services and needs.
- [x] 4.4 Add a Republicar action for expired services and needs.
- [x] 4.5 Make Republicar open the creation-style form prefilled with the expired publication data.
- [x] 4.6 Make Publicar during republication update the same record with new publication and expiration dates.

## 5. Verification

- [x] 5.1 Add backend tests for service publish, draft, suspend, expiration, and republish.
- [x] 5.2 Add backend tests for need publish, draft, suspend, expiration, and republish.
- [x] 5.3 Add backend tests ensuring validity cannot exceed 90 days and only allowed options are accepted.
- [x] 5.4 Add frontend tests or manual QA coverage for missing status dropdown, required fields, categories, form buttons, suspend, expired label, and republish. (No FE test runner in repo; covered by `tsc -b`/vite build + manual QA checklist.)
- [x] 5.5 Run targeted PHPUnit tests for services, needs, and explore/search filtering. (162 passed, incl. 17 publication lifecycle/publishing.)
- [x] 5.6 Run frontend lint/typecheck/build or targeted checks for Mis Publicaciones. (build passes; remaining lint errors are pre-existing repo-wide.)
- [x] 5.7 Validate the OpenSpec change. (`openspec validate` passed.)
