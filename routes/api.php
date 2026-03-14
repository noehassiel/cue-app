<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RecurringTemplateController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });

    // Protected routes
    Route::middleware(['auth:sanctum', 'check.plan.limits'])->group(function () {

        Route::get('user', [AuthController::class, 'user']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // Workspaces
        Route::apiResource('workspaces', WorkspaceController::class);
        Route::get('workspaces/{workspace}/dashboard', [WorkspaceController::class, 'dashboard']);
        Route::get('workspaces/{workspace}/cashflow', [WorkspaceController::class, 'cashflow']);

        // Workspace-scoped resources
        Route::prefix('workspaces/{workspace}')->group(function () {

            // Transactions
            Route::apiResource('transactions', TransactionController::class);
            Route::patch('transactions/{transaction}/confirm', [TransactionController::class, 'confirm']);

            // Recurring templates
            Route::apiResource('recurring-templates', RecurringTemplateController::class);
        });
    });
});
