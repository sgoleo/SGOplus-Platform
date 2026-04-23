<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Get all users with roles and points.
     */
    public function index(): JsonResponse
    {
        /** @var \App\Models\User $admin */
        $admin = Auth::user();
        if (!$admin->hasRole('SuperAdmin')) {
            abort(403, '權限不足。');
        }

        $users = User::with('roles')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department,
                'points' => $user->points,
                'roles' => $user->getRoleNames(),
            ];
        });

        return response()->json($users);
    }

    /**
     * Get all available roles in the system.
     */
    public function roles(): JsonResponse
    {
        return response()->json(Role::all()->pluck('name'));
    }

    /**
     * Sync roles for a specific user.
     */
    public function updateRoles(Request $request, User $user): JsonResponse
    {
        /** @var \App\Models\User $admin */
        $admin = Auth::user();
        if (!$admin->hasRole('SuperAdmin')) {
            abort(403, '只有最高權限管理員可以變更權限。');
        }

        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user->syncRoles($request->roles);

        return response()->json([
            'message' => '使用者角色已更新',
            'roles' => $user->getRoleNames()
        ]);
    }
}
