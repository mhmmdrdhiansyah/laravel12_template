<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.activate',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'permissions.view',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }
}
