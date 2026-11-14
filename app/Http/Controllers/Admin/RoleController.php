<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * @method bool authorize(string $ability, mixed ...$arguments)
 */
class RoleController extends Controller
{
    public const PROTECTED_ROLES = ['super-admin', 'user'];

    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::withCount('permissions')->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function store(\Illuminate\Http\Request $request): RedirectResponse
    {
        $this->authorize('roles.create');
        $request->validate(['name' => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('roles', 'name')]]);

        $role = Role::create(['name' => $request->input('name'), 'guard_name' => 'web']);
        $role->syncPermissions($request->input('permissions', []));

        return redirect()->route('admin.roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.edit', [
            'role' => $role->load('permissions'),
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function update(\Illuminate\Http\Request $request, Role $role): RedirectResponse
    {
        $this->authorize('roles.edit');
        if ($role->name !== $request->input('name')
            && in_array($role->name, self::PROTECTED_ROLES, true)) {
            return redirect()->route('admin.roles.edit', $role)
                ->with('error', 'This role is protected and cannot be renamed.');
        }
        $request->validate(['name' => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('roles', 'name')->ignore($role->id)]]);

        $role->update(['name' => $request->input('name')]);
        $role->syncPermissions($request->input('permissions', []));

        return redirect()->route('admin.roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('roles.delete');
        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return redirect()->route('admin.roles.index')->with('error', 'This role is protected and cannot be deleted.');
        }
        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted.');
    }
}
