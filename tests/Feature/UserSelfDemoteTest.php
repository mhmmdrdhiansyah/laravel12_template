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
