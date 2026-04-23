<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Scopes\DepartmentScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

#[ScopedBy([DepartmentScope::class])]
class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'department',
        'title',
        'details',
        'status',
        'due_date',
        'reward_points',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new DepartmentScope);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'datetime',
    ];

    /**
     * Get the project that owns the task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * The users assigned to the task (owners and collaborators).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user')
            ->withTimestamps();
    }
}
