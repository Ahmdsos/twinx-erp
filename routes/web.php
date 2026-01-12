<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return view('welcome');
});

// Admin Dashboard (Inertia)
Route::get('/admin', function () {
    return Inertia::render('Dashboard/Index');
})->name('admin.dashboard');

Route::get('/admin/dashboard', function () {
    return Inertia::render('Dashboard/Index');
})->name('dashboard');

// POS Route
Route::get('/pos', function () {
    return file_get_contents(public_path('pos/index.html'));
});
