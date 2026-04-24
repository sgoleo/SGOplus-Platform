<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

use Illuminate\Support\Facades\Hash;
use App\Models\PointTransaction;
use Illuminate\Support\Facades\DB;

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
     * Create a new user (Admin only).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'department' => 'nullable|string',
            'roles' => 'nullable|array',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'department' => $validated['department'],
            'points' => 0,
        ]);

        if (!empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return response()->json($user, 201);
    }

    /**
     * Update user profile and roles.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'department' => 'nullable|string',
            'roles' => 'nullable|array',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'department' => $validated['department'],
        ]);

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return response()->json($user);
    }

    /**
     * Manually adjust user points.
     */
    public function adjustPoints(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'amount' => 'required|integer',
            'reason' => 'required|string',
        ]);

        $amount = (int) $request->amount;

        DB::transaction(function () use ($user, $amount, $request) {
            $user->increment('points', $amount);
            
            PointTransaction::create([
                'user_id' => $user->id,
                'task_id' => null, // Manual adjustment
                'points_awarded' => $amount,
                'description' => "管理員手動調整：{$request->reason}",
            ]);
        });

        return response()->json(['points' => $user->points]);
    }

    /**
     * Delete a user.
     */
    public function destroy(User $user): JsonResponse
    {
        if ($user->id === Auth::id()) {
            abort(400, '不能刪除當前登入的帳號。');
        }
        $user->delete();
        return response()->json(['message' => '用戶已刪除']);
    }

    /**
     * Get all available roles in the system.
     */
    public function roles(): JsonResponse
    {
        return response()->json(Role::all()->pluck('name'));
    }
}
