<?php

use App\Http\Controllers\WorkoutUploadController;
use App\Http\Controllers\WorkoutController;
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

// API endpoint for workout photo upload (public for testing)
Route::post('api/workouts/upload', [WorkoutUploadController::class, 'upload'])
    ->name('api.workouts.upload');

// API endpoint to save verified workout data
Route::post('api/workouts/save', [WorkoutUploadController::class, 'save'])
    ->name('api.workouts.save');

// Endpoint to retrieve uploaded photos
Route::get('api/workouts/photos/{path}', [WorkoutUploadController::class, 'getPhoto'])
    ->where('path', '.*')
    ->name('api.workouts.photo');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Workout routes
    Route::get('workouts', [WorkoutController::class, 'index'])->name('workouts.index');
    Route::get('workouts/upload', function () {
        return Inertia::render('workouts/upload');
    })->name('workouts.upload');
    Route::get('workouts/{workout}', [WorkoutController::class, 'show'])->name('workouts.show');
});

require __DIR__.'/settings.php';
