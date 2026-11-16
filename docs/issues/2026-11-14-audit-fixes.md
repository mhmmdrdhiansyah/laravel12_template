# Audit Fixes 1–11 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tutup 11 temuan audit (lockout, duplikasi, dead code, pagination, hardening) tanpa menambah fitur baru.

**Architecture:** Perbaikan behavioral didahului test yang gagal (TDD); refactor murni dibuktikan oleh suite yang tetap hijau. Tidak ada dependency baru, tidak ada file/konvensi baru selain yang disebut.

**Tech Stack:** Laravel 12, PHP 8.3, Fortify 1.x, spatie/laravel-permission 8.x, PHPUnit 11 (sqlite `:memory:`).

**Spec:** Audit laporan sesi 2026-11-14 (temuan 1–11). Pemetaan temuan → task:

| Temuan | Task |
|---|---|
| 1. Rename role super-admin = lockout | Task 2 |
| 2. Self-demote super-admin | Task 3 |
| 3. Rate limiter + config Fortify dobel | Task 4 |
| 4. Nol test gating | Task 1 (+test di tiap task) |
| 5. Tanpa pagination, search client-side | Task 8 |
| 6. Tiga action Fortify mati | Task 6 |
| 7. Scaffolding passkey/2FA setengah jalan | Task 7 |
| 8. RoleController inline validate + phpdoc hack | Task 5 |
| 9. No-op unset password | Task 9 |
| 10. File sampah jar*.txt + temp_app | Task 10 |
| 11. Ganti password tidak logout perangkat lain | Task 11 |

## Global Constraints

- Semua perintah lewat container: `docker compose exec -u www-data app <cmd>`. Pastikan stack jalan: `docker compose up -d` (port lokal bisa 8089 karena `docker-compose.override.yml`).
- Test selalu: `docker compose exec -u www-data app php artisan test --filter=<NamaTest>`. Suite penuh: `docker compose exec -u www-data app php artisan test`.
- Test pakai sqlite `:memory:` via `phpunit.xml` — jangan menambah env DB di test.
- Seed bawaan: `superadmin@example.com` / `password` (role `super-admin`) dan `user@example.com` / `password` (role `user`).
- Konvensi commit: conventional commits bahasa Inggris (`fix:`, `refactor:`, `test:`, `chore:`), satu commit per task.
- Dilarang: menambah dependency composer, membuat class baru di luar yang ditentukan plan, mengubah style baris yang tidak disentuh.
- Daftar role protected: `['super-admin', 'user']`. Di logika PHP pakai konstanta `RoleController::PROTECTED_ROLES`; di blade boleh literal (sudah ada).

## Review Focus

Lima mode gagal yang paling mungkin menggigit, dan test mana yang menguncinya:

1. Input `name` role protected tidak terkirim saat pakai `disabled` (disabled input tidak ikut POST → error validasi). Task 2 pakai `readonly`, bukan `disabled`. Di-pin test Task 2 (`test_custom_role_rename_with_same_name_still_allowed`: update dengan nama sama tetap sukses).
2. Search berisi `%` atau `_` harus diperlakukan literal, bukan wildcard. Di-pin test Task 8 (`test_users_search_treats_percent_as_literal`).
3. Nomor halaman + query search harus bertahan bersamaan (`withQueryString`). Di-pin test Task 8 (`test_users_pagination_keeps_search_query`: link halaman 2 wajib menyertakan `?search=`).
4. `logoutOtherDevices` dipanggil SEBELUM hash password diganti; kalau terbalik, password lama divalidasi terhadap hash baru → 500. Di-pin test Task 11 (`test_wrong_current_password_is_rejected_not_500`).
5. User yang punya `roles.view` tapi bukan `roles.create` harus tetap 403 saat POST `/admin/roles` (middleware route hanya menutup `roles.view`). Di-pin test Task 5 (`test_user_with_only_roles_view_gets_403_on_create`).

---

### Task 1: Baseline gating test suite

**Files:**
- Modify: `tests/TestCase.php`
- Create: `tests/Feature/GatingTest.php`

**Interfaces:**
- Consumes: seeder `DatabaseSeeder` (user `superadmin@example.com`, `user@example.com`), route `login`, `logout`, `admin.*`.
- Produces: `tests/Feature/GatingTest.php` hijau — dipakai Task 4 dan 5 sebagai bukti refactor tidak merusak behavior.

- [ ] **Step 1: Tambah reset cache Spatie Permission di `tests/TestCase.php`**

Registrar permission punya cache statis antar test dalam satu proses; tanpa reset, test berikutnya bisa lihat role lama. Ganti isi file menjadi:

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        parent::tearDown();
    }
}
```

- [ ] **Step 2: Tulis `tests/Feature/GatingTest.php`**

Perhatikan: `->get()` adalah method test case, bukan response — jangan dirantai dari `assertOk()`. Satu request satu statement.

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GatingTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->superAdmin = User::where('email', 'superadmin@example.com')->first();
        $this->regularUser = User::where('email', 'user@example.com')->first();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_user_management(): void
    {
        $this->actingAs($this->regularUser)->get('/admin/users')->assertForbidden();
    }

    public function test_regular_user_cannot_access_role_management(): void
    {
        $this->actingAs($this->regularUser)->get('/admin/roles')->assertForbidden();
    }

    public function test_regular_user_cannot_access_permissions_list(): void
    {
        $this->actingAs($this->regularUser)->get('/admin/permissions')->assertForbidden();
    }

    public function test_regular_user_can_see_dashboard_and_profile(): void
    {
        $this->actingAs($this->regularUser)->get('/admin')->assertOk();
        $this->actingAs($this->regularUser)->get('/profile')->assertOk();
    }

    public function test_super_admin_bypasses_permission_checks(): void
    {
        $this->actingAs($this->superAdmin)->get('/admin/users')->assertOk();
        $this->actingAs($this->superAdmin)->get('/admin/roles')->assertOk();
        $this->actingAs($this->superAdmin)->get('/admin/permissions')->assertOk();
    }

    public function test_super_admin_cannot_delete_themselves(): void
    {
        // Harus super admin: user biasa ditahan middleware users.view sebelum sampai controller.
        $this->actingAs($this->superAdmin)
            ->delete("/admin/users/{$this->superAdmin->id}")
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
    }

    public function test_protected_role_cannot_be_deleted(): void
    {
        $role = Role::where('name', 'super-admin')->first();

        $this->actingAs($this->superAdmin)->delete("/admin/roles/{$role->id}")
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['name' => 'super-admin']);
    }

    public function test_login_with_valid_credentials(): void
    {
        $this->post('/login', ['email' => 'user@example.com', 'password' => 'password'])
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($this->regularUser);
    }

    public function test_logout_ends_session(): void
    {
        $this->actingAs($this->regularUser)->post('/logout');

        $this->assertGuest();
    }

    public function test_forgot_password_sends_reset_link(): void
    {
        Mail::fake();

        $this->post('/forgot-password', ['email' => 'user@example.com'])
            ->assertSessionHas('status');

        Mail::assertSent(ResetPassword::class);
    }

    public function test_reset_password_page_renders(): void
    {
        $this->get('/reset-password/any-token?email=user@example.com')->assertOk();
    }
}
```

- [ ] **Step 3: Jalankan, pastikan hijau**

Run: `docker compose exec -u www-data app php artisan test --filter=GatingTest`
Expected: PASS, 12 test. Semua behavior ini sudah ada di kode — kalau ada yang gagal, berhenti dan laporkan; jangan ubah kode produksi di task ini.

- [ ] **Step 4: Commit**

```bash
git add tests/TestCase.php tests/Feature/GatingTest.php
git commit -m "test: baseline gating and auth coverage"
```

---

### Task 2: Blokir rename role protected

**Files:**
- Modify: `app/Http/Controllers/Admin/RoleController.php`
- Modify: `resources/views/admin/roles/edit.blade.php`
- Test: `tests/Feature/RoleProtectionTest.php`

**Interfaces:**
- Consumes: `GatingTest` (role protected tidak bisa dihapus).
- Produces: `RoleController::PROTECTED_ROLES` — konstanta publik `public const PROTECTED_ROLES = ['super-admin', 'user'];`. Task 3 dan view memakai literal yang sama.

- [ ] **Step 1: Tulis test gagal — `tests/Feature/RoleProtectionTest.php`**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleProtectionTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->superAdmin = User::where('email', 'superadmin@example.com')->first();
    }

    public function test_super_admin_role_cannot_be_renamed(): void
    {
        $role = Role::where('name', 'super-admin')->first();

        $this->actingAs($this->superAdmin)
            ->put("/admin/roles/{$role->id}", ['name' => 'boss', 'permissions' => []])
            ->assertRedirect(route('admin.roles.edit', $role))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'super-admin']);
    }

    public function test_user_role_cannot_be_renamed(): void
    {
        $role = Role::where('name', 'user')->first();

        $this->actingAs($this->superAdmin)
            ->put("/admin/roles/{$role->id}", ['name' => 'member', 'permissions' => []])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'user']);
    }

    public function test_custom_role_can_be_renamed(): void
    {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);

        $this->actingAs($this->superAdmin)
            ->put("/admin/roles/{$role->id}", ['name' => 'publisher', 'permissions' => []])
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'publisher']);
    }

    public function test_custom_role_rename_with_same_name_as_protected_still_allowed(): void
    {
        // Semantik proteksi: kunci nama role YANG protected, bukan melarang nama itu dipakai role lain.
        $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);

        $this->actingAs($this->superAdmin)
            ->put("/admin/roles/{$role->id}", ['name' => 'editor', 'permissions' => []])
            ->assertSessionHas('success');
    }
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `docker compose exec -u www-data app php artisan test --filter=RoleProtectionTest`
Expected: FAIL — `test_super_admin_role_cannot_be_renamed` dan `test_user_role_cannot_be_renamed` (role jadi ke-rename, tidak ada session `error`).

- [ ] **Step 3: Implementasi di `RoleController`**

Di dalam class, sebelum `index()`, tambahkan:

```php
public const PROTECTED_ROLES = ['super-admin', 'user'];
```

Di `update()`, setelah `$this->authorize('roles.edit');` dan sebelum `$request->validate(...)`, tambahkan:

```php
if ($role->name !== $request->input('name')
    && in_array($role->name, self::PROTECTED_ROLES, true)) {
    return redirect()->route('admin.roles.edit', $role)
        ->with('error', 'This role is protected and cannot be renamed.');
}
```

Ganti juga penggunaan literal di `destroy()` (`in_array($role->name, ['super-admin', 'user'], true)`) menjadi `self::PROTECTED_ROLES`.

- [ ] **Step 4: Tampilkan error + kunci input nama di `resources/views/admin/roles/edit.blade.php`**

(a) Tambah blok alert sesudah `<div class="card-body">` (sebelum `<form`):

```blade
@if (session('error'))
    <div class="alert alert-light-danger fs-6 mb-7">{{ session('error') }}</div>
@endif
```

(b) Input `name` (baris 18): tambahkan `readonly` untuk role protected. PENTING pakai `readonly`, bukan `disabled` — input `disabled` tidak ikut dikirim saat submit, akan memicu error validasi `required`:

```blade
<input class="form-control form-control-solid {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text" name="name" value="{{ old('name', $role->name) }}" {{ in_array($role->name, ['super-admin', 'user']) ? 'readonly' : '' }} />
```

- [ ] **Step 5: Jalankan test, pastikan hijau**

Run: `docker compose exec -u www-data app php artisan test --filter=RoleProtectionTest`
Expected: PASS, 4 test.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Admin/RoleController.php resources/views/admin/roles/edit.blade.php tests/Feature/RoleProtectionTest.php
git commit -m "fix: forbid renaming protected roles to prevent admin lockout"
```

---

### Task 3: Blokir self-demote super-admin

**Files:**
- Modify: `app/Http/Controllers/Admin/UserController.php`
- Modify: `resources/views/admin/users/edit.blade.php`
- Test: `tests/Feature/UserSelfDemoteTest.php`

**Interfaces:**
- Consumes: role `super-admin` dari seeder; literal protected `['super-admin', 'user']`.
- Produces: tidak ada — task terakhir yang menyentuh `UserController::update` sebelum Task 9.

- [ ] **Step 1: Tulis test gagal — `tests/Feature/UserSelfDemoteTest.php`**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSelfDemoteTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->superAdmin = User::where('email', 'superadmin@example.com')->first();
    }

    public function test_super_admin_cannot_remove_own_super_admin_role(): void
    {
        $this->actingAs($this->superAdmin)
            ->put("/admin/users/{$this->superAdmin->id}", [
                'name' => $this->superAdmin->name,
                'email' => $this->superAdmin->email,
                'roles' => ['user'],
            ])
            ->assertRedirect(route('admin.users.edit', $this->superAdmin))
            ->assertSessionHas('error');

        $this->assertTrue($this->superAdmin->fresh()->hasRole('super-admin'));
    }

    public function test_super_admin_can_edit_self_while_keeping_super_admin_role(): void
    {
        $this->actingAs($this->superAdmin)
            ->put("/admin/users/{$this->superAdmin->id}", [
                'name' => 'Renamed Admin',
                'email' => $this->superAdmin->email,
                'roles' => ['super-admin'],
            ])
            ->assertSessionHas('success');

        $this->assertSame('Renamed Admin', $this->superAdmin->fresh()->name);
    }

    public function test_admin_can_demote_another_super_admin(): void
    {
        $other = User::factory()->create();
        $other->assignRole('super-admin');

        $this->actingAs($this->superAdmin)
            ->put("/admin/users/{$other->id}", [
                'name' => $other->name,
                'email' => $other->email,
                'roles' => ['user'],
            ])
            ->assertSessionHas('success');

        $this->assertFalse($other->fresh()->hasRole('super-admin'));
    }
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `docker compose exec -u www-data app php artisan test --filter=UserSelfDemoteTest`
Expected: FAIL di `test_super_admin_cannot_remove_own_super_admin_role` (update sukses, role hilang).

- [ ] **Step 3: Implementasi di `UserController::update()`**

Tambah import di atas file: `use Illuminate\Support\Facades\Auth;` (sudah dipakai `destroy()` sebagai FQCN — sekalian rapikan: ganti `\Illuminate\Support\Facades\Auth::id()` menjadi `Auth::id()`).

Di `update()`, setelah `$user->update($data);` dan sebelum `$user->syncRoles(...)`, tambahkan:

```php
if ($user->id === Auth::id()
    && $user->hasRole('super-admin')
    && ! in_array('super-admin', $request->validated('roles', []), true)) {
    return redirect()->route('admin.users.edit', $user)
        ->with('error', 'You cannot remove your own super-admin role.');
}
```

Perhatikan urutan: cek dilakukan setelah `update($data)` tapi sebelum `syncRoles` — jadi name/email tetap tersimpan, hanya perubahan role yang diblok.

- [ ] **Step 4: Tampilkan error di `resources/views/admin/users/edit.blade.php`**

Tambah blok yang sama dengan Task 2, sesudah `<div class="card-body">` (sebelum `<form`):

```blade
@if (session('error'))
    <div class="alert alert-light-danger fs-6 mb-7">{{ session('error') }}</div>
@endif
```

- [ ] **Step 5: Jalankan test, pastikan hijau**

Run: `docker compose exec -u www-data app php artisan test --filter=UserSelfDemoteTest`
Expected: PASS, 3 test.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Admin/UserController.php resources/views/admin/users/edit.blade.php tests/Feature/UserSelfDemoteTest.php
git commit -m "fix: forbid super admin from removing own super admin role"
```

---

### Task 4: Satukan semua config Fortify di FortifyServiceProvider

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `app/Providers/FortifyServiceProvider.php`
- Test: `tests/Feature/GatingTest.php` (tidak diubah — hijau = bukti)

**Interfaces:**
- Consumes: binding yang sudah ada di `AppServiceProvider::boot()` (limiter `login` dobel, tiga view binding, `resetUserPasswordsUsing` dobel).
- Produces: `AppServiceProvider::boot()` hanya berisi `Gate::before`. Semua hal Fortify hidup di `FortifyServiceProvider::boot()`.

- [ ] **Step 1: Jalankan suite dulu (baseline hijau)**

Run: `docker compose exec -u www-data app php artisan test`
Expected: PASS semua.

- [ ] **Step 2: Pindahkan view binding; hapus duplikat dari `AppServiceProvider`**

Di `FortifyServiceProvider::boot()`, setelah baris `Fortify::redirectUserForTwoFactorAuthenticationUsing(...)`, tambahkan:

```php
Fortify::loginView(fn () => view('auth.login'));
Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
Fortify::resetPasswordView(fn ($request) => view('auth.reset-password', ['request' => $request]));
```

Ubah `AppServiceProvider::boot()` menjadi hanya:

```php
public function boot(): void
{
    Gate::before(fn ($user, $ability) => $user->hasRole('super-admin') ? true : null);
}
```

Hapus import yang jadi tak terpakai di `AppServiceProvider`: `Limit`, `Request`, `RateLimiter`, `Fortify`. (Catatan: `resetUserPasswordsUsing` TIDAK ikut dipindah — sudah ada di `FortifyServiceProvider` baris `Fortify::resetUserPasswordsUsing(ResetUserPassword::class)`; baris di `AppServiceProvider` memang duplikat, hapus saja. Limiter `login` versi `FortifyServiceProvider` yang menang (pakai `Str::transliterate`); hapus versi `AppServiceProvider`.)

- [ ] **Step 3: Jalankan suite penuh**

Run: `docker compose exec -u www-data app php artisan test`
Expected: PASS semua — termasuk `GatingTest` (`/login`, `/forgot-password`, `/reset-password/...` tetap render).

- [ ] **Step 4: Commit**

```bash
git add app/Providers/AppServiceProvider.php app/Providers/FortifyServiceProvider.php
git commit -m "refactor: consolidate fortify config into fortify service provider"
```

---

### Task 5: RoleController pakai FormRequest

**Files:**
- Create: `app/Http/Requests/StoreRoleRequest.php`
- Create: `app/Http/Requests/UpdateRoleRequest.php`
- Modify: `app/Http/Controllers/Admin/RoleController.php`
- Test: `tests/Feature/RoleCrudTest.php`

**Interfaces:**
- Consumes: `RoleController::PROTECTED_ROLES` (Task 2); pola FormRequest dari `StoreUserRequest`.
- Produces: `StoreRoleRequest` / `UpdateRoleRequest` dengan `rules()`: `name` + `permissions.*`; `authorize()` masing-masing `roles.create` / `roles.edit`.

- [ ] **Step 1: Tulis test — `tests/Feature/RoleCrudTest.php`**

Test pertama mengunci Review Focus #5 (middleware route hanya minta `roles.view`; POST harus 403 untuk yang tidak punya `roles.create`):

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->superAdmin = User::where('email', 'superadmin@example.com')->first();
    }

    public function test_user_with_only_roles_view_gets_403_on_create(): void
    {
        $viewer = User::factory()->create();
        $viewerRole = Role::create(['name' => 'role-viewer', 'guard_name' => 'web']);
        $viewerRole->givePermissionTo(Permission::where('name', 'roles.view')->first());
        $viewer->assignRole($viewerRole);

        $this->actingAs($viewer)
            ->post('/admin/roles', ['name' => 'new-role', 'permissions' => []])
            ->assertForbidden();
    }

    public function test_super_admin_can_create_role_with_permissions(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/admin/roles', ['name' => 'editor', 'permissions' => ['users.view', 'users.edit']])
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('success');

        $role = Role::where('name', 'editor')->first();
        $this->assertTrue($role->hasPermissionTo('users.view'));
        $this->assertTrue($role->hasPermissionTo('users.edit'));
    }

    public function test_duplicate_role_name_is_rejected(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/admin/roles', ['name' => 'super-admin', 'permissions' => []])
            ->assertInvalid('name');
    }
}
```

- [ ] **Step 2: Jalankan, catat status**

Run: `docker compose exec -u www-data app php artisan test --filter=RoleCrudTest`
Expected: kemungkinan besar ketiga test SUDAH PASS (`$this->authorize('roles.create')` di controller sudah menutup 403). Kalau sudah hijau, lanjut — Step 3–4 refactor yang dijaga test ini. Kalau ada yang gagal, berhenti dan laporkan.

- [ ] **Step 3: Buat dua FormRequest**

`app/Http/Requests/StoreRoleRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('roles.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }
}
```

`app/Http/Requests/UpdateRoleRequest.php` — sama persis kecuali: nama class `UpdateRoleRequest`, `authorize()` return `$this->user()->can('roles.edit')`, dan rule `name` mengabaikan record ini:

```php
'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($this->route('role')->id)],
```

- [ ] **Step 4: Rampingkan RoleController**

- Hapus seluruh docblock `@method bool authorize(...)` di atas class.
- `store(\Illuminate\Http\Request $request)` → `store(StoreRoleRequest $request)`; hapus baris `$this->authorize('roles.create');` dan `$request->validate([...])`; sisakan `Role::create(...)` + `syncPermissions` seperti semula.
- `update(\Illuminate\Http\Request $request, Role $role)` → `update(UpdateRoleRequest $request, Role $role)`; hapus `$this->authorize('roles.edit');` dan `$request->validate([...])`. Blok proteksi rename (Task 2) TETAP di tempatnya, jadi baris pertama method.
- Ganti `$request->input('name')` → `$request->validated('name')` dan `$request->input('permissions', [])` → `$request->validated('permissions', [])` di kedua method.
- Tambah import `use App\Http\Requests\StoreRoleRequest;` dan `use App\Http\Requests\UpdateRoleRequest;`.

- [ ] **Step 5: Jalankan suite penuh**

Run: `docker compose exec -u www-data app php artisan test`
Expected: PASS semua (`GatingTest`, `RoleProtectionTest`, `RoleCrudTest`, dst.).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Requests/StoreRoleRequest.php app/Http/Requests/UpdateRoleRequest.php app/Http/Controllers/Admin/RoleController.php tests/Feature/RoleCrudTest.php
git commit -m "refactor: role validation via form requests, drop phpdoc hack"
```

---

### Task 6: Hapus action Fortify yang tidak pernah dipanggil

**Files:**
- Delete: `app/Actions/Fortify/CreateNewUser.php`
- Delete: `app/Actions/Fortify/UpdateUserProfileInformation.php`
- Delete: `app/Actions/Fortify/UpdateUserPassword.php`
- Modify: `app/Providers/FortifyServiceProvider.php`

**Interfaces:**
- Consumes: registrasi dinonaktifkan (`config/fortify.php` `features` hanya `resetPasswords`), profil via `ProfileController`.
- Produces: tersisa `ResetUserPassword.php` + trait `PasswordValidationRules.php` — DIPERTAHANKAN (dipakai reset password).

- [ ] **Step 1: Hapus file + binding**

```bash
git rm app/Actions/Fortify/CreateNewUser.php app/Actions/Fortify/UpdateUserProfileInformation.php app/Actions/Fortify/UpdateUserPassword.php
```

Di `FortifyServiceProvider::boot()` hapus tiga baris:

```php
Fortify::createUsersUsing(CreateNewUser::class);
Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
```

Hapus import yang jadi tak terpakai: `CreateNewUser`, `UpdateUserProfileInformation`, `UpdateUserPassword`. JANGAN sentuh `ResetUserPassword` dan `PasswordValidationRules`.

- [ ] **Step 2: Cek tidak ada referensi tersisa**

Run: `grep -rn "CreateNewUser\|UpdateUserProfileInformation\|UpdateUserPassword" app config routes tests`
Expected: kosong.

- [ ] **Step 3: Jalankan suite penuh**

Run: `docker compose exec -u www-data app php artisan test`
Expected: PASS semua (login/logout/forgot/reset tetap jalan — tidak ada yang memanggil action yang dihapus).

- [ ] **Step 4: Commit**

```bash
git add -A app/Providers/FortifyServiceProvider.php
git commit -m "refactor: remove unused fortify profile and registration actions"
```

---

### Task 7: Hapus scaffolding passkey/2FA yang mati

**Files:**
- Delete: `database/migrations/2026_11_13_183038_add_two_factor_columns_to_users_table.php`
- Delete: `database/migrations/2026_11_13_183039_create_passkeys_table.php`
- Modify: `app/Providers/FortifyServiceProvider.php`
- Modify: `config/fortify.php`

**Interfaces:**
- Consumes: `config/fortify.php` `features` hanya `Features::resetPasswords()` (tidak diubah).
- Produces: skema users tanpa kolom two_factor, tanpa tabel passkeys. CATATAN: fitur 2FA/passkey sengaja tidak dikerjakan sekarang — kalau nanti diinginkan, tambahkan ulang via dokumentasi Fortify.

- [ ] **Step 1: Hapus dua migration**

```bash
git rm database/migrations/2026_11_13_183038_add_two_factor_columns_to_users_table.php database/migrations/2026_11_13_183039_create_passkeys_table.php
```

- [ ] **Step 2: Bersihkan `FortifyServiceProvider`**

- Hapus import `Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable` dan baris `Fortify::redirectUserForTwoFactorAuthenticationUsing(...)`.
- Hapus dua blok `RateLimiter::for('two-factor', ...)` dan `RateLimiter::for('passkeys', ...)` beserta closure-nya. Sisakan hanya `RateLimiter::for('login', ...)`.

- [ ] **Step 3: Bersihkan `config/fortify.php`**

- Array `limiters` jadi hanya: `'limiters' => ['login' => 'login'],`
- Hapus seluruh blok config `'passkeys' => [...]` beserta comment header-nya.

- [ ] **Step 4: Sinkronkan DB dev dan jalankan suite**

```bash
docker compose exec -u www-data app php artisan migrate:fresh --seed
docker compose exec -u www-data app php artisan test
```

Expected: migrate sukses tanpa error; suite PASS semua. (`migrate:fresh` perlu karena DB dev masih punya tabel passkeys; alur README memang selalu fresh+seed.)

- [ ] **Step 5: Commit**

```bash
git add -A app/Providers/FortifyServiceProvider.php config/fortify.php
git commit -m "chore: remove dead passkey and two factor scaffolding"
```

---

### Task 8: Pagination + search server-side untuk users dan roles

**Files:**
- Modify: `app/Http/Controllers/Admin/UserController.php`
- Modify: `app/Http/Controllers/Admin/RoleController.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `resources/views/admin/users/index.blade.php`
- Modify: `resources/views/admin/roles/index.blade.php`
- Test: `tests/Feature/PaginationTest.php`

**Interfaces:**
- Consumes: `UserFactory` (password default `password`), seeder.
- Produces: `index()` mengirim `LengthAwarePaginator` (`$users` / `$roles`) ke view; search via query string `?search=`.

- [ ] **Step 1: Tulis test gagal — `tests/Feature/PaginationTest.php`**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->superAdmin = User::where('email', 'superadmin@example.com')->first();
    }

    public function test_users_index_paginates_at_25(): void
    {
        User::factory()->count(30)->create(); // total 32 (2 seeder + 30)

        $this->actingAs($this->superAdmin)->get('/admin/users')
            ->assertOk()
            ->assertViewHas('users', fn ($users) => $users->lastPage() === 2 && $users->count() === 25);
    }

    public function test_users_index_page_2_shows_remaining_users(): void
    {
        User::factory()->count(30)->create();

        $this->actingAs($this->superAdmin)->get('/admin/users?page=2')
            ->assertOk()
            ->assertViewHas('users', fn ($users) => $users->currentPage() === 2 && $users->count() === 7);
    }

    public function test_users_search_filters_by_name_or_email(): void
    {
        User::factory()->create(['name' => 'Zedreq Uniqueperson']);

        $this->actingAs($this->superAdmin)->get('/admin/users?search=Zedreq')
            ->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 1);
    }

    public function test_users_search_treats_percent_as_literal(): void
    {
        $this->actingAs($this->superAdmin)->get('/admin/users?search=%')
            ->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 0);
    }

    public function test_users_pagination_keeps_search_query(): void
    {
        User::factory()->count(26)
            ->sequence(fn ($sequence) => ['name' => "Bulky Person {$sequence->index}"])
            ->create(); // 26 cocok "Bulky" → halaman 1 = 25, halaman 2 = 1

        $this->actingAs($this->superAdmin)->get('/admin/users?search=Bulky')
            ->assertOk()
            ->assertSee('?search=Bulky&amp;page=2', false); // link halaman 2 wajib bawa filter

        $this->actingAs($this->superAdmin)->get('/admin/users?search=Bulky&page=2')
            ->assertOk()
            ->assertViewHas('users', fn ($users) => $users->count() === 1);
    }

    public function test_roles_search_filters_by_name(): void
    {
        $this->actingAs($this->superAdmin)->get('/admin/roles?search=super-admin')
            ->assertOk()
            ->assertViewHas('roles', fn ($roles) => $roles->total() === 1);
    }
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `docker compose exec -u www-data app php artisan test --filter=PaginationTest`
Expected: FAIL — `assertViewHas` (view menerima Collection, bukan Paginator), `total()` tidak ada, `?search=` diabaikan.

- [ ] **Step 3: Implementasi controller**

`UserController::index()` — tambah import `use Illuminate\Http\Request;`, ganti body:

```php
public function index(Request $request): View
{
    $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $request->input('search', '')).'%';

    $users = User::with('roles')
        ->when($request->filled('search'), fn ($query) => $query
            ->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term)))
        ->orderBy('id')
        ->paginate(25)
        ->withQueryString();

    return view('admin.users.index', ['users' => $users]);
}
```

`RoleController::index()` — sama pola, kolom `name` saja, plus pertahankan `withCount('permissions')`:

```php
public function index(Request $request): View
{
    $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $request->input('search', '')).'%';

    $roles = Role::withCount('permissions')
        ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', $term))
        ->orderBy('id')
        ->paginate(25)
        ->withQueryString();

    return view('admin.roles.index', ['roles' => $roles]);
}
```

(Tambah `use Illuminate\Http\Request;` di `RoleController` juga.)

Di `AppServiceProvider::boot()` tambah agar `links()` render Bootstrap 5 (selaraskan Metronic):

```php
\Paginator::useBootstrapFive();
```

dengan import `use Illuminate\Pagination\Paginator;` di atas file.

- [ ] **Step 4: Ubah kedua view index**

`users/index.blade.php`:
(a) Ganti blok input search (baris 35–37) menjadi form GET:

```blade
<form method="GET" action="{{ route('admin.users.index') }}" class="d-flex align-items-center position-relative my-1">
    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-solid w-250px ps-14" placeholder="Search name or email" />
</form>
```

(b) Tambah link pagination sesudah `</table>` (masih di dalam `<div class="card-body pt-0">`):

```blade
{{ $users->links() }}
```

(c) Hapus DUA blok @push di bawah file (`vendor-scripts` datatables.bundle.js dan `scripts` inisialisasi `DataTable`). DataTables klien dicabut — pagination sekarang server-side; kalau dibiarkan, DataTables akan membagi ulang 25 baris jadi halaman 10 yang membingungkan.

`roles/index.blade.php`: sama persis (form search action `admin.roles.index`, placeholder "Search role", `{{ $roles->links() }}`, hapus dua @push).

- [ ] **Step 5: Jalankan test, pastikan hijau**

Run: `docker compose exec -u www-data app php artisan test --filter=PaginationTest`
Expected: PASS, 6 test.

- [ ] **Step 6: Verifikasi manual browser**

Login `http://localhost:8089/login` (atau 8088) sebagai superadmin → `/admin/users`: search "user" → baris terfilter; hapus search, navigasi halaman 2. Perhatikan: tidak ada JS error di console (DataTables sudah tidak dimuat di view ini).

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Admin/UserController.php app/Http/Controllers/Admin/RoleController.php app/Providers/AppServiceProvider.php resources/views/admin/users/index.blade.php resources/views/admin/roles/index.blade.php tests/Feature/PaginationTest.php
git commit -m "feat: server side pagination and search for users and roles"
```

---

### Task 9: Hapus no-op unset password

**Files:**
- Modify: `app/Http/Controllers/Admin/UserController.php:52-56`

**Interfaces:**
- Consumes: `UpdateUserRequest::validated()` — key yang absen dari input tidak ikut dikembalikan; `password` nullable berarti kalau hadir pasti lolos `min:8` (string kosong ditolak validasi).
- Produces: tidak ada.

- [ ] **Step 1: Jalankan suite dulu (baseline hijau)**

Run: `docker compose exec -u www-data app php artisan test --filter=UserSelfDemoteTest`
Expected: PASS.

- [ ] **Step 2: Ganti empat baris di `update()`**

```php
$data = $request->validated();
if (! isset($data['password'])) {
    unset($data['password']);
}
$user->update($data);
```

menjadi:

```php
$user->update($request->validated());
```

(Blok proteksi self-demote dari Task 3 tidak berubah — masih setelahnya. Key `roles` di validated() aman diabaikan mass-assignment karena tidak ada di `$fillable` User.)

- [ ] **Step 3: Jalankan suite penuh**

Run: `docker compose exec -u www-data app php artisan test`
Expected: PASS semua.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Admin/UserController.php
git commit -m "refactor: drop no-op password unset in user update"
```

---

### Task 10: Hapus file sampah repo

**Files:**
- Delete: `jar.txt`, `jar6.txt`, `jar7.txt`, `jarv.txt`, `temp_app/`

**Interfaces:**
- Consumes: tidak ada.
- Produces: tidak ada. (File sudah masuk `.gitignore` (`jar*.txt`) — kosongkan disk saja, tidak ada perubahan git.)

- [ ] **Step 1: Hapus**

```bash
rm -f jar.txt jar6.txt jar7.txt jarv.txt
rmdir temp_app
```

Kalau `rmdir` menolak karena tidak kosong, jalankan `ls -A temp_app` DAN laporkan isinya dulu — jangan `rm -rf` buta.

- [ ] **Step 2: Verifikasi**

Run: `git status --short`
Expected: kosong (file-file ini untracked/ignored). Tidak ada commit di task ini.

---

### Task 11: Logout perangkat lain saat ganti password

**Files:**
- Modify: `app/Http/Controllers/ProfileController.php`
- Test: `tests/Feature/ProfilePasswordTest.php`

**Interfaces:**
- Consumes: `UpdatePasswordRequest` (`current_password` tervalidasi `current_password:web`).
- Produces: tidak ada.

- [ ] **Step 1: Tulis test — `tests/Feature/ProfilePasswordTest.php`**

Test kedua mengunci Review Focus #4 (urutan logoutOtherDevices vs update hash):

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilePasswordTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'user@example.com')->first();
    }

    public function test_password_change_updates_hash(): void
    {
        $this->actingAs($this->user)
            ->post('/profile/password', [
                'current_password' => 'password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password-123', $this->user->fresh()->password));
        $this->assertFalse(Hash::check('password', $this->user->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected_not_500(): void
    {
        $this->actingAs($this->user)
            ->from('/profile')
            ->post('/profile/password', [
                'current_password' => 'wrong-old-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('password', $this->user->fresh()->password));
    }
}
```

- [ ] **Step 2: Jalankan — kemungkinan sudah hijau**

Run: `docker compose exec -u www-data app php artisan test --filter=ProfilePasswordTest`
Expected: PASS (behavior hash sudah ada). Test ini mengunci kontrak untuk perubahan Step 3.

- [ ] **Step 3: Tambah logoutOtherDevices — SEBELUM update**

Di `ProfileController::updatePassword()`, jadikan baris pertama method body:

```php
$request->user()->logoutOtherDevices($request->validated('current_password'));
```

Sebelum baris `$request->user()->update([...])`. Urutan wajib: fungsi ini mencocokkan argumen dengan hash LAMA; kalau ditaruh setelah `update()`, password lama divalidasi terhadap hash baru dan request valid pun melempar exception.

- [ ] **Step 4: Jalankan test + suite penuh**

Run: `docker compose exec -u www-data app php artisan test`
Expected: PASS semua, termasuk `ProfilePasswordTest` (wrong current password → redirect validasi, bukan 500).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/ProfileController.php tests/Feature/ProfilePasswordTest.php
git commit -m "fix: log out other devices on password change"
```

---

## Penutup eksekusi

Setelah Task 11:

```bash
docker compose exec -u www-data app php artisan test
```

Suite penuh hijau = selesai. Jangan tambah fitur lain di branch ini.
