# CLAUDE.md

## Project

Pontis is a web application for a private, validated community of Hermanos.

The goal of Pontis V1 is to let validated Hermanos maintain personal profiles, manage visibility, belong to one or more Talleres / Logias, register grades and cargos with history, offer services, publish needs, search for help inside the community, and initiate contact with privacy and consent.

Pontis is not a public social network, not a marketplace, and not a transactional platform.

---

## Required reading before making changes

Before changing business logic, models, migrations, permissions, search, workflows, visibility, roles, Talleres, user validation, services, needs, publications, contact requests, notifications, or audit behavior, read the relevant project docs.

Main project docs:

* `docs/project/overview.md`
* `docs/project/domain-model.md`
* `docs/project/permissions.md`
* `docs/project/workflows.md`
* `docs/project/decisions.md`

Use these documents as the source of truth for product and business rules.

If code and docs disagree, stop and report the mismatch before changing behavior.

---

## Documentation responsibilities

When implementing or changing domain behavior:

* Do not invent rules that are not present in the docs.
* If a rule is missing, ask or mark it as an open question.
* If a decision changes, update `docs/project/decisions.md`.
* If a new workflow is introduced, update `docs/project/workflows.md`.
* If an entity or relationship changes, update `docs/project/domain-model.md`.
* If a permission or visibility rule changes, update `docs/project/permissions.md`.

---

## Core domain rules

### Hermano / Usuario / Persona

* Persona and Usuario are the same functional entity.
* Every registered person is a user.
* Every user represents a real person with a personal profile.
* The UI should prefer the term `Hermano`.
* `Usuario` is mainly for authentication, access, roles, permissions, and system states.
* Do not create a separate functional module called `Personas`.
* Do not create a separate menu called `Personas`.

### Privacy

* Everything is private by default.
* Visibility is opt-in.
* A user decides what to show, to whom, and under what conditions.
* Search must never reveal more information than visibility allows.
* Contact must require consent when direct contact is not explicitly enabled.
* When in doubt, expose less information.

### Public access

* Without login, V1 only has a public homepage and registration entry point.
* No public profiles.
* No public Hermanos list.
* No public Talleres list.
* No public services.
* No public needs.
* No public publications.
* No public search.

### Validation

* A registered user must be validated before accessing the internal system.
* Pending users cannot search, view internal information, publish, or contact Hermanos.
* Validation can be performed by a Superadmin or by an Admin de Taller for the declared Taller principal.

### Talleres / Logias

* A Hermano can belong to multiple Talleres.
* Every Hermano must have one Taller principal.
* A Hermano can be admin in one Taller and a regular user in another.
* A Hermano can have a cargo in one Taller, another cargo in another Taller, or no cargo.
* Permissions related to Talleres must be evaluated per Taller.

### Grades

V1 grades:

* Aprendiz
* Compañero
* Maestro

Rules:

* Every active Hermano must have a current grade.
* Grade history must be preserved.
* Maestro is the highest grade in V1.
* Being Maestro does not grant general administrative permissions.
* Publishing does not depend on grade: any registered, validated user may publish without prior authorization (see decisions.md Decision 16). Being Maestro grants no extra publishing privilege.

### Cargos

* Cargos are optional.
* Cargos are contextual to a Taller.
* Cargos must preserve history.
* A cargo can grant contextual permissions only within its Taller.
* A cargo does not replace administrative roles.
* A cargo in one Taller does not grant permissions over another Taller.
* Expired cargos must not grant active permissions.

### Roles

Base roles for V1:

* Superadmin
* Admin de Taller
* Usuario / Hermano

Rules:

* Superadmin can administer the whole system.
* Admin de Taller can administer only within assigned Talleres.
* Usuario / Hermano can manage their own profile, visibility, services, needs, publications, and contact requests according to permissions.
* Roles, grades, and cargos are different concepts. Do not merge them.

### Sensitive data

Users cannot directly edit sensitive data.

Sensitive data includes:

* Nombre
* Apellido, if treated as legal identity
* Documento / DNI
* Matrícula masónica nacional
* Taller principal inicial, when it affects institutional validation

Sensitive changes require a request and Superadmin approval.

### Search

Search must be broad.

It may search by:

* Hermano
* Taller
* Profession
* Trade / oficio
* Service
* Need
* Product
* Category
* Rubro
* City
* Province
* Country
* State
* Zone
* Location data enabled by visibility

Search result types:

* Identified result
* Partially identified result
* Anonymous result
* Institutional result

Search must respect visibility and consent.

Search must not reveal private data or allow sensitive inference through filters.

### Contact

* Contact must respect consent.
* If direct contact is not enabled, create a contact request.
* The requester chooses what information to share.
* The recipient can accept or reject.
* If rejected, no additional information is revealed.
* Contact request decisions must be auditable.

### Services, needs, and publications

Pontis V1 is not a marketplace.

Do not implement:

* Payments
* Cart
* Direct contracting
* Reviews
* Ratings
* In-system commercial transaction flow

Services, needs, and publications exist to help Hermanos find help and initiate contact.

### Publications

* Only active and validated users can create publications.
* No public publications without login.
* Publications require a visibility/audience selection.
* Publications must show a preview of visible data before publishing.
* Publications do not change the general visibility of the user profile.
* Publishing is free for any registered, validated user: no prior administrative approval, regardless of grade (see decisions.md Decision 16).
* A new publication is available immediately in the author's own status choice; there is no `pending_authorization` state and no "Pedir corrección" action in the publication lifecycle.
* Admin intervention on publications (takedown/suspension) is an exceptional, audited, after-the-fact action, not a prerequisite to publish.
* Being Maestro does not grant admin permissions.

### Crawler

* The crawler may import or update Talleres / Logias only.
* The crawler reads only public sources.
* The crawler must not scrape sites that require login.
* The crawler must not create users.
* The crawler must not create personal profiles.
* The crawler must show differences before applying changes.
* Superadmin must confirm changes before they are applied.
* Crawler execution and confirmation must be audited.

### Audit

Audit relevant functional actions, not every minor interaction.

Audit must include:

* What happened.
* Who did it.
* When it happened.
* Which entity was affected.
* Which role, cargo, or permission was used.
* What decision was made, when applicable.

Audit must not become an indirect way to access private data.

### Notifications

* Notifications must not reveal unnecessary sensitive data.
* Notifications must respect roles, Talleres, cargos, visibility, privacy, and consent.
* Pending users must not receive notifications about internal content.
* Do not notify users for every common search appearance.

---

## Development rules

When asked to implement a feature:

1. Read the relevant docs first.
2. Inspect the current code before proposing changes.
3. Report mismatches between code and docs.
4. Propose a small implementation plan.
5. Implement in small steps.
6. Include tests when behavior changes.
7. Do not move to unrelated tasks without explicit instruction.

---

## Coding expectations

Prefer:

* Small, focused changes.
* Clear domain naming.
* Tests for permissions, visibility, states, and workflows.
* Explicit state transitions.
* Auditable sensitive actions.
* Business logic outside controllers when possible.
* Policies or equivalent permission checks for protected actions.
* Database constraints where they protect important invariants.

Avoid:

* Large unrelated refactors.
* Silent behavior changes.
* Hardcoding business rules in many places.
* Mixing roles, grades, and cargos.
* Exposing private data in search, notifications, logs, audit, or admin screens.
* Implementing out-of-scope V1 features.

---

## Testing expectations

Add or update tests when changing:

* Registration and validation.
* User states.
* Sensitive data changes.
* Taller membership.
* Grade history.
* Cargo history.
* Roles and permissions.
* Visibility.
* Search results.
* Contact requests.
* Services.
* Needs.
* Publications.
* Crawler behavior.
* Notifications.
* Audit events.

Permission and visibility tests are especially important.

---

## UI wording

Preferred UI terms:

* Use `Hermanos` for the main people/member area.
* Use `Talleres` or `Talleres / Logias` for workshops/lodges.
* Use `Usuario` only where the context is authentication, system access, roles, permissions, or technical administration.
* Avoid a `Personas` module or menu.
* Use `O∴ Eterno` instead of `Fallecido` if this state is shown in the UI.

Each main screen should include a short functional description below the title explaining what the section allows the user to do.

The sidebar menu should be hideable/collapsible, especially on small screens.

---

## V1 out of scope

Do not implement unless explicitly requested later:

* Marketplace
* Payments
* Cart
* Direct contracting
* Reviews or ratings
* Full internal chat
* Public social network
* Public profiles without login
* Advanced reports
* Sophisticated automations
* Extremely dynamic permissions engine
* Non-essential external integrations
* Complete final visual design
* Advanced mobile experience
* Taller financial management
* Meeting calendar
* Attendance management
* Minutes / actas
* Voting
* Automatic grade promotion
* Automatic cargo renewal

---

## Conflict resolution

If two rules conflict:

1. Prefer the rule that exposes less private information.
2. Prefer explicit consent before contact.
3. Prefer human review for sensitive actions.
4. Prefer audited administrative action for exceptions.
5. Prefer the project docs over assumptions.
6. If still unclear, stop and ask.

---

## Useful task prompts

### Analyze before coding

Read `CLAUDE.md` and the relevant files under `docs/project`.

Then inspect the current implementation related to the requested feature.

Do not modify code yet.

First report:

* What the docs say.
* What the code currently does.
* Any mismatch.
* Suggested implementation plan.

### Implement one step only

Implement only the first step of the approved plan.

Keep the change small.

Include or update tests.

Do not continue to the next step until requested.

### Check permissions

Before changing this feature, read:

* `docs/project/permissions.md`
* `docs/project/domain-model.md`
* `docs/project/decisions.md`

Then verify that the change does not expose private data, bypass consent, or mix roles, grades, and cargos.

### Check workflow

Before changing this flow, read:

* `docs/project/workflows.md`
* `docs/project/permissions.md`
* `docs/project/decisions.md`

Then verify states, permissions, notifications, and audit behavior.
