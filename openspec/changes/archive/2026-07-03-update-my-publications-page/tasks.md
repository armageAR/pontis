## 1. Backend Publication Workflow

- [x] 1.1 Update service creation so a registered user-created service is not saved as pending authorization and is immediately available in Mis Publicaciones.
- [x] 1.2 Update need creation so a registered user-created need is not saved as pending authorization and is immediately available in Mis Publicaciones.
- [x] 1.3 Remove or disable publication correction-request transitions for services and needs from active API behavior.
- [x] 1.4 Update route/controller validation so publication create and update flows do not require approval notes or correction notes.
- [x] 1.5 Update seeders, factories, enums, labels, or constants that expose `requires_correction` as an active publication state.

## 2. Frontend My Publications UI

- [x] 2.1 Change the offered-services section title from "Mis Servicios" to "Lo que ofrezco".
- [x] 2.2 Remove "Pedir correccion" actions and "Requiere correccion" selectable workflow states from services and needs publication UI.
- [x] 2.3 Apply compact styling to status and items-per-page controls without changing their filter behavior.
- [x] 2.4 Move pagination controls to the bottom of the listing.
- [x] 2.5 Render items-per-page control on the left side of the pagination footer.
- [x] 2.6 Render clickable page numbers and previous/next controls on the right side of the pagination footer.

## 3. Verification

- [x] 3.1 Add or update backend tests covering service and need creation without authorization.
- [x] 3.2 Add or update frontend tests or manual QA coverage for title, compact filters, bottom pagination, and absence of correction actions. (No FE test runner in repo; covered by `tsc -b`/vite build + manual QA checklist.)
- [x] 3.3 Run backend test suite or targeted PHPUnit tests for publication controllers. (150 passed.)
- [x] 3.4 Run frontend lint/typecheck/build or targeted checks for Mis Publicaciones. (build passes; remaining lint errors are pre-existing repo-wide.)
- [x] 3.5 Validate the OpenSpec change before implementation. (`openspec validate` passed.)
