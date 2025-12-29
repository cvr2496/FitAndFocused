<?php

use App\Http\Controllers\WorkoutUploadController;
use App\Http\Controllers\WorkoutController;
use Illuminate\Support\Facades\Auth;
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
    // If user is authenticated, show home page with workout data
    if (Auth::check()) {
        $userId = Auth::id();
        
        // Fetch recent workouts (last 5)
        $recentWorkouts = \App\Models\Workout::where('user_id', $userId)
            ->with('sets')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($workout) {
                // Calculate total volume for each workout
                $totalVolume = $workout->sets->sum(function ($set) {
                    return ($set->weight ?? 0) * ($set->reps ?? 0);
                });
                
                return [
                    'id' => $workout->id,
                    'date' => $workout->date->format('Y-m-d'),
                    'title' => $workout->title,
                    'total_exercises' => $workout->sets->pluck('exercise_name')->unique()->count(),
                    'total_volume' => $totalVolume,
                ];
            });

        // Calculate stats (mock data for now - TODO: implement real calculations)
        // TODO: Calculate actual weekly workouts
        // TODO: Calculate actual streak
        // TODO: Calculate actual total volume
        $stats = [
            'weeklyWorkouts' => 3, // Mock data
            'streak' => 5, // Mock data
            'totalVolume' => 12450, // Mock data
        ];

        return Inertia::render('home', [
            'recentWorkouts' => $recentWorkouts,
            'stats' => $stats,
        ]);
    }
    
    // For guests, show the welcome page
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
