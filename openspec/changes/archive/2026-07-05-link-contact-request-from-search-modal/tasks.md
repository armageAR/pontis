## 1. Shared Contact Flow

- [x] 1.1 Extract the contact request form/modal from `PersonPage` into a reusable component.
- [x] 1.2 Ensure the reusable component supports `requesteeId`, display name, `source="search"`, shared fields, reason selection, submit, success callback, and close behavior.
- [x] 1.3 Change the reason field so the user must explicitly select a reason before submit.
- [x] 1.4 Update `PersonPage` to use the reusable component without changing its existing behavior.

## 2. Search Profile Modal Integration

- [x] 2.1 Add `can_request_contact` to the `PersonProfileModal` profile type.
- [x] 2.2 Add a "Solicitar contacto" action to `PersonProfileModal` when a loaded profile is not the current user.
- [x] 2.3 Open the reusable contact request flow from `PersonProfileModal`.
- [x] 2.4 Keep `PersonProfileModal` open after successful send and show "Solicitud enviada".
- [x] 2.5 Show the contact action disabled when `can_request_contact === false`.
- [x] 2.6 Add hover/focus explanation for disabled contact action.
- [x] 2.7 Ensure anonymous/incognito modal profiles can request contact when allowed without revealing hidden identity fields.

## 3. Tests

- [x] 3.1 Add frontend tests for opening a profile from Hermanos search and seeing "Solicitar contacto" when allowed.
- [x] 3.2 Add frontend tests that submitting from the search modal creates a contact request with `source=search`.
- [x] 3.3 Add frontend tests that successful submit keeps the profile modal open and shows "Solicitud enviada".
- [x] 3.4 Add frontend tests for disabled contact action and explanatory text/title when `can_request_contact === false`.
- [x] 3.5 Add frontend tests that reason selection is required and no implicit default reason is submitted.
- [x] 3.6 Run affected frontend tests and typecheck.
