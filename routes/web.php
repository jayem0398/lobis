<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Auth/Login');
});

Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard/Index');
});

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