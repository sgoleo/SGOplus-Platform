<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'view-projects',
            'create-projects',
            'edit-projects',
            'delete-projects',
            'manage-tasks',
            'assign-tasks',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // 1. Super Admin Role
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // 2. SGOstudio Roles
        $studioManager = Role::create(['name' => 'SGOstudio-Manager']);
        $studioManager->givePermissionTo(['view-projects', 'create-projects', 'edit-projects', 'manage-tasks', 'assign-tasks']);

        $studioMember = Role::create(['name' => 'SGOstudio-Member']);
        $studioMember->givePermissionTo(['view-projects', 'manage-tasks']);

        // 3. Hardware R&D Roles
        $hardwareManager = Role::create(['name' => 'Hardware-Manager']);
        $hardwareManager->givePermissionTo(['view-projects', 'create-projects', 'edit-projects', 'manage-tasks', 'assign-tasks']);

        $hardwareMember = Role::create(['name' => 'Hardware-Member']);
        $hardwareMember->givePermissionTo(['view-projects', 'manage-tasks']);

        // Create Initial Admin User
        $admin = User::updateOrCreate(
            ['email' => 'admin@sgoplus.com'],
            [
                'name' => 'SGOplus Admin',
                'password' => Hash::make('password'),
                'department' => null, // Admins see everything
            ]
        );
        $admin->assignRole($adminRole);

        // Create a Test Studio Manager
        $manager = User::updateOrCreate(
            ['email' => 'studio.manager@sgoplus.com'],
            [
                'name' => 'Studio Lead',
                'password' => Hash::make('password'),
                'department' => 'SGOstudio',
            ]
        );
        $manager->assignRole($studioManager);
    }
}
