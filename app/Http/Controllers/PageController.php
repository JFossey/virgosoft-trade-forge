<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class PageController extends Controller
{
    /**
     * Show the home page
     * Redirect to dashboard if authenticated
     */
    public function home()
    {
        return view('app');
    }

    /**
     * Show the login page
     * Uses guest middleware to redirect authenticated users
     */
    public function login()
    {
        return view('app');
    }

    /**
     * Show the register page
     * Uses guest middleware to redirect authenticated users
     */
    public function register()
    {
        return view('app');
    }

    /**
     * Show the dashboard page
     * Uses auth middleware to protect this route
     */
    public function dashboard()
    {
        return view('app');
    }

    /**
     * Backend logout route
     * Performs logout and redirects to login
     */
    public function logout()
    {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Backend trade route
     * Uses auth middleware to protect this route
     */
    public function trade()
    {
        return view('app');
    }

    /**
     * Show the fund account page
     * Uses auth middleware to protect this route
     */
    public function fundAccount()
    {
        return view('app');
    }
}
