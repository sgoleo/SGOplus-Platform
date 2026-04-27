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
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isAdmin = $user->hasRole('SuperAdmin') || $user->hasPermissionTo('manage-projects');

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
            'type' => 'nullable|in:official,personal',
        ]);

        $type = $validated['type'] ?? 'official';

        if (!$isAdmin) {
            $type = 'personal';
            $rewardPoints = (int) ($request->input('reward_points') ?? 0);
            if ($rewardPoints > 1) {
                abort(403, '個人任務獎勵點數最高為 1 點');
            }
            
            $todayPoints = $this->taskService->getTodayPersonalPoints($user);
            if ($todayPoints + $rewardPoints > 3) {
                abort(403, "每日個人任務點數上限為 3 點。您今日已獲得 {$todayPoints} 點。");
            }
        }

        $validated['type'] = $type;
        $validated['is_crowdsourced'] = filter_var($request->input('is_crowdsourced'), FILTER_VALIDATE_BOOLEAN);
        $validated['max_assignees'] = (int) ($request->input('max_assignees') ?? 1);
        $validated['reward_points'] = (int) ($request->input('reward_points') ?? 0);
        $validated['creator_id'] = $user->id;

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
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        $targetUserId = (int) $request->input('user_id');
        $task = Task::findOrFail($id);

        $isSelfApproveForPersonal = ($task->type === 'personal' && $task->creator_id === $currentUser->id && $targetUserId === $currentUser->id);

        if (!$isSelfApproveForPersonal && !$currentUser->hasRole('SuperAdmin') && !$currentUser->can('manage-projects')) {
            abort(403);
        }

        $task = $this->taskService->approveTask($id, $currentUser, $targetUserId);

        return new TaskResource($task);
    }

    /**
     * Reject the task for a SPECIFIC user.
     */
    /**
     * Reject the task for a SPECIFIC user.
     */
    public function reject(Request $request, int $id): TaskResource
    {
        $userId = $request->input('user_id');
        $task = $this->taskService->rejectTask($id, (int)$userId);
        return new TaskResource($task);
    }

    /**
     * Get today's personal task points for the current user.
     */
    public function todayPersonalPoints(): JsonResponse
    {
        $user = Auth::user();
        $points = $this->taskService->getTodayPersonalPoints($user);
        return response()->json(['points' => $points, 'limit' => 3]);
    }
}
