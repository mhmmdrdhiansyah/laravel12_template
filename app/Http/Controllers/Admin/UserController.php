<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::with('roles')->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ]);
        $user->syncRoles($request->validated('roles', []));

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user->load('roles'),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        if (! isset($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);
        $user->syncRoles($request->validated('roles', []));

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === \Illuminate\Support\Facades\Auth::id()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete your own account.');
        }
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
