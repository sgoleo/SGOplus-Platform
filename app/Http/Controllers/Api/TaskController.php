<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Services\TaskService;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
        
        $tasks = $this->taskService->getTasksForUser($user, $request->only(['status', 'project_id', 'all']));

        return TaskResource::collection($tasks);
    }

    /**
     * Get all pending reviews for admin.
     */
    public function pendingReviews(): AnonymousResourceCollection
    {
        $tasks = $this->taskService->getPendingReviews();
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
            'reward_points' => 'nullable|integer',
            'is_crowdsourced' => 'nullable|boolean',
            'max_assignees' => 'nullable|integer|min:1',
        ]);

        // Force casting for specific types
        $validated['is_crowdsourced'] = filter_var($request->input('is_crowdsourced'), FILTER_VALIDATE_BOOLEAN);
        $validated['max_assignees'] = (int) ($request->input('max_assignees') ?? 1);
        $validated['reward_points'] = (int) ($request->input('reward_points') ?? 0);

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
     * Remove the specified task.
     */
    public function destroy(int $id): JsonResponse
    {
        /** @var \App\Models\User $admin */
        $admin = Auth::user();
        if (!$admin->hasRole('SuperAdmin') && !$admin->hasPermissionTo('manage-projects')) {
            abort(403);
        }

        $task = Task::findOrFail($id);
        $task->delete();

        return response()->json(['message' => '任務已刪除']);
    }

    /**
     * Submit a task for review with evidence.
     */
    public function submitReview(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'evidence_text' => 'nullable|string',
            'evidence_image' => 'required|image|mimes:jpeg,png,webp,jpg|max:5120',
        ]);

        $user = Auth::user();
        $path = $request->file('evidence_image')->store('evidence', 'public');

        $this->taskService->submitPivotReview($id, $user->id, [
            'text' => $validated['evidence_text'],
            'image_path' => $path
        ]);

        return response()->json(['message' => '已提交審核']);
    }

    /**
     * Approve the task for a SPECIFIC user.
     */
    public function approve(Request $request, int $id): TaskResource
    {
        /** @var \App\Models\User $admin */
        $admin = Auth::user();
        $userId = $request->input('user_id'); // 針對哪位使用者進行審核

        if (!$admin->hasRole('SuperAdmin') && !$admin->hasPermissionTo('manage-projects')) {
            abort(403);
        }

        $task = $this->taskService->approveTask($id, $admin, (int)$userId);

        return new TaskResource($task);
    }

    /**
     * Reject the task for a SPECIFIC user.
     */
    public function reject(Request $request, int $id): TaskResource
    {
        $userId = $request->input('user_id');
        $task = $this->taskService->rejectTask($id, (int)$userId);
        return new TaskResource($task);
    }
}
