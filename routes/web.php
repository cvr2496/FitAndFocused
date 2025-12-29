<?php

use App\Http\Controllers\WorkoutUploadController;
use App\Http\Controllers\WorkoutController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'app_debug' => config('app.debug'),
        'app_key_set' => !empty(config('app.key')),
        'db_connection' => config('database.default'),
    ]);
});

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Workout upload and management routes
    Route::get('workouts', [WorkoutController::class, 'index'])->name('workouts.index');
    Route::get('workouts/upload', function () {
        return Inertia::render('workouts/upload');
    })->name('workouts.upload');
    Route::get('workouts/{workout}', [WorkoutController::class, 'show'])->name('workouts.show');
    
    // API endpoints for workout operations
    Route::post('api/workouts/upload', [WorkoutUploadController::class, 'upload'])
        ->name('api.workouts.upload');
    Route::post('api/workouts/save', [WorkoutUploadController::class, 'save'])
        ->name('api.workouts.save');
    Route::get('api/workouts/photos/{path}', [WorkoutUploadController::class, 'getPhoto'])
        ->where('path', '.*')
        ->name('api.workouts.photo');
});

require __DIR__.'/settings.php';
