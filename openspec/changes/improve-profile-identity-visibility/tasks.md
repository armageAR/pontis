## 1. Data Model

- [x] 1.1 Add storage for the independent Identidad anonymous-search preference.
- [x] 1.2 Add migration or compatibility handling for existing `identity = anonymous` records, mapping them to anonymous-search enabled and a conservative Identidad audience.
- [x] 1.3 Update backend validation/types so Identidad audience accepts only audience levels and the anonymous-search preference is saved separately.

## 2. Backend Behavior

- [x] 2.1 Update `/profile/visibility` read responses to return Identidad audience and anonymous-search preference independently.
- [x] 2.2 Update `/profile/visibility` write handling to allow changing Identidad audience without changing anonymous-search preference.
- [x] 2.3 Update Hermano search logic so identity visibility is evaluated before revealing name, last name, matricula, or email.
- [x] 2.4 Update Hermano search logic so non-qualified viewers can see an anonymous result only when anonymous-search preference is enabled and the match does not depend on hidden identity fields.
- [x] 2.5 Update public/profile shaping so anonymous results never expose identity fields to non-qualified viewers.

## 3. Frontend Profile UI

- [x] 3.1 Update profile visibility API types to represent Identidad audience and anonymous-search preference separately.
- [x] 3.2 Split the Identidad privacy UI into an audience selector and a separate anonymous-search checkbox or toggle.
- [x] 3.3 Keep the audience selector enabled when anonymous-search preference is enabled.
- [x] 3.4 Add concise helper text explaining that selected viewers see identity and other eligible viewers see an anonymous result only when enabled.
- [x] 3.5 Ensure saving one Identidad control preserves the other control's current value.
- [x] 3.6 Move the profile data save button to the bottom-right footer area of the profile page.
- [x] 3.7 Review section labels, helper text, and spacing on the profile page so the privacy controls and save action are clear.

## 4. Verification

- [x] 4.1 Add backend tests for qualified viewer sees identity when audience allows it, even with anonymous-search enabled.
- [x] 4.2 Add backend tests for non-qualified viewer sees anonymous result when anonymous-search is enabled and the query uses eligible non-identity criteria.
- [x] 4.3 Add backend tests for non-qualified viewer does not match by hidden identity fields.
- [x] 4.4 Add frontend coverage or manual verification for separated controls, enabled selector, persistence, helper copy, and bottom-right save button placement.
- [x] 4.5 Run relevant backend and frontend checks.
