<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\ShoppingListController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\BirthdayController;
use App\Http\Controllers\DashboardController;

// Auth (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

// Email verification
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->name('verification.verify');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/email/verification-notification', [VerificationController::class, 'sendVerificationEmail']);

    // Family setup
    Route::post('/family/create', [FamilyController::class, 'create']);
    Route::post('/family/join', [FamilyController::class, 'join']);

    // Family required
    Route::middleware('has.family')->group(function () {
        Route::get('/family/current', [FamilyController::class, 'current']);
        Route::post('/family/leave', [FamilyController::class, 'leave']);

        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Shopping
        Route::get('/shopping', [ShoppingListController::class, 'index']);
        Route::get('/shopping/search', [ShoppingListController::class, 'search']);
        Route::post('/shopping/add', [ShoppingListController::class, 'add']);
        Route::post('/shopping/remove/{id}', [ShoppingListController::class, 'remove']);

        // Calendar
        Route::apiResource('calendar/events', CalendarController::class);

        // Tasks
        Route::get('/tasks', [TaskController::class, 'index']);
        Route::post('/tasks', [TaskController::class, 'store']);
        Route::put('/tasks/{id}/complete', [TaskController::class, 'complete']);
        Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);

        // Birthdays
        Route::apiResource('birthdays', BirthdayController::class);
    });
});
