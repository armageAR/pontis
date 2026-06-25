<?php

use App\Http\Controllers\Admin\WorkshopController;
use App\Http\Controllers\AuthController;
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

    Route::get('/account-status',         [AuthController::class, 'accountStatus']);
    Route::post('/email/resend-verification', [AuthController::class, 'resendVerification']);

    Route::get('/users', [UserController::class, 'index']);
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus']);

    Route::prefix('admin')->group(function () {
        Route::get('/workshops', [WorkshopController::class, 'index']);
        Route::post('/workshops', [WorkshopController::class, 'store']);
        Route::get('/workshops/{workshop}', [WorkshopController::class, 'show']);
        Route::patch('/workshops/{workshop}', [WorkshopController::class, 'update']);
        Route::delete('/workshops/{workshop}', [WorkshopController::class, 'destroy']);

        Route::post('/workshops/{workshop}/disable', [WorkshopController::class, 'disable']);
        Route::post('/workshops/{workshop}/enable', [WorkshopController::class, 'enable']);

        Route::get('/workshops/{workshop}/users', [WorkshopController::class, 'users']);
        Route::post('/workshops/{workshop}/users', [WorkshopController::class, 'assignUsers']);
        Route::delete('/workshops/{workshop}/users', [WorkshopController::class, 'removeUsers']);
    });
});
