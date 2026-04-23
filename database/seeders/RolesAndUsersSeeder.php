<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use App\Models\Department;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class RolesAndUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 0. Create Initial Departments
        Department::updateOrCreate(['code' => 'SGOstudio'], ['name' => 'SGOstudio', 'description' => '創意工作室']);
        Department::updateOrCreate(['code' => 'Hardware R&D'], ['name' => 'Hardware R&D', 'description' => '硬體研發部門']);

        // 1. Reset Spatie Cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Define Permissions
        $permissions = [
            'view-tasks',
            'create-tasks',
            'edit-tasks',
            'delete-tasks',
            'manage-projects',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        // 3. Define Roles
        $superAdmin = Role::firstOrCreate(['name' => 'SuperAdmin']);
        $studioMember = Role::firstOrCreate(['name' => 'SGOstudioMember']);
        $hardwareDev = Role::firstOrCreate(['name' => 'HardwareDev']);

        // Give all permissions to SuperAdmin
        $superAdmin->syncPermissions(Permission::all());
        $studioMember->syncPermissions(['view-tasks', 'create-tasks']);
        $hardwareDev->syncPermissions(['view-tasks', 'edit-tasks']);

        // 4. Create Test Users
        $usersData = [
            [
                'name' => 'Admin User',
                'email' => 'admin@sgoplus.one',
                'department' => null,
                'role' => $superAdmin,
            ],
            [
                'name' => 'Studio Member',
                'email' => 'studio@sgoplus.com',
                'department' => 'SGOstudio',
                'role' => $studioMember,
            ],
            [
                'name' => 'Hardware Developer',
                'email' => 'hardware@sgoplus.com',
                'department' => 'Hardware R&D',
                'role' => $hardwareDev,
            ],
        ];

        $users = [];
        foreach ($usersData as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('SGOgroup*7'),
                    'department' => $data['department'],
                ]
            );
            $user->syncRoles([$data['role']]);
            $users[] = $user;
        }

        // 5. Create Projects and Tasks
        $projects = [
            [
                'name' => 'SGOplus Website Redesign',
                'department' => 'SGOstudio',
                'tasks' => ['Frontend Layout', 'Backend API Integration', 'UI/UX Review']
            ],
            [
                'name' => 'SGO-X Sensor Prototype',
                'department' => 'Hardware R&D',
                'tasks' => ['Circuit Design', 'Firmware Coding', 'Stress Testing']
            ],
        ];

        foreach ($projects as $projData) {
            $project = Project::updateOrCreate(
                ['name' => $projData['name']],
                ['department' => $projData['department'], 'description' => $projData['name'] . ' description']
            );

            foreach ($projData['tasks'] as $taskTitle) {
                Task::updateOrCreate(
                    ['title' => $taskTitle, 'project_id' => $project->id],
                    [
                        'department' => $project->department, // denormalized
                        'details' => "Details for $taskTitle",
                        'status' => 'Open',
                        'due_date' => now()->addDays(rand(1, 30)),
                    ]
                );
            }
        }
    }
}
