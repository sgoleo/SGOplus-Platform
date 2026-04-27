<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

use App\Models\PointTransaction;
use Illuminate\Support\Facades\DB;

class TaskService
{
    /**
     * Get all tasks that have pending reviews (global view for admins).
     */
    public function getPendingReviews(): Collection
    {
        return Task::with(['project', 'users' => function($q) {
                $q->where('status', 'pending_review'); // 針對公海任務
            }])
            ->where(function($q) {
                $q->where('status', 'Review') // 針對普通任務
                  ->orWhereHas('users', function($uq) {
                      $uq->where('status', 'pending_review');
                  });
            })
            ->get();
    }

    /**
     * Get filtered tasks based on user role and department.
     */
    public function getTasksForUser(User $user, array $filters = []): Collection
    {
        $isAdmin = $user->hasAnyRole(['admin', 'Admin', 'SuperAdmin']) || $user->can('manage-projects');
        $isSuperAdmin = $user->hasRole('SuperAdmin');
        
        $query = Task::with(['project', 'users' => function($q) use ($user, $isAdmin) {
            if (!$isAdmin) {
                $q->where('user_id', $user->id);
            }
        }]);

        // 如果請求全域清單且是管理員，直接跳過過濾
        if (!empty($filters['all']) && $isAdmin) {
            return $query->withoutGlobalScopes()->get();
        }

        // 核心邏輯修正：
        $query->where(function($q) use ($user, $isAdmin, $isSuperAdmin) {
            // 1. 分配給我的 (無論何種角色，只要參與了就得看到)
            $q->whereHas('users', function($uq) use ($user) {
                $uq->where('user_id', $user->id);
            });

            // 2. 管理員權限擴充：
            if ($isAdmin) {
                $q->orWhere(function($sub) use ($user, $isSuperAdmin) {
                    // 如果是 SuperAdmin，則不需要部門過濾 (看到所有任務)
                    if (!$isSuperAdmin) {
                        $sub->where('department', $user->department)
                           ->orWhere('department', 'Public')
                           ->orWhere('department', 'public')
                           ->orWhereNull('department');
                    } else {
                        // SuperAdmin 本身就能看到全部，不需要額外條件
                        // 但為了讓 orWhere 生效，我們這裡直接給個 true 條件 (在 Laravel 中可以用 whereRaw('1=1'))
                        $sub->whereRaw('1=1');
                    }
                });
            }
        });

        // 移除原有的外部 department 限制，避免過濾掉已接取的跨部門公海任務

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Approve a specific user's work on a task.
     */
    public function approveTask(int $taskId, User $approver, int $userId): Task
    {
        return DB::transaction(function () use ($taskId, $approver, $userId) {
            $task = Task::findOrFail($taskId);
            $user = User::findOrFail($userId);
            
            // 更新中介表狀態
            $task->users()->updateExistingPivot($userId, [
                'status' => 'completed',
                'points_awarded' => $task->reward_points
            ]);

            // 發放點數給該使用者
            $user->increment('points', $task->reward_points);

            // 紀錄交易
            PointTransaction::create([
                'user_id' => $user->id,
                'task_id' => $task->id,
                'points_awarded' => $task->reward_points,
                'description' => "完成公海任務獎勵：{$task->title}",
            ]);

            // 如果不是公海任務，主狀態也改為 Done
            if (!$task->is_crowdsourced) {
                $task->update(['status' => 'Done']);
            }

            return $task;
        });
    }

    /**
     * Reject a specific user's work on a task.
     */
    public function rejectTask(int $taskId, int $userId): Task
    {
        $task = Task::findOrFail($taskId);
        $task->users()->updateExistingPivot($userId, [
            'status' => 'in_progress'
        ]);
        
        if (!$task->is_crowdsourced) {
            $task->update(['status' => 'In Progress']);
        }

        return $task;
    }

    /**
     * Submit evidence to pivot table.
     */
    public function submitPivotReview(int $taskId, int $userId, array $data): void
    {
        $task = Task::findOrFail($taskId);
        $task->users()->updateExistingPivot($userId, [
            'status' => 'pending_review',
            'evidence_image_path' => $data['image_path'] ?? null,
            'evidence_text' => $data['text'] ?? null,
        ]);

        if (!$task->is_crowdsourced) {
            $task->status = 'Review';
            $task->save();
        }
    }

    /**
     * Get total points earned from personal tasks today.
     */
    public function getTodayPersonalPoints(User $user): int
    {
        return (int) Task::where('type', 'personal')
            ->whereHas('users', function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->whereDate('task_user.created_at', now()->toDateString());
            })
            ->join('task_user', 'tasks.id', '=', 'task_user.task_id')
            ->where('task_user.user_id', $user->id)
            ->whereDate('task_user.created_at', now()->toDateString())
            ->sum('task_user.points_awarded');
    }

    /**
     * Create a new task.
     */
    public function createTask(array $data): Task
    {
        /** @var User $user */
        $user = Auth::user();
        
        // If personal task, assign creator
        if (($data['type'] ?? 'official') === 'personal') {
            $data['creator_id'] = $user->id;
            // Personal tasks are automatically assigned to the creator
            $task = Task::create($data);
            $task->users()->attach($user->id, ['status' => 'in_progress']);
            return $task;
        }

        return Task::create($data);
    }

    /**
     * Update task status.
     */
    public function updateTaskStatus(int $taskId, string $status): Task
    {
        $task = Task::findOrFail($taskId);
        $task->update(['status' => $status]);
        return $task;
    }

    /**
     * Apply extra filters to the query.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }
    }
}
