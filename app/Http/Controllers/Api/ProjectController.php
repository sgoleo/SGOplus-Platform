<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    /**
     * Store a newly created project.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project = Project::create($validated);

        return response()->json($project, 201);
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->hasRole('SuperAdmin') && !$user->hasPermissionTo('manage-projects')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|in:active,archived,completed'
        ]);

        $project->update($validated);

        return response()->json($project);
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->hasRole('SuperAdmin')) {
            abort(403);
        }

        $project->delete();

        return response()->json(['message' => '專案已刪除']);
    }

    /**
     * Get list of projects (filtered by department via scope).
     */
    public function index(): JsonResponse
    {
        return response()->json(Project::all());
    }
}
