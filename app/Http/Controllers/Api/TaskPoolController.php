<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskPoolController extends Controller
{
    /**
     * Get all available crowdsourced tasks.
     */
    public function index(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 獲取公海任務：is_crowdsourced = true 且名額未滿 且 我還沒接過
        $query = Task::withCount('users')
            ->where('is_crowdsourced', true)
            ->whereDoesntHave('users', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with('project');

        // RBAC: 如果不是 Admin，則過濾部門
        if (!$user->hasRole('SuperAdmin')) {
            $query->where(function($q) use ($user) {
                $q->where('department', $user->department)
                  ->orWhere('department', 'Public')    // 支援 Public 標籤
                  ->orWhere('department', 'public')    // 支援小寫
                  ->orWhereNull('department');         // 支援未分類任務
            });
        }

        $tasks = $query->get()->filter(function ($task) {
            return $task->users_count < $task->max_assignees;
        });

        return response()->json($tasks->values());
    }

    /**
     * Claim a task from the pool.
     */
    public function claim(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return DB::transaction(function () use ($id, $user) {
            // 使用悲觀鎖防止名額超收
            $task = Task::where('id', $id)->lockForUpdate()->firstOrFail();

            if (!$task->is_crowdsourced) {
                return response()->json(['message' => '此任務非公海任務'], 400);
            }

            // 檢查是否已經接過
            if ($task->users()->where('user_id', $user->id)->exists()) {
                return response()->json(['message' => '您已經接取過此任務'], 400);
            }

            // 檢查名額
            if ($task->users()->count() >= $task->max_assignees) {
                return response()->json(['message' => '任務名額已滿'], 400);
            }

            // 接取成功，寫入中介表
            $task->users()->attach($user->id, [
                'status' => 'in_progress',
                'points_awarded' => 0
            ]);

            return response()->json(['message' => '任務接取成功', 'task_id' => $id]);
        });
    }
}
