<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TaskPoolController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/user/change-password', [AuthController::class, 'changePassword']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/user/personal-points', [TaskController::class, 'todayPersonalPoints']);

    Route::get('/tasks/pending-reviews', [TaskController::class, 'pendingReviews']);
    Route::apiResource('tasks', TaskController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/tasks-pool', [TaskPoolController::class, 'index']);
    Route::post('/tasks/{task}/claim', [TaskPoolController::class, 'claim']);
    
    Route::post('/tasks/{task}/submit-review', [TaskController::class, 'submitReview']);
    Route::post('/tasks/{task}/approve', [TaskController::class, 'approve']);
    Route::post('/tasks/{task}/reject', [TaskController::class, 'reject']);
    
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('departments', DepartmentController::class);

    // User Management (Admin Only)
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/roles', [UserController::class, 'roles']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    Route::post('/users/{user}/adjust-points', [UserController::class, 'adjustPoints']);

    // Role & Permission Management (Admin Only)
    Route::get('/roles-detailed', [RoleController::class, 'index']);
    Route::get('/permissions', [RoleController::class, 'permissions']);
    Route::apiResource('roles', RoleController::class)->except(['index']);
});
