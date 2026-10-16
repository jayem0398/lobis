<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Inertia\Inertia;

Broadcast::routes(['middleware' => ['web']]);

// Debug Route (Remove or comment out in production)
Route::get('/sso-debug', function () {
    return response()->json([
        'authenticated' => Auth::check(),
        'user' => Auth::user(),
        'session_id' => session()->getId(),
        'cookie_domain' => config('session.domain'),
    ]);
});

// SSO Redirect Helper Logic
$redirectToLodis = function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    $lodisLoginUrl = 'https://lodis.lendell-local.xyz/?' . http_build_query([
        'system' => 'lobis',
        'redirect_to' => route('dashboard'),
    ]);

    return redirect()->away($lodisLoginUrl);
};

// Root & Login routes both redirect unauthenticated users to LODIS
Route::get('/', $redirectToLodis);
Route::get('/login', $redirectToLodis)->name('login');

// Protected Routes (Requires active SSO authentication)
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard/Index');
    })->name('dashboard');

    Route::get('/invoices', function () {
        return Inertia::render('Invoices/Index');
    });

    Route::get('/invoices/create', function () {
        return Inertia::render('Invoices/Create');
    });

    Route::get('/clients', function () {
        return Inertia::render('Clients/Index');
    });

    Route::get('/teams', function () {
        return Inertia::render('Teams/Index');
    });

    Route::get('/users', function () {
        return Inertia::render('Users/Index');
    });

    Route::get('/reports', function () {
        return Inertia::render('Reports/Index');
    });

    Route::get('/settings', function () {
        return Inertia::render('Settings/Index');
    });

    Route::get('/activity-logs', function () {
        return Inertia::render('ActivityLogs/Index');
    });

});