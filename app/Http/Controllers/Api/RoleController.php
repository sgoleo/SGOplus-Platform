<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Get detailed roles list with permissions and department.
     */
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'department' => $role->department,
                'description' => $role->description,
                'icon' => $role->icon,
                'color' => $role->color,
                'permissions' => $role->permissions->pluck('name'),
            ];
        });

        return response()->json($roles);
    }

    /**
     * Get all available permissions.
     */
    public function permissions(): JsonResponse
    {
        // Force fetch only sanctum guard permissions to avoid 500 errors
        return response()->json(Permission::where('guard_name', 'sanctum')->pluck('name'));
    }

    /**
     * Create a new role.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->hasRole('SuperAdmin')) abort(403);

        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'department' => 'nullable|string',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);

        // Create Role with specific guard
        $role = Role::create([
            'name' => $validated['name'],
            'department' => $validated['department'],
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'color' => $validated['color'] ?? null,
            'guard_name' => 'sanctum'
        ]);

        if (!empty($validated['permissions'])) {
            // FORCE find permissions specifically for sanctum guard
            $permissions = Permission::whereIn('name', $validated['permissions'])
                ->where('guard_name', 'sanctum')
                ->get();
            $role->syncPermissions($permissions);
        }

        return response()->json($role, 201);
    }

    /**
     * Update an existing role.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->hasRole('SuperAdmin')) abort(403);

        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'department' => 'nullable|string',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);

        $role->update([
            'name' => $validated['name'],
            'department' => $validated['department'],
            'description' => $validated['description'] ?? $role->description,
            'icon' => $validated['icon'] ?? $role->icon,
            'color' => $validated['color'] ?? $role->color,
            'guard_name' => 'sanctum' // Keep it consistent
        ]);

        if (isset($validated['permissions'])) {
            // FORCE fetch permissions under sanctum guard
            $permissions = Permission::whereIn('name', $validated['permissions'])
                ->where('guard_name', 'sanctum')
                ->get();
            $role->syncPermissions($permissions);
        }

        return response()->json($role);
    }

    /**
     * Delete a role.
     */
    public function destroy(Role $role): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->hasRole('SuperAdmin')) abort(403);

        $role->delete();
        return response()->json(['message' => '角色已刪除']);
    }
}
