<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function __construct(
        protected TaskService $taskService
    ) {}

    /**
     * Display a listing of the tasks filtered by user permissions.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $tasks = $this->taskService->getTasksForUser($user, $request->only(['status', 'project_id']));

        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created task.
     */
    public function store(Request $request): TaskResource
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'department' => 'required|string',
            'title' => 'required|string|max:255',
            'details' => 'nullable|string',
            'status' => 'required|in:Open,In Progress,Review,Done',
            'due_date' => 'nullable|date',
        ]);

        $task = $this->taskService->createTask($validated);

        return new TaskResource($task);
    }

    /**
     * Update task status.
     */
    public function update(Request $request, int $id): TaskResource
    {
        $validated = $request->validate([
            'status' => 'required|in:Open,In Progress,Review,Done',
        ]);

        $task = $this->taskService->updateTaskStatus($id, $validated['status']);

        return new TaskResource($task);
    }

    /**
     * Approve the task and award points.
     */
    public function approve(int $id): TaskResource
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Basic RBAC check (can be refined with dedicated Policy)
        if (!$user->hasRole('SuperAdmin') && !$user->hasPermissionTo('manage-projects')) {
            abort(403, '權限不足。');
        }

        $task = $this->taskService->approveTask($id, $user);

        return new TaskResource($task);
    }

    /**
     * Reject the task back to in progress.
     */
    public function reject(int $id): TaskResource
    {
        $task = $this->taskService->rejectTask($id);
        return new TaskResource($task);
    }
}
