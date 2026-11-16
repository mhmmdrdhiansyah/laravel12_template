<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
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
            'status' => 'active',
            'activated_at' => now(),
            'activated_by' => Auth::id(),
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
        $user->update($request->validated());
        if ($user->id === Auth::id()
            && $user->hasRole('super-admin')
            && ! in_array('super-admin', $request->validated('roles', []), true)) {
            return redirect()->route('admin.users.edit', $user)
                ->with('error', 'You cannot remove your own super-admin role.');
        }
        $user->syncRoles($request->validated('roles', []));

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('users.delete');
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete your own account.');
        }
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    public function activate(User $user): RedirectResponse
    {
        $this->authorize('users.activate');

        $user->update([
            'status' => 'active',
            'activated_at' => now(),
            'activated_by' => Auth::id(),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User activated.');
    }

    public function reject(User $user): RedirectResponse
    {
        $this->authorize('users.activate');

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
        $this->authorize('users.activate');

        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot change your own account status.');
        }

        if ($user->status !== 'active') {
            return redirect()->route('admin.users.index')->with('error', 'Only active accounts can be deactivated.');
        }

        $user->update(['status' => 'deactivated']);

        return redirect()->route('admin.users.index')->with('success', 'User deactivated.');
    }
}
