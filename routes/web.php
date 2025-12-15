<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

// Home route - redirect authenticated users to dashboard
Route::get('/', [PageController::class, 'home'])
    ->middleware('guest')
    ->name('home');

// Login route - serves SPA (guest middleware redirects authenticated users)
Route::get('/login', [PageController::class, 'login'])
    ->middleware('guest')
    ->name('login');

// Register route - serves SPA (guest middleware redirects authenticated users)
Route::get('/register', [PageController::class, 'register'])
    ->middleware('guest')
    ->name('register');

// Dashboard route - requires authentication
Route::get('/dashboard', [PageController::class, 'dashboard'])
    ->middleware('auth')
    ->name('dashboard');

// Trade route - requires authentication
Route::get('/trade', [PageController::class, 'trade'])
    ->middleware('auth')
    ->name('trade');

// Fund Account route - requires authentication
Route::get('/account/fund', [PageController::class, 'fundAccount'])
    ->middleware('auth')
    ->name('account.fund');

// Backend logout route - performs logout and redirects
Route::get('/logout', [PageController::class, 'logout'])->name('logout');
