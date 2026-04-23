<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\DepartmentController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('tasks', TaskController::class)->only(['index', 'store', 'update']);
    Route::post('/tasks/{task}/approve', [TaskController::class, 'approve']);
    Route::post('/tasks/{task}/reject', [TaskController::class, 'reject']);
    Route::apiResource('projects', ProjectController::class)->only(['index', 'store']);
    Route::apiResource('departments', DepartmentController::class);
});
