<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PdfController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingController;

Route::middleware('web')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Authenticated API Routes Group (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Auth User
    Route::get('/user', [AuthController::class, 'user']);

    // Dashboard Analytics & Global Search
    Route::get('/dashboard/analytics', [DashboardController::class, 'index']);
    Route::get('/dashboard/metrics', [DashboardController::class, 'index']);
    Route::get('/search', [DashboardController::class, 'search']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/test', [NotificationController::class, 'testNotification']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    // Reports API
    Route::get('/reports/financial', [ReportController::class, 'financial']);
    Route::get('/reports/financial/pdf', [ReportController::class, 'pdf']);

    // Settings API
    Route::get('/settings', [SettingController::class, 'index']);
    Route::put('/settings', [SettingController::class, 'update']);

    // Activity & Audit Logs API (Admin only)
    Route::get('/activity-logs', [\App\Http\Controllers\Api\ActivityLogController::class, 'index']);

    // Invoices Management Routes
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/next-number', [InvoiceController::class, 'nextNumber']);
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::put('/invoices/{id}', [InvoiceController::class, 'update']);
    Route::post('/invoices/{id}/delete-request', [InvoiceController::class, 'deleteRequest']);
    Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy']);

    // PDF Generation Route 
    Route::get('/invoices/{id}/pdf', [PdfController::class, 'downloadInvoicePdf']);

    // Clients Management Routes
    Route::apiResource('clients', ClientController::class);

    // Teams Management Routes
    Route::get('/teams', [TeamController::class, 'index']);
    Route::post('/teams', [TeamController::class, 'store']);
    Route::put('/teams/{id}', [TeamController::class, 'update']);
    Route::delete('/teams/{id}', [TeamController::class, 'destroy']);

    // Users & Roles Management Routes
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::post('/users/check-email', [UserController::class, 'checkEmail']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});