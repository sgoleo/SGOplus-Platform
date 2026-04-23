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
     * Get filtered tasks based on user role and department.
     */
    public function getTasksForUser(User $user, array $filters = []): Collection
    {
        $query = Task::with(['project', 'users']);

        // Note: The DepartmentScope already handles filtering by department 
        // for non-admin users in the Task model booted() method.
        
        // If user is admin, we might want to bypass all scopes to see everything
        if ($user->hasRole('admin') || $user->hasRole('SuperAdmin')) {
            $query->withoutGlobalScopes();
        }

        // Apply additional filters
        $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Approve a task and award points using a transaction.
     */
    public function approveTask(int $taskId, User $approver): Task
    {
        return DB::transaction(function () use ($taskId, $approver) {
            $task = Task::findOrFail($taskId);
            
            if ($task->status === 'Done') {
                throw new \Exception('此任務已完成。');
            }

            // 1. Update Task Status
            $task->update(['status' => 'Done']);

            // 2. Award Points to all assigned users
            foreach ($task->users as $user) {
                $points = $task->reward_points ?? 0;
                
                if ($points > 0) {
                    $user->increment('points', $points);

                    // 3. Record Transaction
                    PointTransaction::create([
                        'user_id' => $user->id,
                        'task_id' => $task->id,
                        'points_awarded' => $points,
                        'description' => "完成任務獎勵：{$task->title}",
                    ]);
                }
            }

            return $task;
        });
    }

    /**
     * Reject a task back to In Progress.
     */
    public function rejectTask(int $taskId): Task
    {
        $task = Task::findOrFail($taskId);
        $task->update(['status' => 'In Progress']);
        return $task;
    }

    /**
     * Create a new task.
     */
    public function createTask(array $data): Task
    {
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
