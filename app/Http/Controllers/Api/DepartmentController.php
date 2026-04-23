<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Department::all());
    }

    public function store(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->hasRole('SuperAdmin')) {
            abort(403, '只有最高權限管理員可以新增部門。');
        }

        $validated = $request->validate([
            'name' => 'required|string|unique:departments,name',
            'code' => 'required|string|unique:departments,code',
            'description' => 'nullable|string',
        ]);

        $department = Department::create($validated);
        return response()->json($department, 201);
    }

    public function update(Request $request, Department $department): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->hasRole('SuperAdmin')) {
            abort(403, '只有最高權限管理員可以編輯部門。');
        }

        $validated = $request->validate([
            'name' => 'required|string|unique:departments,name,' . $department->id,
            'code' => 'required|string|unique:departments,code,' . $department->id,
            'description' => 'nullable|string',
        ]);

        $department->update($validated);
        return response()->json($department);
    }

    public function destroy(Department $department): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->hasRole('SuperAdmin')) {
            abort(403, '只有最高權限管理員可以刪除部門。');
        }

        $department->delete();
        return response()->json(['message' => '部門已刪除']);
    }
}
