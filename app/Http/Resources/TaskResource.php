<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $title
 * @property string $details
 * @property string $status
 * @property \Carbon\Carbon $due_date
 * @property \App\Models\Project $project
 */
class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'details' => $this->details,
            'status' => $this->status,
            'reward_points' => $this->reward_points,
            'due_date' => $this->due_date?->toDateTimeString(),
            'project' => [
                'id' => $this->project->id,
                'name' => $this->project->name,
                'department' => $this->project->department,
            ],
        ];
    }
}
