<?php

use App\Http\Controllers\WorkoutUploadController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Root - redirect based on auth status
Route::get('/', function () {
    return redirect()->route(Auth::check() ? 'home' : 'login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Home screen (main dashboard)
    Route::get('home', [HomeController::class, 'index'])->name('home');
    
    // Redirect old dashboard to home
    Route::redirect('dashboard', '/home')->name('dashboard');

    // Workout upload and management routes
    Route::get('workouts', [WorkoutController::class, 'index'])->name('workouts.index');
    Route::get('workouts/upload', function () {
        return Inertia::render('workouts/upload');
    })->name('workouts.upload');
    Route::get('workouts/verify', [WorkoutUploadController::class, 'showVerify'])->name('workouts.verify');
    Route::get('workouts/{workout}', [WorkoutController::class, 'show'])->name('workouts.show');
    Route::delete('workouts/{workout}', [WorkoutController::class, 'destroy'])->name('workouts.destroy');
    
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
