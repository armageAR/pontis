<?php

use App\Http\Controllers\Admin\WorkshopController;
use App\Http\Controllers\Admin\WorkshopSyncController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::get('/workshops/search', [AuthController::class, 'searchWorkshops']);

Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->name('verification.verify');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::post('/workshops/{workshop}/dismiss-notification', [WorkshopController::class, 'dismissNotification']);

    Route::get('/account-status',         [AuthController::class, 'accountStatus']);
    Route::post('/email/resend-verification', [AuthController::class, 'resendVerification']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/my-workshops', [UserController::class, 'myWorkshops']);
    Route::patch('/users/{user}', [UserController::class, 'update']);
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus']);
    Route::patch('/users/{user}/password', [UserController::class, 'updatePassword']);
    Route::post('/users/{user}/workshops/{workshop}', [UserController::class, 'addWorkshop']);
    Route::patch('/users/{user}/workshops/{workshop}', [UserController::class, 'updateWorkshopRole']);
    Route::delete('/users/{user}/workshops/{workshop}', [UserController::class, 'removeWorkshop']);

    Route::prefix('admin')->group(function () {
        Route::get('/workshops', [WorkshopController::class, 'index']);
        Route::post('/workshops', [WorkshopController::class, 'store']);
        Route::get('/workshops/{workshop}', [WorkshopController::class, 'show']);
        Route::patch('/workshops/{workshop}', [WorkshopController::class, 'update']);
        Route::delete('/workshops/{workshop}', [WorkshopController::class, 'destroy']);

        Route::post('/workshops/{workshop}/disable', [WorkshopController::class, 'disable']);
        Route::post('/workshops/{workshop}/enable', [WorkshopController::class, 'enable']);
        Route::post('/workshops/{workshop}/join', [WorkshopController::class, 'join']);
        Route::delete('/workshops/{workshop}/leave', [WorkshopController::class, 'leave']);
        Route::post('/workshops/{workshop}/join-requests/{user}/approve', [WorkshopController::class, 'approveJoinRequest']);
        Route::post('/workshops/{workshop}/join-requests/{user}/reject', [WorkshopController::class, 'rejectJoinRequest']);

        Route::post('/workshops/gla/preview', [WorkshopSyncController::class, 'preview']);
        Route::post('/workshops/gla/apply',   [WorkshopSyncController::class, 'apply']);

        Route::get('/workshops/{workshop}/users', [WorkshopController::class, 'users']);
        Route::post('/workshops/{workshop}/users', [WorkshopController::class, 'assignUsers']);
        Route::delete('/workshops/{workshop}/users', [WorkshopController::class, 'removeUsers']);
    });
});
