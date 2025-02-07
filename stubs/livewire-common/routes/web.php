<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'auth::welcome');

Route::view('dashboard', 'auth::dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'auth::profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
