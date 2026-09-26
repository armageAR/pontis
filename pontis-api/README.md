# Pontis API

Laravel 13 REST API for Pontis — membership management for organizations made up of local
groups. See the [root README](../README.md) for the full project overview, architecture and
API reference.

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
# configure DB_* and SUPERADMIN_* in .env, then:
php artisan migrate
php artisan db:seed --class=SuperAdminSeeder   # prints a generated password if none is set
php artisan db:seed --class=WorkshopSeeder     # initial group directory
php artisan db:seed --class=DemoUsersSeeder    # optional fake members for local testing
php artisan serve
```

## Commands

```bash
php artisan test                            # feature + unit tests
./vendor/bin/pint                           # code style
php artisan workshops:sync-from-gla --dry-run
```

## Layout

- `app/Http/Controllers` — auth, dashboard, users, admin group management, directory sync
- `app/Models` — `User`, `Workshop`
- `app/Policies/WorkshopPolicy` — all authorization rules
- `app/Services/GlaSyncService` — fetches and diffs the public group directory
- `routes/api.php` — every endpoint
- `tests/Feature` — auth, user listing, groups and memberships

Licensed under the [MIT License](../LICENSE).
