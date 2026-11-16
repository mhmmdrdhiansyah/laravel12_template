<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Notifications\NewUserRegistration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user (pending activation).
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'status' => 'pending',
        ]);

        $user->assignRole('user');

        User::role('super-admin')->get()->each(
            fn (User $admin) => $admin->notify(new NewUserRegistration($user))
        );

        return $user;
    }
}
