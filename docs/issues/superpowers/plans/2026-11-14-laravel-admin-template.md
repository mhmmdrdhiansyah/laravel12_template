# Laravel 12 Admin Panel Template Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reusable Laravel 12 starter template: clone → `docker compose up` → working login + Metronic admin panel with users/roles/permissions management and profile page.

**Architecture:** Blade + Fortify (auth) + Spatie Laravel Permission (roles/permissions). Metronic 8.3.0 demo1 Bootstrap 5 prebuilt `dist/` assets copied into `public/`, HTML split into Blade layouts/partials — every view derived from a Metronic reference page (Task 6 studies the docs first). Two Docker services (nginx-alpine, php-fpm-alpine) joining the existing `mysql` container's network. Docker is set up in Task 2; every later composer/artisan command runs inside the container.

**Tech Stack:** Laravel 12, PHP 8.3, Fortify, Spatie Laravel Permission v6, Bootstrap 5 (Metronic dist), jQuery + DataTables (bundled in Metronic's `plugins.bundle.js` / `datatables.bundle.js`), Nginx Alpine, Docker Compose, MySQL 8.4 (external container).

**Spec:** `docs/superpowers/specs/2026-11-14-laravel-admin-template-design.md`

## Preconditions (verify before Task 1)

- Host has PHP 8.3 + composer + rsync (needed ONLY for the Task 1 scaffold).
- Docker Engine + compose plugin working on the host.
- Existing container `mysql` (image `mysql:8.4`) running on network `mysql_net`, with db `app`, user `app`, password `app`, port 3306 published to the host.
- Metronic source exists at `/mnt/share/template/demo1/dist` (authoring machine only; Task 5 commits the copied assets, cloners never need this path).
- Host user uid/gid is `1000` (default on single-user Linux). If not, pass `UID=<your-uid>` build arg in Task 2.

## Global Constraints

- PHP 8.3 (`php:8.3-fpm-alpine`), Nginx (`nginx:alpine`). No other images except `composer:2` used as a build-stage source for the composer binary.
- MySQL comes from the EXISTING container `mysql`. NEVER define a MySQL service in compose; join `mysql_net` as an external network.
- DB credentials read from `.env` (`DB_HOST=mysql` — container-network hostname), never hardcoded in code or Dockerfile. `app`/`app`/`app` are DEV-ONLY defaults.
- No `container_name` anywhere in compose (template clones must not collide on one host). Address services by name: `app`, `nginx`.
- Host-side composer/php is used ONLY in Task 1. From Task 2 on, run composer/artisan as:
  `docker compose exec -u www-data app <command>` (the image rebuilds `www-data` to uid 1000 so container writes stay owned by the host user).
- **UI rule: every view must be copied/adapted from a Metronic dist reference page listed in `docs/superpowers/metronic-notes.md` (Task 6). Never invent markup or CSS classes. Do NOT use plugins that this dist does not bundle (no select2 — plain `<select multiple>` for roles; jQuery and ApexCharts ARE bundled inside `plugins.bundle.js`).**
- No tests in the template. Every task verifies via running commands (artisan/docker/curl). All `curl -X POST` verifications MUST send the CSRF token — use the login helper from Task 7 verbatim.
- No Livewire, Inertia, Tailwind, API routes, or business modules.
- Metronic asset source: `/mnt/share/template/demo1/dist` — use prebuilt bundles as-is, do NOT rebuild from Gulp source. `media/` goes to `public/assets/media` (keep Metronic's `assets/media/...` references working).
- Super admin gets everything via `Gate::before` (no per-permission assignment).
- Role `user` sees ONLY dashboard + own profile; everything else blocked by permission middleware and hidden from sidebar.
- Permission naming: `<resource>.<action>`. Exactly 9 permissions: `users.view`, `users.create`, `users.edit`, `users.delete`, `roles.view`, `roles.create`, `roles.edit`, `roles.delete`, `permissions.view`. Route middleware checks the `.view` permission of each resource; controllers call `$this->authorize('<resource>.<action>')` for create/edit/delete. No `dashboard.view`, no `roles.assign`.
- Composer packages: `laravel/fortify`, `spatie/laravel-permission`. Nothing else beyond Laravel defaults.

## Review Focus

- Non-permitted user hitting an admin URL directly (e.g. `/admin/users`): must get 403, not a blank page or error 500. → Task 8 verification.
- `user`-role login: sidebar must show only Dashboard + Profile; dashboard must render with zero permissions. → Task 8 verification.
- Profile photo upload: non-image or >2MB file must show a validation error, not corrupt storage; replacing a photo must delete the old file. → Task 9 verification.
- Seeded super admin must log in and see ALL menus after fresh `migrate:fresh --seed`. → Task 10 verification.
- Container boot from clean state (`docker compose down && docker compose up -d --build`): PHP-FPM permissions on `storage/` and `bootstrap/cache/` must not 500 (uid-1000 pool user is the fix; verified at first boot in Task 2 and re-verified on fresh clone in Task 10).
- Any `curl -X POST` returning 419 means the CSRF token was not sent — re-read the Task 7 helper, do not "fix" it by disabling CSRF.
- Any admin page whose markup does not match its reference page recorded in `metronic-notes.md` fails review — re-copy from the Metronic source.

---

### Task 1: Scaffold Laravel 12 project (host tooling — the only host task)

**Files:**
- Create: whole Laravel app in repo root (via `composer create-project`)

**Interfaces:**
- Produces: working Laravel app; later tasks modify `routes/web.php`, `app/Models/User.php`, `.env`, `composer.json`.

- [ ] **Step 1: Create project into a temp dir, then merge into repo root (root already contains `docs/`)**

```bash
cd /home/aptika/project/template_new
composer create-project laravel/laravel temp_app --prefer-dist
rsync -a temp_app/ ./ && rm -rf temp_app
```

- [ ] **Step 2: Prepare `.env`**

```bash
cp .env.example .env
```

Edit `.env`: `DB_CONNECTION=mysql`, `DB_HOST=mysql`, `DB_PORT=3306`, `DB_DATABASE=app`, `DB_USERNAME=app`, `DB_PASSWORD=app`. Do NOT run `key:generate` on the host — Task 2 does it in the container.

- [ ] **Step 3: Verify scaffold**

Run: `php artisan --version`
Expected: `Laravel Framework 12.x.x`

- [ ] **Step 4: Init git and commit**

```bash
git init && git add -A && git commit -m "chore: scaffold laravel 12"
```

### Task 2: Docker (nginx + php-fpm, Alpine, external mysql, uid-matched pool user)

**Files:**
- Create: `docker/php/Dockerfile`, `docker/php/php.ini`, `docker/nginx/default.conf`, `docker-compose.yml`, `.dockerignore`

**Interfaces:**
- Consumes: existing container `mysql` on network `mysql_net`.
- Produces: services `app` (php-fpm) and `nginx` (port `8088:80`); external network `mysql_net`. Every later task uses `docker compose exec -u www-data app ...`.

`docker-compose.yml` (exact):

```yaml
services:
  app:
    build:
      context: ./docker/php
      args:
        UID: 1000
    volumes:
      - ./:/var/www/html
    networks: [mysql_net]
  nginx:
    image: nginx:alpine
    ports: ["8088:80"]
    volumes:
      - ./:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on: [app]
    networks: [mysql_net]
networks:
  mysql_net:
    external: true
```

`docker/php/Dockerfile` (exact):

```dockerfile
FROM php:8.3-fpm-alpine

ARG UID=1000

RUN apk add --no-cache icu-dev libzip-dev \
    && docker-php-ext-install pdo_mysql bcmath intl zip opcache

# Rebuild www-data to match the host user, so bind-mount writes stay host-owned
# (fixes storage/ + bootstrap/cache/ permission 500s with the ./ bind mount).
RUN apk add --no-cache shadow \
    && groupmod -g ${UID} www-data \
    && usermod -u ${UID} -g ${UID} www-data

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_HOME=/tmp/composer

COPY php.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html
```

`docker/php/php.ini` (upload limits aligned with the 2MB avatar validation; nginx allows 8m so oversize reaches Laravel validation, not a raw 413):

```ini
memory_limit = 256M
upload_max_filesize = 8M
post_max_size = 10M
```

`docker/nginx/default.conf` (exact):

```nginx
server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.php;
    client_max_body_size 8m;

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
}
```

`.dockerignore`: `vendor`, `node_modules`, `.git`, `storage/logs/*.log`.

- [ ] **Step 1: Write the 5 files above.**
- [ ] **Step 2: Build and boot, then generate the app key in the container**

```bash
docker compose up -d --build
docker compose exec -u www-data app php artisan key:generate
```

Expected: both containers up; key written to `.env`. If `apk add` fails, check package names with `docker run --rm php:8.3-fpm-alpine apk search <name>`.

- [ ] **Step 3: Verify the app answers through nginx**

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8088/
```

Expected: `200` (Laravel welcome page).

- [ ] **Step 4: Verify storage is writable by the container (Review Focus: no 500s)**

```bash
docker compose exec -u www-data app touch storage/app/.write-test && rm storage/app/.write-test && echo OK
```

Expected: `OK`. If this fails, the host uid is not 1000 — rebuild with `--build-arg UID=$(id -u)` (or `docker compose build app` with `args: UID: <uid>` set).

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: docker nginx php-fpm alpine with uid-matched pool user"
```

### Task 3: Install Fortify and Spatie (in-container)

**Files:**
- Modify: `composer.json`, `app/Providers/AppServiceProvider.php`, `app/Models/User.php`, `bootstrap/app.php`, `config/fortify.php`, `.env` (unchanged from Task 1)
- Create: `database/migrations/xxxx_add_avatar_path_to_users_table.php`

**Interfaces:**
- Consumes: Task 1 scaffold + Task 2 containers.
- Produces: `App\Models\User` has `HasRoles` trait and `avatar_path` fillable + DB column; `Gate::before` lives in `AppServiceProvider::boot()` (Laravel 12 has NO default `AuthServiceProvider` — do not create one); Spatie middleware aliases registered in `bootstrap/app.php` (v6 does NOT auto-register them on Laravel 11+).

- [ ] **Step 1: Require packages and publish configs (in-container)**

```bash
docker compose exec -u www-data app composer require laravel/fortify spatie/laravel-permission
docker compose exec -u www-data app php artisan vendor:publish --provider="Laravel\Fortify\FortifyServiceProvider"
docker compose exec -u www-data app php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

- [ ] **Step 2: Add `HasRoles` to User model**

In `app/Models/User.php`: add `use Spatie\Permission\Traits\HasRoles;` and `use HasRoles;` inside the class, alongside the existing `HasFactory`/`Notifiable` traits. Fillable: add `avatar_path` (used by Task 9).

- [ ] **Step 3: `Gate::before` in `app/Providers/AppServiceProvider.php` `boot()`** (this provider already exists in the scaffold):

```php
use Illuminate\Support\Facades\Gate;

Gate::before(fn ($user, $ability) => $user->hasRole('super-admin') ? true : null);
```

- [ ] **Step 4: Register Spatie middleware aliases in `bootstrap/app.php`** — unconditional, v6 does not auto-register on Laravel 11+:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);
})
```

- [ ] **Step 5: `avatar_path` migration**

```bash
docker compose exec -u www-data app php artisan make:migration add_avatar_path_to_users_table --table=users
```

Migration body: `$table->string('avatar_path')->nullable();`.

- [ ] **Step 6: Strip Fortify features and set post-login home**

In `config/fortify.php`: set `'home' => '/admin'`; reduce `features` to `[Features::ResetPasswords::class]` (no registration, no email verification). No `FortifyServiceProvider` is created — view registration happens in Task 7 via `AppServiceProvider`.

- [ ] **Step 7: Migrate and verify (DB is reached via `mysql_net`, from inside the container)**

```bash
docker compose exec -u www-data app php artisan migrate
docker compose exec -u www-data app php artisan migrate:status | head -5
```

Expected: `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions` tables created; `add_avatar_path_to_users_table` listed as Ran.

- [ ] **Step 8: Commit**

```bash
git add -A && git commit -m "feat: install fortify and spatie permission"
```

### Task 4: Permissions, roles, users seeders

**Files:**
- Create: `database/seeders/PermissionSeeder.php`, `database/seeders/RoleSeeder.php`, `database/seeders/UserSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

**Interfaces:**
- Consumes: Spatie models `Spatie\Permission\Models\Role`, `Spatie\Permission\Models\Permission`.
- Produces: the 9 permissions from Global Constraints; roles `super-admin`, `user` (both with NO synced permissions — super admin passes via `Gate::before`, role `user` needs zero permissions to see the dashboard); users `superadmin@example.com` / `user@example.com`, both password `password`.

- [ ] **Step 1: Write `PermissionSeeder`** — loop the 9 permission names, `Permission::findOrCreate($name, 'web')`.
- [ ] **Step 2: Write `RoleSeeder`** — `Role::findOrCreate('super-admin', 'web')`, `Role::findOrCreate('user', 'web')`; do NOT sync permissions to either role.
- [ ] **Step 3: Write `UserSeeder`** — `User::updateOrCreate(['email' => ...], [...])` + `->assignRole(...)`, `Hash::make('password')` for both users:
  - `superadmin@example.com`, name `Super Admin`, role `super-admin`
  - `user@example.com`, name `Regular User`, role `user`
- [ ] **Step 4: Call all three from `DatabaseSeeder`** in order: permissions → roles → users.
- [ ] **Step 5: Verify** (note fully-qualified class names — bare `User::` fails in `--execute`)

```bash
docker compose exec -u www-data app php artisan migrate:fresh --seed
docker compose exec -u www-data app php artisan tinker --execute="echo \Spatie\Permission\Models\Role::count() . ' ' . \Spatie\Permission\Models\Permission::count() . ' ' . (\App\Models\User::whereEmail('superadmin@example.com')->first()->hasRole('super-admin') ? 'yes' : 'no');"
```

Expected output: `2 9 yes`.

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat: permission role user seeders"
```

### Task 5: Metronic assets into public/

**Files:**
- Create: `public/assets/{css,js,plugins,media}/` (copied)

**Interfaces:**
- Produces: asset URLs used by the Blade layout (Task 7): `asset('assets/css/style.bundle.css')`, `asset('assets/plugins/global/plugins.bundle.css')`, `asset('assets/plugins/custom/datatables/datatables.bundle.css')`, and the 3 matching `.js` files (`style.bundle.js`, `plugins.bundle.js`, `datatables.bundle.js`). jQuery and ApexCharts ship INSIDE `plugins.bundle.js`.

- [ ] **Step 1: Copy asset dirs (NOT the HTML). `media` stays INSIDE `assets/` — Metronic markup references `assets/media/...`**

```bash
cp -r /mnt/share/template/demo1/dist/assets public/
```

(No favicon ships in the Metronic dist — verified. Do not add one; `<head-meta>` must not reference `favicon.ico`.)

Verify sizes: `du -sh public/assets` (~50–100MB is normal for Metronic; it is committed on purpose so cloners never need the Metronic source).

- [ ] **Step 2: Verify files exist**

Run: `ls public/assets/css/style.bundle.css public/assets/js/style.bundle.js public/assets/plugins/global/plugins.bundle.js public/assets/media`
Expected: all 4 paths listed.

- [ ] **Step 3: Commit**

```bash
git add -A && git commit -m "feat: copy metronic dist assets"
```

### Task 6: Metronic documentation study (read BEFORE writing any view — produces `metronic-notes.md`)

**Files:**
- Create: `docs/superpowers/metronic-notes.md`

**Interfaces:**
- Consumes: Metronic dist at `/mnt/share/template/demo1/dist` (documentation + reference pages).
- Produces: `metronic-notes.md` — the single source of truth for Tasks 7–9. A view is only "correct" if its markup was derived from the reference page recorded there.

Doc pages are 170–660KB HTML. Read them with targeted greps/line ranges (class names, `<section>` markup, `<script>` blocks) — NEVER whole-file.

- [ ] **Step 1: Read the fundamentals**
  - `dist/documentation/getting-started.html` — required CSS/JS order (`plugins.bundle` → `style.bundle` → page scripts), body/root classes, `KTUtil`/`KTApp` basics.
  - `dist/documentation/file-structure.html` — what each dist folder is for.
- [ ] **Step 2: Study one reference page per planned view and record it in `metronic-notes.md`:**

| Planned view | Metronic reference |
|---|---|
| Admin layout (sidebar/topbar/footer) | `dist/index2.html` (full page shell) + `dist/layout/` partials |
| Dashboard | `dist/index2.html` — stat/widget cards only |
| Users + Roles + Permissions lists | `dist/apps/user-management/users/list.html` |
| Users create/edit form | `dist/documentation/base/forms/controls.html` (+ `base/forms/validation` if present) |
| Roles edit (permission checkboxes) | `dist/documentation/base/forms/controls.html` checkbox groups |
| Profile | `dist/pages/profile/overview.html` (simplify to 3 sections) + `dist/documentation/forms/image-input.html` for the avatar UI |
| Login / forgot / reset | `dist/authentication/` sign-in + reset pages |

- [ ] **Step 3: Record the patterns in `metronic-notes.md`** — for each entry above: (a) exact source path, (b) the outer container/card classes to copy, (c) any required `data-kt-*` attributes or inline init scripts, (d) gotchas (e.g. demo JS that must NOT be copied).
  Must-record specifics:
  - Table page skeleton from `list.html`: toolbar (search input + Add button), `card` + `table` classes, `<tbody>` row markup — minus the demo's fake delete JS.
  - DataTables client-side init, adapted from `dist/assets/js/custom/apps/user-management/users/list/table.js`: `$(document.getElementById('kt_table_users')).DataTable({info:!1, order:[], pageLength:10, lengthChange:!1, ...})` wrapped in `KTUtil.onDOMContentLoaded(...)`. jQuery comes from `plugins.bundle.js`; `datatables.bundle.js` must be loaded after it.
  - Form skeleton: label/input/error-slot structure from `base/forms/controls.html` (Laravel validation errors render into the form's error slots — note where `@error` blocks go).
  - Layout script order: `plugins.bundle.js` → `datatables.bundle.js` (list pages only) → `style.bundle.js` → `@stack('scripts')`.
  - CONFIRMED FACTS to keep (already verified on this dist): no select2 bundled → roles multi-select is plain `<select multiple>`; jQuery + ApexCharts are inside `plugins.bundle.js`; dashboard charts are optional and only if they init without console errors.
- [ ] **Step 4: Verify** — `metronic-notes.md` exists and covers all 7 rows of the table above plus the 5 must-record specifics. If a reference page turns out to be missing on disk, pick the closest Metronic page and note the substitution — never fall back to invented markup.
- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "docs: metronic study notes and view reference map"
```

### Task 7: Blade layout + partials + auth views + redirects

**Files:**
- Create: `resources/views/layouts/admin.blade.php`
- Create: `resources/views/partials/head-meta.blade.php`, `partials/sidebar.blade.php`, `partials/topbar.blade.php`, `partials/footer-scripts.blade.php`
- Create: `resources/views/auth/login.blade.php`, `resources/views/auth/forgot-password.blade.php`, `resources/views/auth/reset-password.blade.php`
- Modify: `app/Providers/AppServiceProvider.php`, `routes/web.php`, `bootstrap/app.php`

**Interfaces:**
- Consumes: Task 5 asset paths, Task 6 `metronic-notes.md` (layout reference: `dist/index2.html` page shell + `dist/layout/` partials; auth reference: `dist/authentication/`). Extract the `<body>` markup per the notes; replace static hrefs with `route()` calls, asset hrefs with `asset()` calls.
- Produces: layout used by all admin views via `@extends('layouts.admin')`; sections `@section('page-title')`, `@section('content')`, `@stack('scripts')` slot in `footer-scripts`. Sidebar contains exactly: Dashboard, User Management (Users, Roles, Permissions), and Profile — permission-only items wrapped in `@can('users.view')` / `@can('roles.view')` / `@can('permissions.view')`.

Form field names are FIXED by Fortify — do not rename:
- login: `email`, `password`, `remember` (`POST route('login')`, `@csrf`)
- forgot-password: `email` (`POST route('password.email')`)
- reset-password: `token`, `email`, `password`, `password_confirmation` (`POST route('password.update')`)
- logout (topbar dropdown): a `<form method="POST" action="{{ route('logout') }}">@csrf</form>` submitted by the Metronic logout link
- all forms include `@csrf` — without it every POST returns 419

- [ ] **Step 1: Extract markup** into the partial files per `metronic-notes.md` (script order recorded there: `plugins.bundle.js` → page-plugin bundles → `style.bundle.js` → `@stack('scripts')`).
- [ ] **Step 2: Register auth views in `AppServiceProvider::boot()`** (no separate Fortify provider):

```php
use Laravel\Fortify\Fortify;

Fortify::loginView(fn () => view('auth.login'));
Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
Fortify::resetPasswordView(fn ($request) => view('auth.reset-password', ['request' => $request]));
```

- [ ] **Step 3: Post-login redirects.** In `bootstrap/app.php` (same `withMiddleware` block as Task 3) add `$middleware->redirectUsersTo('/admin');`. In `routes/web.php` replace the default `/` welcome route with `Route::redirect('/', '/admin');`.

- [ ] **Step 4: Verify rendering and registration lockout**

```bash
curl -s http://localhost:8088/login | grep style.bundle.css   # match → assets wired
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8088/register   # 404
```

- [ ] **Step 5: Verify a real login works (CSRF-aware helper — reuse verbatim in Tasks 8–10)**

```bash
BASE=http://localhost:8088
CSRF=$(curl -s -c jar.txt $BASE/login | grep -oP 'name="_token" value="\K[^"]+')
curl -s -b jar.txt -c jar.txt -X POST $BASE/login \
  -d "_token=$CSRF&email=superadmin@example.com&password=password" \
  -o /dev/null -w '%{http_code}\n'
```

Expected: `302` (redirect to `/admin`); `curl -s -b jar.txt -o /dev/null -w '%{http_code}\n' $BASE/admin` returns `200`.

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat: metronic layout auth views and redirects"
```

### Task 8: Admin panel — dashboard, Users, Roles, Permissions (Metronic-native UI + permission gating)

**Files:**
- Modify: `routes/web.php`
- Create: `app/Http/Controllers/Admin/DashboardController.php`, `UserController.php`, `RoleController.php`, `PermissionController.php`
- Create: `app/Http/Requests/StoreUserRequest.php`, `UpdateUserRequest.php`
- Create: views `resources/views/admin/dashboard.blade.php`, `admin/users/index.blade.php`, `admin/users/create.blade.php`, `admin/users/edit.blade.php`, `admin/roles/index.blade.php`, `admin/roles/create.blade.php`, `admin/roles/edit.blade.php`, `admin/permissions/index.blade.php`

**Interfaces:**
- Consumes: Task 7 layout + login helper, Task 6 `metronic-notes.md` (every view maps to its recorded reference page), Task 4 permissions/users, Spatie middleware aliases (Task 3).
- Produces: routes (exact pattern; profile routes are placeholders filled in Task 9):

```php
Route::redirect('/', '/admin');

Route::middleware(['auth'])->group(function () {
    Route::get('/admin', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Task 9: profile routes here (auth only, no permission middleware)

    Route::middleware('permission:users.view')->resource('admin/users', UserController::class)->except(['show']);
    Route::middleware('permission:roles.view')->resource('admin/roles', RoleController::class)->except(['show']);
    Route::middleware('permission:permissions.view')->get('/admin/permissions', [PermissionController::class, 'index'])->name('admin.permissions.index');
});
```

Gating rule: route middleware carries the `.view` check; EACH mutating controller action calls `$this->authorize()` with its own permission (Gate abilities come from Spatie automatically):

- `UserController`: `store` → `$this->authorize('users.create')`, `edit`/`update` → `users.edit`, `destroy` → `users.delete`. Create/edit forms: name, email, password (required on create, optional+nullable on edit), roles multi-select saved with `syncRoles` (covered by `users.edit`, no separate `roles.assign`).
- `RoleController`: `store` → `roles.create`, `edit`/`update` → `roles.edit` + edit form has checkbox groups of all permissions saved via `$role->syncPermissions($request->input('permissions', []))`, `destroy` → `roles.delete`.
- `PermissionController`: read-only index table.

UI rules (from `metronic-notes.md`):
- **List pages (Users, Roles, Permissions)**: copy the table page skeleton from `apps/user-management/users/list.html` — toolbar with search input + "Add" button, `card` + `table` classes, row markup. Rows rendered by Blade `@foreach`; initialize client-side DataTables via the `table.js` pattern recorded in the notes (`KTUtil.onDOMContentLoaded` + `$('#kt_table_...').DataTable({info:!1, order:[], pageLength:10, lengthChange:!1})`), pushed into `@push('scripts')`. `datatables.bundle.js` loads on list pages only.
- **Delete buttons**: per-row `<form method="POST" action="{{ route('admin.users.destroy', $user) }}">@csrf @method('DELETE')</form>` styled as the Metronic icon button. Do NOT copy the demo's client-side-only delete (it removes the row without calling the server).
- **Forms**: skeleton from `base/forms/controls.html` as recorded in the notes; Laravel `@error` messages render into the form's error slots; roles multi-select is a plain `<select multiple name="roles[]">` (NO select2 in this dist).
- **Dashboard**: copy 2–3 stat/widget cards from `index2.html` (user count, role count via `User::count()` / `Role::count()`). No charts unless the recorded init works without console errors.

- [ ] **Step 1: Write routes per the exact pattern above.**
- [ ] **Step 2: Dashboard view** per the notes. Auth-only, no permission check — role `user` must see it.
- [ ] **Step 3: Users CRUD + `StoreUserRequest`/`UpdateUserRequest`** (email `unique:users,email` ignoring own id on update; password `nullable|confirmed|min:8` on update).
- [ ] **Step 4: Roles CRUD** — index lists roles with permission count; edit form has permission checkbox groups.
- [ ] **Step 5: Permissions index** — read-only table (same list skeleton, no Add button).
- [ ] **Step 6: Verify super admin happy path** — log in with the Task 7 helper, then check every page:

```bash
curl -s -b jar.txt -o /dev/null -w '%{http_code}\n' http://localhost:8088/admin
curl -s -b jar.txt -o /dev/null -w '%{http_code}\n' http://localhost:8088/admin/users
curl -s -b jar.txt -o /dev/null -w '%{http_code}\n' http://localhost:8088/admin/roles
curl -s -b jar.txt -o /dev/null -w '%{http_code}\n' http://localhost:8088/admin/permissions
```

Expected: `200` for all four. Also create a user with role `user`, and create/edit a role with permission checkboxes via the browser.

- [ ] **Step 7: Verify `user` role is locked out** — repeat the Task 7 helper with `jar2.txt` and `email=user@example.com`, then:

```bash
curl -s -b jar2.txt -o /dev/null -w '%{http_code}\n' http://localhost:8088/admin          # 200
curl -s -b jar2.txt -o /dev/null -w '%{http_code}\n' http://localhost:8088/admin/users    # 403
curl -s -b jar2.txt http://localhost:8088/admin | grep -c 'admin/users'                   # 0 (sidebar hidden)
```

Expected: `200`, `403`, `0`. A 419 here means the CSRF token was not sent — use the helper verbatim.

- [ ] **Step 8: Commit**

```bash
git add -A && git commit -m "feat: metronic admin panel users roles permissions with gating"
```

### Task 9: Profile page (photo, data, password)

**Files:**
- Modify: `routes/web.php` (fill the placeholder comment in the Task 8 auth group — NO permission middleware)
- Create: `app/Http/Controllers/ProfileController.php`, `app/Http/Requests/UpdateProfileRequest.php`, `app/Http/Requests/UpdatePasswordRequest.php`
- Create: `resources/views/admin/profile.blade.php`

**Interfaces:**
- Consumes: `avatar_path` column + fillable (Task 3), Task 7 layout, Task 6 notes (reference: `dist/pages/profile/overview.html` simplified to ONE page with 3 sections — data, photo, password; avatar UI optional pattern from `dist/documentation/forms/image-input.html`).
- Produces: routes —
  - `GET /profile` → view with current data
  - `POST /profile` → update name/email (+ optional photo: `image|mimes:jpg,jpeg,png|max:2048`, stored on the `public` disk under `avatars/`; on replace, delete the old file via `Storage::disk('public')->delete()` BEFORE storing the new one; update `avatar_path`)
  - `POST /profile/password` → `current_password` validated with `Hash::check` (use `current_password` rule or manual check), then update `password` with `Hash::make`

- [ ] **Step 1: Implement controller + requests** (validation rules as specified; email `unique:users,email` ignoring own id). Photo input field name: `photo`.
- [ ] **Step 2: Create the storage symlink INSIDE the container (host-side symlink uses host paths and breaks in the container)**

```bash
docker compose exec -u www-data app php artisan storage:link
```

Also set `FILESYSTEM_DISK=public` in `.env` (and `.env.example` in Task 11).

- [ ] **Step 3: Verify photo upload** — log in as super admin with the Task 7 helper, then:

```bash
CSRF=$(curl -s -b jar.txt -c jar.txt http://localhost:8088/profile | grep -oP 'name="_token" value="\K[^"]+')
curl -s -b jar.txt -X POST http://localhost:8088/profile \
  -F "_token=$CSRF" -F "name=Super Admin" -F "email=superadmin@example.com" \
  -F "photo=@/tmp/test.png" -o /dev/null -w '%{http_code}\n'
docker compose exec -u www-data app ls storage/app/public/avatars
```

Expected: `302` redirect, file present in `avatars/`. Then re-upload with a `.txt` file → redirect back with validation error and NO new file stored. Then upload a second PNG → the FIRST file is gone from `avatars/` (old-file delete works).

- [ ] **Step 4: Verify password change** — POST wrong `current_password` → redirect back with error; POST correct one → login with the new password works (re-run the Task 7 helper with the new password), then change it back.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: profile page with photo data password"
```

### Task 10: README + fresh-install smoke test (full clone simulation)

**Files:**
- Create: `README.md`

**Interfaces:**
- Consumes: everything above.
- Produces: documented first-run path for cloners (no host PHP needed, no Metronic source needed).

- [ ] **Step 1: Write README** with these sections:
  - **Preconditions:** Docker + compose; an existing MySQL on network `mysql_net` (db `app`, user `app`, password `app` — DEV-ONLY credentials); host uid `1000` (else build with `--build-arg UID=<uid>`).
  - **Setup (exact order, all in-container):**

```bash
cp .env.example .env            # adjust DB_* if your MySQL differs
docker compose up -d --build
docker compose exec -u www-data app composer install
docker compose exec -u www-data app php artisan key:generate
docker compose exec -u www-data app php artisan migrate:fresh --seed
docker compose exec -u www-data app php artisan storage:link
```

  - **Login:** `http://localhost:8088/login` — `superadmin@example.com` / `password` (super admin, sees everything) and `user@example.com` / `password` (sees dashboard + profile only).
  - **Forgot password:** mail goes to `storage/logs/laravel.log` (`MAIL_MAILER=log`) — copy the reset URL from that file into the browser.
- [ ] **Step 2: Run the full sequence from a clean state** — `docker compose down`, remove `.env`, remove `storage/app/public/avatars/*`, re-run every README step verbatim.
- [ ] **Step 3: Verify both personas end-to-end** — super admin sees all menus and `/admin`, `/admin/users`, `/admin/roles`, `/admin/permissions` all return 200; `user@example.com` sees dashboard + profile only and gets 403 on `/admin/users`. Use the Task 7 login helper with fresh cookie jars.
- [ ] **Step 4: Commit**

```bash
git add -A && git commit -m "docs: readme setup guide"
```

### Task 11: Cleanup and final housekeeping

**Files:**
- Modify: `.gitignore` (add `jar.txt`, `jar2.txt`, ignore contents of `storage/app/public/avatars` and the `public/storage` symlink), `.env.example` (DB values + `FILESYSTEM_DISK=public` + `MAIL_MAILER=log`)
- Delete: `jar.txt`, `jar2.txt`, `/tmp/test.png`, any throwaway scripts

- [ ] **Step 1: Remove scaffolds and temp files.**
- [ ] **Step 2: Verify clean tree** — `git status` shows nothing unexpected; `grep -rn "TODO" app/ resources/` returns nothing.
- [ ] **Step 3: Commit**

```bash
git add -A && git commit -m "chore: cleanup and env example"
```
