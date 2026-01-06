<?php

use App\Models\Workout;
use App\Models\Set;
use Inertia\Testing\AssertableInertia as Assert;

test('workout index loads for authenticated user', function () {
    $user = actingAsDemo();
    
    $response = $this->get(route('workouts.index'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => 
        $page->component('workouts/index')
    );
});

test('workout index displays all user workouts', function () {
    $user = actingAsDemo();
    
    $response = $this->get(route('workouts.index'));
    
    $response->assertInertia(fn (Assert $page) => 
        $page->has('workouts')
            ->where('workouts', fn ($workouts) => count($workouts) === 15)
    );
});

test('workout index orders by date descending', function () {
    $user = actingAsDemo();
    
    $response = $this->get(route('workouts.index'));
    
    $response->assertInertia(fn (Assert $page) => 
        $page->has('workouts.0.date')
            ->where('workouts.0.date', '2025-01-06') // Most recent demo workout
    );
});

test('workout show displays workout details', function () {
    $user = actingAsDemo();
    $workout = $user->workouts()->first();
    
    $response = $this->get(route('workouts.show', $workout));
    
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => 
        $page->component('workouts/show')
            ->has('workout')
            ->has('exercises')
    );
});

test('workout show groups sets by exercise', function () {
    $user = actingAsDemo();
    $workout = $user->workouts()->first();
    
    $response = $this->get(route('workouts.show', $workout));
    
    $response->assertInertia(fn (Assert $page) => 
        $page->has('exercises')
            ->has('exercises.0.name')
            ->has('exercises.0.sets')
            ->where('exercises.0.sets', fn ($sets) => count($sets) > 0)
    );
});

test('user cannot view another users workout', function () {
    seedDemo();
    $demoUser = \App\Models\User::where('email', 'demo@fitandfocused.com')->first();
    $demoWorkout = $demoUser->workouts()->first();
    
    // Create and login as different user
    $otherUser = \App\Models\User::factory()->create();
    $this->actingAs($otherUser);
    
    $response = $this->get(route('workouts.show', $demoWorkout));
    
    $response->assertStatus(403);
    
    // Cleanup
    $otherUser->delete();
});

test('workout destroy deletes workout', function () {
    $user = actingAsDemo();
    $workout = $user->workouts()->first();
    $workoutId = $workout->id;
    
    $response = $this->delete(route('workouts.destroy', $workout));
    
    $response->assertRedirect(route('workouts.index'));
    expect(Workout::find($workoutId))->toBeNull();
});

test('workout destroy deletes associated sets', function () {
    $user = actingAsDemo();
    $workout = $user->workouts()->first();
    $workoutId = $workout->id;
    
    $setCountBefore = Set::where('workout_id', $workoutId)->count();
    expect($setCountBefore)->toBeGreaterThan(0);
    
    $this->delete(route('workouts.destroy', $workout));
    
    $setCountAfter = Set::where('workout_id', $workoutId)->count();
    expect($setCountAfter)->toBe(0);
});

test('user cannot delete another users workout', function () {
    seedDemo();
    $demoUser = \App\Models\User::where('email', 'demo@fitandfocused.com')->first();
    $demoWorkout = $demoUser->workouts()->first();
    
    // Create and login as different user
    $otherUser = \App\Models\User::factory()->create();
    $this->actingAs($otherUser);
    
    $response = $this->delete(route('workouts.destroy', $demoWorkout));
    
    $response->assertStatus(403);
    
    // Cleanup
    $otherUser->delete();
});

test('workout index requires authentication', function () {
    $response = $this->get(route('workouts.index'));
    
    $response->assertRedirect(route('login'));
});

