<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['owner', 'manager', 'developer', 'viewer'];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name'       => $role,
                'guard_name' => 'web',
            ]);
        }

        $permissions = [
            'manage-projects',
            'view-projects',
            'manage-tasks',
            'view-tasks',
            'manage-sprints',
            'manage-members',
            'use-ai-features',
            'export-reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'web',
            ]);
        }

        Role::findByName('owner')->givePermissionTo(Permission::all());

        Role::findByName('manager')->givePermissionTo([
            'manage-projects',
            'view-projects',
            'manage-tasks',
            'view-tasks',
            'manage-sprints',
            'manage-members',
            'use-ai-features',
            'export-reports',
        ]);

        Role::findByName('developer')->givePermissionTo([
            'view-projects',
            'manage-tasks',
            'view-tasks',
            'use-ai-features',
        ]);

        Role::findByName('viewer')->givePermissionTo([
            'view-projects',
            'view-tasks',
        ]);
    }
}