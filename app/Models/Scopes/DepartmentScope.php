<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class DepartmentScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        // If not logged in or is admin, don't filter (Admin can see everything)
        if (!$user || $user->hasRole('admin')) {
            return;
        }

        // If the user has a department, filter the records by that department
        if ($user->department) {
            $builder->where('department', $user->department);
        }
    }
}
