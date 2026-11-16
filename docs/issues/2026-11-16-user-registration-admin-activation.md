# User Registration with Admin Activation — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Self-service registration that creates a *pending* account; super-admins get a database notification and can Activate / Reject / Deactivate from the Users page; only `active` accounts can log in.

**Architecture:** Fortify's registration feature handles the signup route; a `status` column plus `activated_at`/`activated_by` on `users` tracks the approval lifecycle. Login is gated by a custom `Fortify::authenticateUsing()` closure and an `EnsureUserIsActive` middleware. Admin actions are three extra UserController methods behind a new `users.activate` permission.

**Tech Stack:** Laravel 12, Fortify (already installed), Spatie Permission (already installed), Metronic dist views (no build step), database notifications.

**Spec:** Decisions confirmed by the product owner in chat on 2026-11-16 (recorded in Global Constraints below). Template conventions: `docs/issues/superpowers/metronic-notes.md`, `docs/issues/superpowers/plans/2026-11-14-laravel-admin-template.md`.

## Global Constraints

- No test suite in this template. Every task verifies by running commands (`docker compose exec app php artisan …`, `curl`). Never create PHPUnit tests.
- Every view MUST be copied/adapted from a Metronic dist reference page in `/mnt/share/template/demo1/dist`; never invent markup or CSS classes. Do NOT use plugins the dist does not bundle.
- All `curl -X POST` checks MUST send the CSRF token — use the "curl helpers" appendix at the bottom of this file verbatim. A POST returning `419` means the token was not sent — re-read the helper; never "fix" it by disabling CSRF.
- A `rejected` or `deactivated` email address cannot be used to register again — the `unique:users` rule blocks it. Intentional (prevents re-register bypass); admins use Activate instead, which works from any status.
- No Livewire, Inertia, Tailwind, API routes, or business modules.
- Email sending is NOT configured in this template. The "notify admin" decision is implemented as Laravel **database notifications** (the sidebar badge shows the pending count on every page load — no JS polling; see Task 4's scope note), never `Mail::` / `Notification::send` mail channel.
- Confirmed product decisions (verbatim):
  - New accounts get role `user` at registration.
  - Lifecycle column: `status` (`pending` | `active` | `rejected` | `deactivated`) + `activated_at` + `activated_by` for audit.
  - Login before activation is BLOCKED with a per-status message; not a "waiting page".
  - Super-admins are notified of new registrations via database notification + sidebar badge.
  - Admin actions: Activate (any → `active`), Reject (`pending` → `rejected`), Deactivate (`active` → `deactivated`).
  - UI language: keep existing English admin copy style ("User activated." etc.).

## Review Focus

- A pending user submits valid credentials → login must fail with "waiting for admin activation", NOT a generic "bad credentials" and NOT a 500. → Task 3 Step 2 test D.
- A `deactivated` user with a live session must lose access on their next request, not stay logged in. → Task 3 Step 5 test G.
- Admin-created users (UserController@store) must be `active` immediately — registration columns must not break the existing create flow. → Task 1 Step 4 check + Task 5 Step 5 test H.
- The Users list must show pending users so admins can find them; a pending user appearing "normal" with no status column fails the feature. → Task 5 Step 5 test E.
- `register` route must be reachable unauthenticated (guest middleware), and `/register` must 404-free render even while logged-out badge logic runs. → Task 2 Step 6 + Task 6 Step 4.

---

### Task 1: Status lifecycle on `users`

**Files:**
- Create: `database/migrations/2026_11_16_000000_add_activation_fields_to_users_table.php`
- Modify: `app/Models/User.php`
- Modify: `app/Http/Controllers/Admin/UserController.php` (`store`, `update`)

**Interfaces:**
- Produces: `User::$status: string` ('pending'|'active'|'rejected'|'deactivated'), `User::activated_at: ?datetime` (cast), `User::activated_by: ?int`, all in `$fillable`; `User::isActive(): bool`. Later tasks rely on these exact names.
- Consumes: existing `UserController::store/update` (seen above), `App\Models\User` with `HasRoles`, `avatar_path` fillable.

- [ ] **Step 1: Create the migration** — add after `avatar_path` context (use `Schema::table('users', ...)`):

```php
$table->string('status')->default('pending')->index();
$table->timestamp('activated_at')->nullable();
$table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
```
In `down()`, drop the foreign key first, then the columns: `$table->dropForeign(['activated_by']); $table->dropColumn(['status', 'activated_at', 'activated_by']);`

- [ ] **Step 2: Update `app/Models/User.php`** — add `'status'`, `'activated_at'`, `'activated_by'` to `$fillable`; add `'activated_at' => 'datetime'` to `$casts`; add method:

```php
public function isActive(): bool
{
    return $this->status === 'active';
}
```

- [ ] **Step 3: Make admin-created users active** — in `UserController::store` add to the `User::create([...])` array: `'status' => 'active', 'activated_at' => now(), 'activated_by' => Auth::id()`. In `update`, do NOT touch status (only the dedicated actions in Task 5 change it).

- [ ] **Step 4: Run and verify**

```bash
docker compose exec -u www-data app php artisan migrate
docker compose exec app php artisan tinker --execute="print(App\Models\User::count()); print(PHP_EOL); print(App\Models\User::where('status','active')->count());"
```

Expected: migration runs; seeded super admin shows status count ≥ 1 (seeder users must be `active` — if the seeder doesn't set status, it defaults to `pending`: FIX by adding `'status' => 'active'` to both `User::updateOrCreate` arrays in `database/seeders/UserSeeder.php` and re-running `migrate:fresh --seed`).

- [ ] **Step 5: Verify existing login still works** (curl helpers appendix, login as seeded super admin → expect redirect to `/admin`).

- [ ] **Step 6: Commit** — `git add -A && git commit -m "feat: add activation status lifecycle to users"`

### Task 2: Registration route + view

**Files:**
- Modify: `config/fortify.php` (features array)
- Create: `app/Actions/Fortify/CreateNewUser.php`
- Create: `resources/views/auth/register.blade.php`
- Modify: `resources/views/auth/login.blade.php` (add "Register" link)
- Modify: `app/Providers/AppServiceProvider.php` if it registers Fortify views there; otherwise `app/Providers/FortifyServiceProvider.php` — wherever `Fortify::loginView(...)` currently lives (it is in FortifyServiceProvider at time of writing).

**Interfaces:**
- Produces: `POST /register` creates a `User` with `status='pending'`, assigns role `user`, fires `App\Notifications\NewUserRegistration` (Task 4), redirects to `/login`. `Fortify::registerView()` returns `view('auth.register')`.
- Consumes: Task 1 fields; Metronic reference `dist/authentication/layouts/basic/sign-up.html`.

- [ ] **Step 1: Enable the feature** — `config/fortify.php`: `'features' => [Features::registration(),]` (keep the "reset via admin" comment above it).

- [ ] **Step 2: Create `CreateNewUser`** implementing `Laravel\Fortify\Contracts\CreatesNewUsers` — validation: `name` required|string|max:255, `email` required|email|unique:users, `password` current Laravel password rules array (copy the style of the deleted `ResetUserPassword` from git history: `['required','string','confirmed', Password::min(8)…]` — use `Password::defaults()` if the app has none configured, else min 8). Create user with `status => 'pending'`, then `$user->assignRole('user')`, then `$user->notify(new NewUserRegistration())` (class arrives in Task 4 — reference it now, and create a stub `App\Notifications\NewUserRegistration` extending `Notification` with `via() => ['database']` and empty `toArray()` if you reach this step before Task 4; Task 4 replaces the body). Return `$user`.

- [ ] **Step 3: Register the view** — in the provider that holds `Fortify::loginView`, add:

```php
Fortify::registerView(fn () => view('auth.register'));
```

- [ ] **Step 4: Build `resources/views/auth/register.blade.php`** — copy `/mnt/share/template/demo1/dist/authentication/layouts/basic/sign-up.html`, adapt exactly the way `login.blade.php` was adapted from `sign-in.html` (same `@include('partials.head-meta')`, `<title>… — Register</title>`, `partials.footer-scripts`). Form: `method="POST" action="{{ route('register') }}"`, fields `name`, `email`, `password`, `password_confirmation`, each with the `@error` pattern from `login.blade.php`; keep Metronic classes (`form-control-lg form-control-solid` etc.). Record the adaptation in `docs/issues/superpowers/metronic-notes.md` §7 table.

- [ ] **Step 5: Add login-page link** — in `login.blade.php`, under the submit button: `<div class="text-center fs-6">No account? <a href="{{ route('register') }}" class="link-primary fw-bolder">Register</a></div>`.

- [ ] **Step 6: Verify**

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8089/register          # expect 200
curl -s http://localhost:8089/register | grep -c 'route('                        # >0 means blade compiled
```

Then register through the browser or CSRF-aware curl (curl helpers appendix): expect redirect to `/login`, and in DB the new user has `status='pending'` and role `user`. Logging in as that user at this point may succeed — acceptable only because Task 3 lands next; do not "fix" here.

- [ ] **Step 7: Commit** — `git commit -m "feat: self-service registration (pending accounts)"`

### Task 3: Login gating

**Files:**
- Modify: `app/Providers/FortifyServiceProvider.php`
- Create: `app/Http/Middleware/EnsureUserIsActive.php`
- Modify: `bootstrap/app.php` (alias registration)

**Interfaces:**
- Produces: `EnsureUserIsActive` middleware, alias name `'active'`; login rejection messages exactly: `'Your account is waiting for admin activation.'` (pending), `'Your account has been rejected.'` (rejected), `'Your account has been deactivated.'` (deactivated).
- Consumes: Task 1 `User::isActive()`.

- [ ] **Step 1: `FortifyServiceProvider::boot()`** — add before the login view line. The closure must BOTH check credentials AND status: return `$user` only when `Hash::check(...)` passes AND `$user->isActive()`; otherwise throw `ValidationException::withMessages([Fortify::username() => <message>])` using the exact messages above keyed by `$user->status` (wrong password / unknown email returns `null` for Fortify's generic error — do NOT throw in that case). Imports needed: `App\Models\User`, `Illuminate\Support\Facades\Hash`, `Illuminate\Validation\ValidationException`.

```php
Fortify::authenticateUsing(function (Request $request) {
    $user = User::where('email', $request->input('email'))->first();

    if (! $user || ! Hash::check($request->input('password'), $user->password)) {
        return null; // Fortify shows its generic "invalid credentials" error
    }

    if (! $user->isActive()) {
        throw ValidationException::withMessages([
            Fortify::username() => match ($user->status) {
                'pending' => __('Your account is waiting for admin activation.'),
                'rejected' => __('Your account has been rejected.'),
                'deactivated' => __('Your account has been deactivated.'),
                default => __('Your account is not active.'),
            },
        ]);
    }

    return $user;
});
```

- [ ] **Step 2: Verify blocked logins** (curl helpers appendix; each expect redirect back to `/login` with the message flashed — check `session('errors')` text or the blade `@error` output):

- A: wrong password → generic invalid-credentials message (Fortify default).
- B: `pending` user, correct password → "waiting for admin activation".
- B2: `pending` user + WRONG password → generic invalid-credentials message (the password check runs before the status check; account status must not leak).
- C: `rejected` user → "rejected".
- D: unknown email → generic invalid-credentials message (status leak check).

- [ ] **Step 3: `EnsureUserIsActive` middleware** — `handle($request, $next)`: if `Auth::check()` and `! Auth::user()->isActive()`, `Auth::guard('web')->logout(); $request->session()->invalidate(); $request->session()->regenerateToken();` then redirect to `route('login')` with the per-status message via `->withErrors(['email' => …])`. Register in `bootstrap/app.php`: `->withMiddleware(fn (Middleware $middleware) => $middleware->alias(['active' => EnsureUserIsActive::class]))` (append to the existing `withMiddleware` closure — do not create a second one).

- [ ] **Step 4: Apply the alias** to the auth route group in `routes/web.php`: `Route::middleware(['auth', 'active'])->group(...)`.

- [ ] **Step 5: Verify**

- E: log in as an `active` seeded admin → `/admin` renders 200.
- F: manually set a user to `deactivated` (`php artisan tinker --execute="App\Models\User::where('email','x@y.z')->update(['status'=>'deactivated'])"`), keep their session cookie, GET `/admin` → redirected to `/login` with "deactivated" message.
- G: rejected user session → same behavior.

- [ ] **Step 6: Commit** — `git commit -m "feat: block login for non-active accounts, enforce status mid-session"`

### Task 4: Super-admin notification

**Files:**
- Create: `app/Notifications/NewUserRegistration.php`

**Interfaces:**
- Produces: `NewUserRegistration` — `via(): ['database']`, `toArray(): ['name' => …, 'email' => …, 'user_id' => …]`. Task 2's `CreateNewUser` already calls `$user->notify(...)`; if a stub was created there, replace it with the full class.
- Consumes: Laravel database notifications (`notifications` table exists by default in Laravel 11+ `0001_01_01_000000_create_users_table`-adjacent migration — verify with `php artisan migrate:status`; if missing, `php artisan notifications:table && php artisan migrate`).

**Scope note:** the notification rows are the persistent record of registrations. The sidebar badge (Task 5 Step 4) counts `pending` users directly, so it stays correct after activation; a notifications dropdown (listing + mark-as-read) is deliberately OUT of scope for this plan.

- [ ] **Step 1: Write the class** — implements `ShouldQueue` NOT required (database channel is sync; keep it simple). Constructor takes `User $user` (public property).

- [ ] **Step 2: Verify**

```bash
docker compose exec app php artisan tinker --execute="print(App\Models\User::whereHas('roles', fn(\$r) => \$r->where('name','super-admin'))->first()->notifications()->count());"
```

Register a fresh account via curl, re-run: count increases by 1 for EVERY super-admin user (each gets their own row).

- [ ] **Step 3: Commit** — `git commit -m "feat: notify super-admins of new registrations"`

### Task 5: Admin activation UI

**Files:**
- Modify: `app/Http/Controllers/Admin/UserController.php`
- Modify: `routes/web.php`
- Modify: `database/seeders/PermissionSeeder.php`
- Modify: `resources/views/admin/users/index.blade.php`
- Modify: `resources/views/partials/sidebar.blade.php`

**Interfaces:**
- Produces: routes `POST admin/users/{user}/activate|reject|deactivate` named `admin.users.activate|reject|deactivate`, each behind `permission:users.activate`, each `RedirectResponse` back to `admin.users.index` with `session('success')`. Controller methods: `activate(User $user)`, `reject(User $user)`, `deactivate(User $user)`.
- Consumes: Task 1 fields; existing index blade structure (Metronic `apps/user-management/users/list.html` reference, `metronic-notes.md` §3).

- [ ] **Step 1: Seeder** — add `'users.activate'` to the `PermissionSeeder` permission list.

- [ ] **Step 2: Routes + controller** — after the resource route:

```php
Route::middleware('permission:users.activate')->group(function () {
    Route::post('admin/users/{user}/activate', [UserController::class, 'activate'])->name('admin.users.activate');
    Route::post('admin/users/{user}/reject', [UserController::class, 'reject'])->name('admin.users.reject');
    Route::post('admin/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('admin.users.deactivate');
});
```

Controller behavior (each sets `activated_at`/`activated_by` only when transitioning TO `active`; `deactivate`/`reject` leave the audit columns untouched): `activate` from any status → `active`; `reject` only from `pending` (else redirect back with `session('error') = 'Only pending accounts can be rejected.'`); `deactivate` only from `active` (same error pattern: `'Only active accounts can be deactivated.'`). Guard: a super-admin cannot deactivate/reject themselves (`$user->id === Auth::id()` → error `'You cannot change your own account status.'`). Success flashes: `'User activated.'`, `'User rejected.'`, `'User deactivated.'`.

Reference implementation (the prose above is the contract; this matches the existing controller style — `RedirectResponse`, `Auth::id()`, flash keys `success`/`error`):

```php
public function activate(User $user): RedirectResponse
{
    $user->update([
        'status' => 'active',
        'activated_at' => now(),
        'activated_by' => Auth::id(),
    ]);

    return redirect()->route('admin.users.index')->with('success', 'User activated.');
}

public function reject(User $user): RedirectResponse
{
    if ($user->id === Auth::id()) {
        return redirect()->route('admin.users.index')->with('error', 'You cannot change your own account status.');
    }

    if ($user->status !== 'pending') {
        return redirect()->route('admin.users.index')->with('error', 'Only pending accounts can be rejected.');
    }

    $user->update(['status' => 'rejected']);

    return redirect()->route('admin.users.index')->with('success', 'User rejected.');
}

public function deactivate(User $user): RedirectResponse
{
    if ($user->id === Auth::id()) {
        return redirect()->route('admin.users.index')->with('error', 'You cannot change your own account status.');
    }

    if ($user->status !== 'active') {
        return redirect()->route('admin.users.index')->with('error', 'Only active accounts can be deactivated.');
    }

    $user->update(['status' => 'deactivated']);

    return redirect()->route('admin.users.index')->with('success', 'User deactivated.');
}
```

- [ ] **Step 3: Index view** — add a `Status` column between Roles and Joined Date: badge per Metronic badge classes mapping `active`→`badge-light-success`, `pending`→`badge-light-warning`, `rejected`→`badge-light-danger`, `deactivated`→`badge-light-secondary`. In the actions dropdown (the existing Edit/Delete menu), add for `@can('users.activate')`: Activate (visible when not `active`), Reject (only `pending`), Deactivate (only `active` and not self — pass `auth()->id()` comparison in blade). Each a POST form with `@csrf`, styled like the existing Delete form button.

- [ ] **Step 4: Sidebar badge** — in `sidebar.blade.php`, the Users menu-item is already wrapped in `@can('users.view')`. Right after that `@can`, compute the count with `@php` (NOT `{{ }}` — a Blade echo would print the number into the page): `@php $pending = App\Models\User::where('status', 'pending')->count(); @endphp`. Then extend the existing `<span class="menu-title">Users</span>` with:

```blade
<span class="menu-title">Users</span>
@if ($pending > 0)
    <span class="badge badge-light-danger fs-8 ms-2">{{ $pending }}</span>
@endif
```

- [ ] **Step 5: Verify**

- E: log in as super admin → Users page shows the Task 2 pending user with warning badge and Activate/Reject buttons; sidebar badge shows the pending count.
- F: Activate → success flash, user can now log in (re-run Task 3 test B with same credentials → passes).
- G: Deactivate an active non-admin user → flash, their session dies on next request (Task 3 test F).
- H: create a user via the EXISTING "Add User" form → it appears `active` immediately with no audit columns missing (Task 1 guarantee).
- I: permission check — assign a role with only `users.view` (no `users.activate`): action buttons hidden, direct `POST /admin/users/{id}/activate` → 403.

- [ ] **Step 6: Commit** — `git commit -m "feat: admin activation workflow for registrations"`

### Task 6: End-to-end verification

**Files:** none (verification only).

- [ ] **Step 1: Fresh state**

```bash
docker compose down && docker compose up -d --build
sleep 5
docker compose exec -u www-data app php artisan migrate:fresh --seed
```

Expected: no errors; seeded super admin `active`, loggable.

- [ ] **Step 2: Register a pending user via CSRF-aware curl** (log in first with the curl helpers appendix, then):

```bash
# login as seeded super admin, then:
TOKEN=$(curl -s -b /tmp/cj http://localhost:8089/register | grep -oP 'name="_token" value="\K[^"]+' | head -1)
curl -s -b /tmp/cj -c /tmp/cj -o /dev/null -w '%{http_code}\n' -X POST http://localhost:8089/register \
  -H "Referer: http://localhost:8089/register" \
  --data-urlencode "_token=$TOKEN" \
  --data-urlencode "name=Pending User" \
  --data-urlencode "email=pending@example.test" \
  --data-urlencode "password=Sup3rSecret!" \
  --data-urlencode "password_confirmation=Sup3rSecret!"
```

Expected: 302 to `/login`. DB check: `pending@example.test` has `status='pending'`, role `user`, notification row exists.

- [ ] **Step 3: Full journey** — pending login blocked (message) → admin activates from UI → pending login succeeds → lands on `/admin` with Dashboard + Profile only → admin deactivates → session dies on next request. Each step observed, not assumed. Record actual outputs in the commit message body or the task log.

- [ ] **Step 4: Regression sweep** — `/register` 200 logged-out; wrong-password login still shows generic error; existing Add-User flow creates active users; roles/permissions pages unchanged.

- [ ] **Step 5: Commit** — `git commit -m "chore: e2e verification for registration + activation"` (or note verification-only, nothing to commit).

---

## curl helpers (appendix)

Adapted from the 2026-11-14 plan Task 7 (port 8089, cookie jar `/tmp/cj`). Tasks 2, 3, 5, and 6 reuse this verbatim.

```bash
# 1) Log in as the seeded super admin (session saved to /tmp/cj):
BASE=http://localhost:8089
CSRF=$(curl -s -c /tmp/cj $BASE/login | grep -oP 'name="_token" value="\K[^"]+')
curl -s -b /tmp/cj -c /tmp/cj -X POST $BASE/login \
  -d "_token=$CSRF&email=superadmin@example.com&password=password" \
  -o /dev/null -w '%{http_code}\n'   # expect 302

# 2) For any POST while logged out (e.g. /register), fetch a fresh token from the form page first:
TOKEN=$(curl -s -b /tmp/cj $BASE/register | grep -oP 'name="_token" value="\K[^"]+' | head -1)
```

Seeded credentials: `superadmin@example.com` / `password` (`UserSeeder`). A POST returning `419` means the token was not sent — re-read this helper, never disable CSRF.
