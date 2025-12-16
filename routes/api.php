<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/profile', [ProfileController::class, 'show'])->name('api.profile');
    Route::post('/account/fund', [ProfileController::class, 'fund'])->name('api.account.fund');
    Route::post('/logout', [AuthController::class, 'logout']);

    // Activity routes
    Route::get('/activity', [ActivityController::class, 'index'])->name('api.activity.index');

    // Order routes
    Route::get('/orders', [OrderController::class, 'index'])->name('api.orders.index');
    Route::post('/orders', [OrderController::class, 'store'])->name('api.orders.store');
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel'])->name('api.orders.cancel');
    Route::post('/orders/match', [OrderController::class, 'match'])->name('api.orders.match');
});
