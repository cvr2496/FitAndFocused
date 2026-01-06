<?php

use App\Models\Workout;
use App\Models\Set;
use App\Models\User;

test('workout belongs to user', function () {
    $user = actingAsDemo();
    $workout = $user->workouts()->first();
    
    expect($workout->user)->toBeInstanceOf(User::class);
    expect($workout->user_id)->toBe($user->id);
});

test('workout has many sets', function () {
    $user = actingAsDemo();
    $workout = $user->workouts()->first();
    
    expect($workout->sets)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($workout->sets()->count())->toBeGreaterThan(0);
});

test('workout date is cast to carbon instance', function () {
    $user = actingAsDemo();
    $workout = $user->workouts()->first();
    
    expect($workout->date)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('workout has required attributes', function () {
    $user = actingAsDemo();
    $workout = $user->workouts()->first();
    
    expect($workout->title)->not->toBeNull();
    expect($workout->date)->not->toBeNull();
    expect($workout->photo_path)->not->toBeNull();
});

test('deleting workout cascades to sets', function () {
    $user = actingAsDemo();
    $workout = $user->workouts()->first();
    $workoutId = $workout->id;
    
    $setCountBefore = Set::where('workout_id', $workoutId)->count();
    expect($setCountBefore)->toBeGreaterThan(0);
    
    $workout->delete();
    
    $setCountAfter = Set::where('workout_id', $workoutId)->count();
    expect($setCountAfter)->toBe(0);
});

test('can create workout with sets', function () {
    $user = actingAsDemo();
    
    $workout = Workout::create([
        'user_id' => $user->id,
        'date' => now(),
        'title' => 'Test Workout',
        'photo_path' => 'test/path.jpg',
    ]);
    
    Set::create([
        'workout_id' => $workout->id,
        'exercise_name' => 'Bench Press',
        'set_number' => 1,
        'reps' => 10,
        'weight' => 60,
        'unit' => 'kg',
    ]);
    
    $workout->refresh();
    expect($workout->sets()->count())->toBe(1);
    
    // Cleanup
    $workout->delete();
});

