<?php

use App\Models\User;
use App\Models\Workout;
use App\Models\Set;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

test('demo user seeder creates user', function () {
    // Clean up first
    $user = User::where('email', 'demo@fitandfocused.com')->first();
    if ($user) {
        $user->delete();
    }
    
    Artisan::call('demo:seed');
    
    $user = User::where('email', 'demo@fitandfocused.com')->first();
    expect($user)->not->toBeNull();
    expect($user->email_verified_at)->not->toBeNull();
});

test('demo user seeder creates 15 workouts', function () {
    seedDemo();
    
    $user = User::where('email', 'demo@fitandfocused.com')->first();
    $workoutCount = Workout::where('user_id', $user->id)->count();
    
    expect($workoutCount)->toBe(15);
});

test('demo user seeder creates correct number of sets', function () {
    seedDemo();
    
    $user = User::where('email', 'demo@fitandfocused.com')->first();
    $setCount = Set::whereHas('workout', function ($query) use ($user) {
        $query->where('user_id', $user->id);
    })->count();
    
    expect($setCount)->toBe(139); // As per demo data JSON
});

test('demo user seeder generates placeholder images', function () {
    seedDemo();
    
    $user = User::where('email', 'demo@fitandfocused.com')->first();
    $workouts = Workout::where('user_id', $user->id)->get();
    
    foreach ($workouts as $workout) {
        expect(Storage::disk('public')->exists($workout->photo_path))->toBeTrue();
    }
});

test('demo user seeder is idempotent', function () {
    seedDemo();
    $user = User::where('email', 'demo@fitandfocused.com')->first();
    $firstWorkoutCount = Workout::where('user_id', $user->id)->count();
    
    // Run seeder again
    seedDemo();
    $user = User::where('email', 'demo@fitandfocused.com')->first();
    $secondWorkoutCount = Workout::where('user_id', $user->id)->count();
    
    expect($secondWorkoutCount)->toBe($firstWorkoutCount);
    expect($secondWorkoutCount)->toBe(15);
});

test('demo user workouts have varied data', function () {
    seedDemo();
    
    $user = User::where('email', 'demo@fitandfocused.com')->first();
    
    // Check for kg units
    $kgSets = Set::whereHas('workout', function ($query) use ($user) {
        $query->where('user_id', $user->id);
    })->where('unit', 'kg')->count();
    
    // Check for lbs units
    $lbsSets = Set::whereHas('workout', function ($query) use ($user) {
        $query->where('user_id', $user->id);
    })->where('unit', 'lbs')->count();
    
    expect($kgSets)->toBeGreaterThan(0);
    expect($lbsSets)->toBeGreaterThan(0);
});

test('demo user workouts span multiple dates', function () {
    seedDemo();
    
    $user = User::where('email', 'demo@fitandfocused.com')->first();
    $workouts = Workout::where('user_id', $user->id)
        ->orderBy('date')
        ->get();
    
    $firstDate = $workouts->first()->date;
    $lastDate = $workouts->last()->date;
    
    expect($firstDate->notEqualTo($lastDate))->toBeTrue();
});

