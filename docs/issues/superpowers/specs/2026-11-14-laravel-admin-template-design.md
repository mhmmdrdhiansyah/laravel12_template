# Laravel 12 Admin Panel Template — Design

## Intent
Reusable starting template for every new system that needs an admin panel. After clone + `docker compose up`, the project ships with working auth, an admin panel, and roles/permissions management, styled with Metronic.

## Goals
- Laravel 12, Blade templating.
- Fortify for auth (login, logout, forgot/reset password).
- Spatie Laravel Permission for roles & permissions.
- Metronic 8.3.0 demo1 (Bootstrap 5) as the admin UI: `dist/` assets used directly, HTML split into Blade layouts and partials (header, sidebar, footer, scripts).
- Docker: `nginx:alpine` + `php:8.3-fpm-alpine`. MySQL comes from the existing running container `mysql` (image `mysql:8.4`) — do NOT create a new MySQL service; join network `mysql_net`. No fixed `container_name` (the template is cloned per project; fixed names collide).
- All tooling (composer, artisan) runs inside the container from the Docker task onward. The host needs PHP + composer ONLY for the initial `create-project` scaffold.
- Features in v1:
  1. Auth: login, logout, forgot/reset password (Fortify, custom Blade views).
  2. Admin panel: dashboard (simple, placeholder widgets), Users CRUD with role assignment, Roles CRUD with permission checkboxes, Permissions list.
  3. Profile page per user: upload profile photo, edit personal data, change password.
  4. Seeders: super admin user (all access via `Gate::before`), default roles `super-admin` and `user`, base permissions.
  5. Role `user` after login: sees the dashboard only, plus their own profile page. All other admin menu items/routes are hidden and blocked by permission middleware. Tests are NOT part of the template (curl-based verification only).

## Non-goals
- No business-domain modules, no API layer, no multi-tenancy.
- No Livewire, Inertia, or frontend SPA.
- No Tailwind (Metronic here is Bootstrap 5).
- Do not rebuild Metronic from Gulp source; use the prebuilt `dist/` at `/mnt/share/template/demo1/dist` as the asset source (authoring machine only — the copied assets are committed, cloners do not need this path).

## Constraints
- PHP 8.3 (stable for Laravel 12), Alpine-based images.
- MySQL credentials from `.env`, never hardcoded in code or Dockerfile. Dev defaults DB `app`, user `app`, password `app` — matching the existing container; these are DEV-ONLY values, README must say so.
- Super admin bypasses permission checks via `Gate::before` — no per-permission assignment.
- Existing container: name `mysql`, port 3306 (published to host), network `mysql_net`.
- Bind-mounted workspace: php-fpm pool user is rebuilt to uid/gid `1000` (build arg `UID`) so container writes match the host user. Documented in README; override the build arg if the host user differs.
- Upload limit 2MB (Laravel validation) with `client_max_body_size 8m` in nginx and matching `php.ini` limits, so large files fail validation with a readable error instead of a raw nginx 413.

## Decisions already made (by the user or resolved in the plan)
- Spatie Laravel Permission (roles + permissions).
- Fortify for auth. Post-login destination `/admin` (Fortify `home` + `redirectUsersTo` + root redirect).
- Metronic demo1, Bootstrap 5 variant, dist assets. `media/` copied to `public/assets/media` so Metronic's `assets/media/...` references stay valid.
- **UI must be derived from Metronic's own reference pages — never invented.** A mandatory documentation-study task runs BEFORE any view is written: read `dist/documentation/getting-started.html`, `file-structure.html`, and the relevant component pages; produce `docs/superpowers/metronic-notes.md` mapping every planned view to its source reference page.
- List pages (Users, Roles) use the table pattern from `dist/apps/user-management/users/list.html` with client-side jQuery DataTables (jQuery is bundled in `plugins.bundle.js`; init pattern from its `table.js`). Rows are rendered by Blade; delete buttons POST to the real destroy route — the demo's client-side-only delete is NOT copied. Roles multi-select is a plain `<select multiple>` (this dist bundles no select2). Dashboard copies stat/widget cards from `dist/index2.html`; charts optional only if they work without console errors (ApexCharts is inside `plugins.bundle.js`).
- nginx + php-fpm, Alpine images; no fixed container names; `8088:80` host port.
- Seeder creates super admin, a plain `user` role (no permissions), and exactly 9 permissions:
  `users.view`, `users.create`, `users.edit`, `users.delete`, `roles.view`, `roles.create`, `roles.edit`, `roles.delete`, `permissions.view`.
  Route middleware gates the `.view` permissions; controller `$this->authorize()` gates create/edit/delete per action. No `dashboard.view` (dashboard is auth-only — role `user` must see it) and no `roles.assign` (role assignment is part of `users.edit`).
- Users table gains `avatar_path` via its own migration (nullable string), stored on the `public` disk under `avatars/`.
- Docker comes early in the plan; every later task runs composer/artisan via `docker compose exec -u www-data app ...`, so `DB_HOST=mysql` (container-network hostname) is the only DB host ever used.

## Nothing left to the builder
All former open points (Blade partial structure, asset copy list, permission names, compose wiring) are decided in the plan: `docs/superpowers/plans/2026-11-14-laravel-admin-template.md`.
