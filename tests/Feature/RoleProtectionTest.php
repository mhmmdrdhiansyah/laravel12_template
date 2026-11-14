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
