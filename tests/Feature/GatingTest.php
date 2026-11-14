<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'user@example.com'])
            ->assertSessionHas('status');

        Notification::assertSentTo($this->regularUser, ResetPassword::class);
    }

    public function test_reset_password_page_renders(): void
    {
        $this->get('/reset-password/any-token?email=user@example.com')->assertOk();
    }
}
