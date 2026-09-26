# Pontis

[![CI](https://github.com/armageAR/pontis/actions/workflows/ci.yml/badge.svg)](https://github.com/armageAR/pontis/actions/workflows/ci.yml)

Membership management platform for organizations that are structured as a network of local
groups (chapters, branches, clubs — referred to in the code as **workshops**).

Pontis gives an organization a single place to keep its directory of groups up to date, let
people request membership in the group they belong to, and delegate day-to-day member
administration to each group's own admins instead of one central operator.

It is a two-part application:

| Part | Stack | Directory |
| --- | --- | --- |
| REST API | Laravel 13 · PHP 8.3 · Sanctum · PostgreSQL | `pontis-api/` |
| Web client | React 19 · TypeScript · Vite 8 · React Router 7 | `pontis-app/` |

Built with the help of AI coding agents — [Claude Code](https://claude.com/claude-code) and
[Codex](https://openai.com/codex) — under human review.

---

## Features

**Accounts and onboarding**
- Self-service sign-up where the new member picks the group they want to join.
- Every new account starts as `pending`: email verification plus an explicit approval by a
  group admin or a superadmin is required before the account becomes `active`.
- Account lifecycle states: `pending`, `active`, `rejected`, `suspended`, `inactive` —
  login is blocked with a specific message for each non-active state.
- Token authentication with Laravel Sanctum; the client stores the bearer token and
  redirects to the login page on any `401`.

**Groups (workshops)**
- Full CRUD for groups, including zone, name and number, meeting day and frequency,
  address, city, province, country, language and free-form notes.
- Groups can be disabled instead of deleted; deletion is a soft delete.
- Public search endpoint used by the sign-up form so applicants can find their group
  without an account.

**Membership and permissions**
- Many-to-many membership through a pivot table that carries a per-group role
  (`member` / `admin`), a membership state (`pending` / `active`), whether the membership
  was requested by the user, and when the user last saw the decision.
- Two authorization layers: a global role (`user` / `superadmin`) and a per-group role, both
  enforced by a policy — a group admin manages only their own group, a superadmin manages
  everything.
- Join requests, approvals and rejections, leaving a group, assigning and removing members
  in bulk, and changing a member's role within a group.
- Dashboard that shows each admin the join requests waiting on them, and shows members the
  outcome of their own requests, with dismissible notifications.

**Directory import**
- A sync service fetches a public directory page, parses it, and diffs it against the stored
  groups, classifying entries as new, modified or no longer listed.
- Changes are always previewed first: the superadmin reviews the diff in the UI and chooses
  which new, modified and delisted entries to apply.
- Tracked fields (name, zone, meeting day and frequency, address, city, province, language)
  are compared field by field, and the raw source text plus a `last_synced_at` timestamp are
  kept for auditing.
- Also available headless: `php artisan workshops:sync-from-gla [--dry-run]`.
- Fields the page does not report are left untouched rather than overwritten with empty
  values, so hand-curated data survives a sync.

---

## Architecture

```
pontis/
├── pontis-api/                 Laravel REST API
│   ├── app/
│   │   ├── Enums/              UserStatus, WorkshopStatus
│   │   ├── Http/
│   │   │   ├── Controllers/    Auth, Dashboard, User, Admin\Workshop, Admin\WorkshopSync
│   │   │   ├── Requests/       Form-request validation for admin endpoints
│   │   │   └── Resources/      UserResource, WorkshopResource
│   │   ├── Models/             User, Workshop
│   │   ├── Policies/           WorkshopPolicy
│   │   ├── Services/           Directory sync service
│   │   ├── Notifications/      Email verification
│   │   └── Console/Commands/   workshops:sync-from-gla
│   ├── database/
│   │   ├── migrations/         users, workshops, user_workshop pivot, roles, statuses
│   │   └── seeders/            SuperAdmin, initial groups, demo users
│   ├── routes/api.php          All endpoints
│   └── tests/Feature/          Auth, user listing, workshop/membership coverage
│
└── pontis-app/                 React SPA
    └── src/
        ├── api/                Typed axios clients (auth, users, workshops, dashboard, sync)
        ├── components/         Design-system primitives + layouts + route guard
        ├── context/            AuthContext (session, current user, role helpers)
        └── pages/              home, login, register, pending, verify-email,
                                dashboard, workshops, users
```

The frontend has no UI framework dependency: each component ships with its own CSS file and
`lucide-react` provides the icons. Imports use the `@/` alias for `src/`.

---

## Getting started

### Requirements

- PHP 8.3+ with the `pdo_pgsql`, `mbstring`, `xml`, `curl`, `tokenizer`, `bcmath`, `ctype`,
  `fileinfo`, `dom`, `openssl` and `zip` extensions
- Composer
- Node.js 22+ (Vite 8 requires Node 20 or newer)
- PostgreSQL 14+ (SQLite works for running the test suite)

### API

```bash
cd pontis-api
composer install
cp .env.example .env
php artisan key:generate
# set DB_* in .env, then:
php artisan migrate
php artisan db:seed --class=SuperAdminSeeder    # initial superadmin account
php artisan db:seed --class=WorkshopSeeder      # initial group directory
php artisan db:seed --class=DemoUsersSeeder     # optional: fake members for local testing
php artisan serve
```

`SuperAdminSeeder` takes its credentials from `SUPERADMIN_EMAIL` and `SUPERADMIN_PASSWORD`.
There is no hardcoded password: if `SUPERADMIN_PASSWORD` is empty, a random one is generated
and printed once in the seeder output. The seeder never touches an account that already
exists, so it is safe to run on every deploy.

### Web client

```bash
cd pontis-app
npm install
npm run dev
```

In development, Vite proxies `/api` to `http://pontis.api.local` (see `vite.config.ts`).
Either point that hostname at your local API or set `VITE_API_URL` to the API base URL.

### Useful commands

```bash
# API
php artisan test                            # PHPUnit feature + unit tests
./vendor/bin/pint                           # code style
php artisan workshops:sync-from-gla --dry-run

# Web client
npm run build                               # tsc -b && vite build
npm run lint                                # ESLint
npm run preview
```

---

## Configuration

Backend (`pontis-api/.env`):

| Variable | Purpose |
| --- | --- |
| `APP_URL` | Public URL of the API; used to build email verification links |
| `FRONTEND_URL` | SPA URL the API redirects to after email verification |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Database |
| `SANCTUM_STATEFUL_DOMAINS` | Domains allowed to use stateful Sanctum auth |
| `MAIL_MAILER` and related `MAIL_*` | Delivery of verification email (`log` by default) |
| `SUPERADMIN_NAME`, `SUPERADMIN_EMAIL`, `SUPERADMIN_PASSWORD` | Initial superadmin account; an empty password means one is generated |

Frontend (`pontis-app/.env`):

| Variable | Purpose |
| --- | --- |
| `VITE_API_URL` | API base URL; falls back to the relative `/api` path |

---

## API reference

All routes are prefixed with `/api`. Every endpoint except the public ones requires the
`Authorization: Bearer <token>` header.

**Public**

| Method | Endpoint | Description |
| --- | --- | --- |
| `POST` | `/register` | Sign up and request membership in a group |
| `POST` | `/login` | Authenticate and receive a token |
| `GET` | `/workshops/search` | Search active groups (used by the sign-up form) |
| `GET` | `/email/verify/{id}/{hash}` | Confirm an email address |

**Authenticated**

| Method | Endpoint | Description |
| --- | --- | --- |
| `POST` | `/logout` | Revoke the current token |
| `GET` | `/me` | Current user |
| `GET` | `/account-status` | Approval and email-verification state |
| `POST` | `/email/resend-verification` | Resend the verification email |
| `GET` | `/dashboard` | Pending join requests and membership notifications |
| `GET` | `/my-workshops` | Groups the current user belongs to |
| `GET` | `/users` | Paginated member list, filterable by search, role, status, group and group role, and sortable (scoped by role) |
| `PATCH` | `/users/{user}` | Update a member |
| `PATCH` | `/users/{user}/status` | Change a member's account status |
| `PATCH` | `/users/{user}/password` | Change a member's password |
| `POST` \| `PATCH` \| `DELETE` | `/users/{user}/workshops/{workshop}` | Add a membership, change its role, remove it |
| `POST` | `/workshops/{workshop}/dismiss-notification` | Mark a membership decision as seen |

**Administration** (`/api/admin`, group admin or superadmin depending on the action)

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` \| `POST` | `/workshops` | List and create groups |
| `GET` \| `PATCH` \| `DELETE` | `/workshops/{workshop}` | Read, update, soft-delete a group |
| `POST` | `/workshops/{workshop}/disable` \| `/enable` | Toggle a group's availability |
| `POST` | `/workshops/{workshop}/join` | Request membership |
| `DELETE` | `/workshops/{workshop}/leave` | Leave a group |
| `POST` | `/workshops/{workshop}/join-requests/{user}/approve` \| `/reject` | Resolve a join request |
| `GET` \| `POST` \| `DELETE` | `/workshops/{workshop}/users` | List, assign and remove members in bulk |
| `POST` | `/workshops/gla/preview` | Diff the external directory against stored groups (superadmin) |
| `POST` | `/workshops/gla/apply` | Apply the selected parts of that diff (superadmin) |

---

## Deployment

Both services ship with Railway configuration (`railway.toml` + `nixpacks.toml`) and deploy
independently:

- **API** — Nixpacks installs PHP 8.3 and dependencies, caches config/routes/views, then on
  start runs migrations, seeds the superadmin and the initial group directory, and serves the
  app. Health check: `/up`. Set `APP_KEY` (`php artisan key:generate --show`) and the
  `SUPERADMIN_*` variables in the host's environment — never in a committed file.
- **Web client** — builds with `npm ci && npm run build` on Node 22 and serves `dist/` as a
  static SPA. Health check: `/`.

Nothing is Railway-specific beyond those two files; any PHP host plus any static host works.
`start-server.sh` at the repository root runs migrations, builds the frontend and starts the
dev server in one step.

---

## Testing and CI

The feature suite covers authentication and token handling, the member listing with its
role-based scoping, and group/membership management including the policy rules:

```bash
cd pontis-api
php artisan test                            # 136 tests, 3 of which fail — see issue #1
php artisan test --exclude-group known-failure   # what CI runs: 133 passing
```

`.github/workflows/ci.yml` runs on every push to `main` and on every pull request:

| Job | Blocking | What it runs |
| --- | --- | --- |
| API tests | yes | `php artisan test --exclude-group known-failure` on PHP 8.3 |
| Web client build | yes | `npm run build` (`tsc -b && vite build`) on Node 22 |
| API code style | yes | `./vendor/bin/pint --test` |
| Web client lint | yes | `npm run lint` |

The three tests tracked in [#1](https://github.com/armageAR/pontis/issues/1) are marked
`#[Group('known-failure')]` and excluded in CI so that the build is a real signal instead of
permanently red. They are not skipped locally: a plain `php artisan test` still runs them and
still fails, which is the point. Every other job is blocking, so a style or lint regression
fails the build.

The web client has no automated tests yet, so changes to it are verified by `npm run lint`,
the type-checking build, and manual checks against a running instance.

---

## Known issues

- [**#1 — The group listing is not scoped by membership.**](https://github.com/armageAR/pontis/issues/1)
  `GET /api/admin/workshops` returns the whole directory to any authenticated user. Three
  tests in `pontis-api/tests/Feature/WorkshopTest.php` expect that scoping to be implicit and
  currently fail, so **a fresh clone runs 133 of 136 tests green**. Whether the tests or the
  controller are wrong is still an open decision; see the issue.
- The directory importer cannot always tell where a street ends and a city begins, because the
  source page appends the city to the address as free text with no delimiter. When the
  boundary is ambiguous the importer reports no city rather than guessing, and leaves the
  stored value untouched — so a handful of entries keep whatever was curated by hand. See
  [#4](https://github.com/armageAR/pontis/issues/4) for the reasoning.

## Security

- No credentials are committed: `.env` files are ignored and `.env.example` ships with empty
  or placeholder values only.
- The initial superadmin password is environment-driven or randomly generated, never a
  known default.
- `UserFactory` uses the password `password` for fake members. It is only reachable through
  the test suite and `DemoUsersSeeder`; do not run `DemoUsersSeeder` on a public instance.
- Found a vulnerability? Please open a private report through the repository's security
  advisories rather than a public issue.

## Notes for contributors

- The domain entity is called `Workshop` throughout the code and API; read it as "group".
- Parts of the codebase are Spanish and Argentina-specific — seed data, some user-facing
  response strings, and the directory importer, which is written against one specific public
  directory page and is intentionally coupled to it.
- Keep authorization decisions in `WorkshopPolicy` rather than in controllers.
- Frontend: one component per file with a sibling `.css`, imports through the `@/` alias.

---

## License

Released under the [MIT License](LICENSE).
