<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ColaboradorController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\VacationBalanceController;
use App\Http\Controllers\Api\VacationController;
use Illuminate\Support\Facades\Route;

// Rutas compatibles con clientes viejos que aun apuntan a /api/login.
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

Route::get('/employee-info', [EmployeeController::class, 'show']);
Route::get('/areas', [EmployeeController::class, 'areas']);
Route::get('/download-template', [EmployeeController::class, 'template']);
Route::get('/colaboradores', [ColaboradorController::class, 'index']);
Route::post('/vacations', [VacationController::class, 'store']);
Route::get('/vacations/{vacation}/pdf', [VacationController::class, 'pdf']);
Route::get('/vacations/{vacation}/attachment', [VacationController::class, 'attachment']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    Route::get('/vacations', [VacationController::class, 'index']);
    Route::get('/vacations/{vacation}', [VacationController::class, 'show']);
    Route::patch('/vacations/{vacation}/status', [VacationController::class, 'updateStatus'])
        ->middleware('role:Administrador,Supervisor');

    Route::middleware('role:Administrador')->group(function (): void {
        Route::put('/vacations/{vacation}', [VacationController::class, 'update']);
        Route::delete('/vacations/{vacation}', [VacationController::class, 'destroy']);

        Route::get('/roles', [UserManagementController::class, 'roles']);
        Route::get('/users', [UserManagementController::class, 'index']);
        Route::post('/users', [UserManagementController::class, 'store']);
        Route::put('/users/{user}', [UserManagementController::class, 'update']);
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy']);

        Route::post('/colaboradores', [ColaboradorController::class, 'store']);
        Route::put('/colaboradores/{documento}', [ColaboradorController::class, 'update']);
        Route::delete('/colaboradores/{documento}', [ColaboradorController::class, 'destroy']);

        Route::get('/vacation-balances', [VacationBalanceController::class, 'index']);
        Route::post('/vacation-balances/monthly-accrual', [VacationBalanceController::class, 'applyMonthlyAccrual']);
        Route::post('/vacation-balances/taken', [VacationBalanceController::class, 'registerTaken']);
    });
});
