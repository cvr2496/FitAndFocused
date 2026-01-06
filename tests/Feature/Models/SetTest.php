<?php

use App\Models\Set;
use App\Models\Workout;

test('set belongs to workout', function () {
    $user = actingAsDemo();
    $workout = $user->workouts()->first();
    $set = $workout->sets()->first();
    
    expect($set->workout)->toBeInstanceOf(Workout::class);
    expect($set->workout_id)->toBe($workout->id);
});

test('set has required attributes', function () {
    $user = actingAsDemo();
    $workout = $user->workouts()->first();
    $set = $workout->sets()->first();
    
    expect($set->exercise_name)->not->toBeNull();
    expect($set->set_number)->toBeGreaterThan(0);
    expect($set->unit)->toBeIn(['kg', 'lbs']);
});

test('set can have kg unit', function () {
    $user = actingAsDemo();
    
    $kgSets = Set::whereHas('workout', function ($query) use ($user) {
        $query->where('user_id', $user->id);
    })->where('unit', 'kg')->get();
    
    expect($kgSets->count())->toBeGreaterThan(0);
});

test('set can have lbs unit', function () {
    $user = actingAsDemo();
    
    $lbsSets = Set::whereHas('workout', function ($query) use ($user) {
        $query->where('user_id', $user->id);
    })->where('unit', 'lbs')->get();
    
    expect($lbsSets->count())->toBeGreaterThan(0);
});

test('set can have optional notes', function () {
    $user = actingAsDemo();
    
    $setsWithNotes = Set::whereHas('workout', function ($query) use ($user) {
        $query->where('user_id', $user->id);
    })->whereNotNull('notes')->get();
    
    expect($setsWithNotes->count())->toBeGreaterThan(0);
});

test('sets are ordered by set_number', function () {
    $user = actingAsDemo();
    $workout = $user->workouts()->first();
    
    $sets = $workout->sets()->orderBy('set_number')->get();
    
    $previousSetNumber = 0;
    foreach ($sets as $set) {
        expect($set->set_number)->toBeGreaterThan($previousSetNumber);
        $previousSetNumber = $set->set_number;
    }
});

