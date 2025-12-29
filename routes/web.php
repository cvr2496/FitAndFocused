<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Temporary public route for testing upload page
Route::get('test-upload', function () {
    return Inertia::render('workouts/upload-standalone');
})->name('test.upload');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Workout routes
    Route::get('workouts/upload', function () {
        return Inertia::render('workouts/upload');
    })->name('workouts.upload');
});

require __DIR__.'/settings.php';
