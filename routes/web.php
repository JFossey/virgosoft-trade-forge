<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Login route - serves SPA (required by auth middleware)
Route::get("/login", function () {
    return view("app");
})->name("login");

// Register route - serves SPA
Route::get("/register", function () {
    return view("app");
})->name("register");

// Backend logout route - performs logout and redirects
Route::get("/logout", function (Request $request) {
    Auth::guard("web")->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect("/login");
})->name("logout");

// Dashboard route - requires authentication
Route::get("/dashboard", function () {
    return view("app");
})
    ->middleware("auth")
    ->name("dashboard");

// Catch all web route, to send any unknown routes to the front-end.
// Vue Router will handle the client-side routing
Route::get("/{any}", function () {
    return view("app");
})->where("any", ".*");
