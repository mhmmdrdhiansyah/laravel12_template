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
