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
