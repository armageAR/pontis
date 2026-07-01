## 1. Data And Rules Review

- [x] 1.1 Audit existing degree records for manual `end_date`, duplicated current degrees, skipped degrees, regressions, and users without a current degree.
- [x] 1.2 Decide how to handle existing inconsistent degree histories before enforcing stricter validation.
- [x] 1.3 Confirm whether normal profile actions remain self-service or require administrative authorization for grados and cargos.

## 2. Backend Validation

- [x] 2.1 Add shared validation that a submitted `workshop_id` belongs to the target Hermano for degree records.
- [x] 2.2 Add shared validation that a submitted `workshop_id` belongs to the target Hermano for position records.
- [x] 2.3 Enforce strict degree progression: first `aprendiz`, then `companero`, then `maestro`.
- [x] 2.4 Reject degree skips, regressions, duplicate current degree submissions, and date ordering that breaks the historical sequence.
- [x] 2.5 Remove or reject manual `end_date` updates for profile degree create/update requests.
- [x] 2.6 Preserve optional `end_date` behavior for position create/update requests.
- [x] 2.7 Ensure API responses can still present historical degree periods, deriving previous period end from the next degree start when needed.

## 3. Frontend Profile UI

- [x] 3.1 Replace the degree modal `WorkshopPicker` with a dropdown sourced from the user's existing Talleres.
- [x] 3.2 Replace the position modal `WorkshopPicker` with a dropdown sourced from the user's existing Talleres.
- [x] 3.3 Remove the "Fecha de fin" field from the degree modal and degree payloads.
- [x] 3.4 Keep the "Fecha de fin" field in the position modal.
- [x] 3.5 Limit degree options to the valid next degree where possible and show clear helper/error text for invalid progression.
- [x] 3.6 Show clear error text when the backend rejects a degree or position because the selected Taller does not belong to the Hermano.
- [x] 3.7 Update profile API types so degree payloads no longer require manual `end_date` from the form.

## 4. Verification

- [x] 4.1 Add backend tests for rejecting degree and position records with Talleres outside the Hermano's memberships.
- [x] 4.2 Add backend tests for valid degree progression from `aprendiz` to `companero` and from `companero` to `maestro`.
- [x] 4.3 Add backend tests for rejecting first degree other than `aprendiz`, skipped degree, duplicate degree, and regression.
- [x] 4.4 Add backend tests proving profile degree changes do not create manual end-date gaps and still preserve history.
- [x] 4.5 Add frontend verification for dropdown-only Taller selection in both modals.
- [x] 4.6 Add frontend verification that degree form has no end-date field and position form keeps its end-date field.
- [x] 4.7 Run relevant backend and frontend checks.
