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
