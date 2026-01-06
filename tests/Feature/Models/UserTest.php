<?php

use App\Models\User;
use App\Models\Workout;

test('user has workouts relationship', function () {
    $user = actingAsDemo();
    
    expect($user->workouts)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($user->workouts()->count())->toBeGreaterThan(0);
});

test('user can have multiple workouts', function () {
    $user = actingAsDemo();
    
    $workoutCount = $user->workouts()->count();
    expect($workoutCount)->toBe(15); // Demo user has 15 workouts
});

test('user email is verified', function () {
    $user = actingAsDemo();
    
    expect($user->email_verified_at)->not->toBeNull();
});

test('user has correct email', function () {
    $user = actingAsDemo();
    
    expect($user->email)->toBe('demo@fitandfocused.com');
});

test('deleting user cascades to workouts', function () {
    seedDemo();
    $user = User::where('email', 'demo@fitandfocused.com')->first();
    $userId = $user->id;
    
    $workoutCountBefore = Workout::where('user_id', $userId)->count();
    expect($workoutCountBefore)->toBeGreaterThan(0);
    
    $user->delete();
    
    $workoutCountAfter = Workout::where('user_id', $userId)->count();
    expect($workoutCountAfter)->toBe(0);
});

