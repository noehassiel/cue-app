<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\DebtController;
use App\Http\Controllers\Api\V1\FundController;
use App\Http\Controllers\Api\V1\RecurringTemplateController;
use App\Http\Controllers\Api\V1\ReportController;
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

    // Billing routes (no plan limits check)
    Route::middleware('auth:sanctum')->prefix('billing')->name('billing.')->group(function () {
        Route::get('plans', [BillingController::class, 'plans'])->name('plans');
        Route::get('status', [BillingController::class, 'status'])->name('status');
        Route::post('checkout', [BillingController::class, 'checkout'])->name('checkout');
        Route::get('portal', [BillingController::class, 'portal'])->name('portal');
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

            // Funds
            Route::apiResource('funds', FundController::class);
            Route::post('funds/{fund}/deposit', [FundController::class, 'deposit']);
            Route::post('funds/{fund}/withdraw', [FundController::class, 'withdraw']);
            Route::get('funds/{fund}/movements', [FundController::class, 'movements']);

            // Debts
            Route::get('debts/upcoming', [DebtController::class, 'upcoming']);
            Route::apiResource('debts', DebtController::class);
            Route::patch('debts/{debt}/installments/{installment}/pay', [DebtController::class, 'payInstallment']);

            // Reports
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('monthly', [ReportController::class, 'monthly'])->name('monthly');
                Route::get('projection', [ReportController::class, 'projection'])->name('projection');
                Route::get('export/csv', [ReportController::class, 'exportCsv'])->name('export.csv');
                Route::get('export/xlsx', [ReportController::class, 'exportXlsx'])->name('export.xlsx');
            });

            // Import
            Route::post('transactions/import', [TransactionController::class, 'import'])->name('transactions.import');
        });
    });
});
