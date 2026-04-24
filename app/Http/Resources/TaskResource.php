<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

/**
 * @property int $id
 * @property string $title
 * @property string $details
 * @property string $status
 * @property int $reward_points
 * @property string|null $evidence_image_path
 * @property string|null $evidence_text
 * @property bool $is_crowdsourced
 * @property int $max_assignees
 * @property \Carbon\Carbon $due_date
 * @property \App\Models\Project $project
 * @property \Illuminate\Database\Eloquent\Collection $users
 */
class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'details' => $this->details,
            'status' => $this->status,
            // 個人化狀態：優先讀取目前登入者的中介表進度
            'current_user_status' => $this->users->where('id', Auth::id())->first()?->pivot?->status ?? $this->status,
            'reward_points' => (int) $this->reward_points,
            'is_crowdsourced' => (bool) $this->is_crowdsourced,
            'max_assignees' => (int) $this->max_assignees,
            'assignees_count' => $this->users->count(),
            'due_date' => $this->due_date?->toDateTimeString(),
            'evidence_image_url' => $this->evidence_image_path ? Storage::url($this->evidence_image_path) : null,
            'evidence_text' => $this->evidence_text,
            'project' => [
                'id' => $this->project->id,
                'name' => $this->project->name,
                'department' => $this->project->department,
            ],
            // 包含所有申請者（供管理員審核）
            'assignees' => $this->users->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'pivot_status' => $user->pivot->status,
                    'pivot_evidence_text' => $user->pivot->evidence_text,
                    'pivot_evidence_image_url' => $user->pivot->evidence_image_path ? Storage::url($user->pivot->evidence_image_path) : null,
                    'pivot_points_awarded' => $user->pivot->points_awarded,
                ];
            }),
        ];
    }
}
