## 1. Data And API Foundations

- [x] 1.1 Audit existing `user_workshop` data for users with zero or multiple active principal Talleres.
- [x] 1.2 Add or update an endpoint to list profile workshop memberships including active and pending relationships, `status`, `my_role`, `is_principal`, and Taller display fields.
- [x] 1.3 Ensure the profile membership list does not use the active-only `workshops()` relation when pending relationships must be shown.
- [x] 1.4 Reuse the existing join request behavior for profile-initiated workshop join requests.

## 2. Backend Membership Actions

- [x] 2.1 Add an endpoint or controller action to mark one active Taller as the user's principal Taller.
- [x] 2.2 Implement principal change inside a database transaction that clears the previous principal and marks the selected active membership as principal.
- [x] 2.3 Reject principal changes for pending, rejected, inactive, or nonexistent memberships.
- [x] 2.4 Update leave-workshop behavior to reject attempts to leave the principal Taller.
- [x] 2.5 Update leave-workshop behavior to remove the user's cargos associated with the Taller being left.
- [x] 2.6 Return clear validation/error messages for principal-leave attempts and invalid principal changes.

## 3. Frontend Profile Section

- [x] 3.1 Add a "Mis Talleres" section to the profile page.
- [x] 3.2 Render a table with Taller identity, membership status, principal indicator, and action icons.
- [x] 3.3 Add an empty state when the user has no active or pending Taller relationships.
- [x] 3.4 Add an "Agregar Taller" action that opens a modal.
- [x] 3.5 Implement modal search by Taller name or number using existing workshop search behavior.
- [x] 3.6 Show or enable "Solicitar unirse" only after a Taller is selected.
- [x] 3.7 After a successful join request, close or reset the modal and refresh the table showing "Pendiente de aprobacion".

## 4. Frontend Actions And Confirmations

- [x] 4.1 Add a leave icon action for non-principal Talleres.
- [x] 4.2 Show confirmation before leaving a Taller and do nothing when cancelled.
- [x] 4.3 Disable or hide leave action for the principal Taller and explain that it cannot be removed.
- [x] 4.4 Add an icon action to mark an active non-principal Taller as principal.
- [x] 4.5 Show confirmation naming the current principal Taller and the selected new principal Taller before changing principal.
- [x] 4.6 Refresh the profile workshop table and related profile state after leaving a Taller or changing principal.

## 5. Verification

- [x] 5.1 Add backend tests for profile membership listing with active and pending Talleres.
- [x] 5.2 Add backend tests for profile join request creation and duplicate prevention.
- [x] 5.3 Add backend tests for rejecting leave on principal Taller.
- [x] 5.4 Add backend tests that leaving a non-principal Taller removes the membership and associated cargos.
- [x] 5.5 Add backend tests for atomic principal change and rejection of pending/nonexistent memberships.
- [x] 5.6 Add frontend verification for table rendering, add modal, pending status, leave confirmation, principal blocking, and principal-change confirmation.
- [x] 5.7 Run relevant backend and frontend checks.
