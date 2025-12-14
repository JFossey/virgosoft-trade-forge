<?php

use Illuminate\Support\Facades\Route;

// Serve the Vue SPA for all routes except API routes
// Vue Router will handle the client-side routing
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
