<?php

use Illuminate\Support\Facades\Storage;

test('can retrieve workout photo', function () {
    $user = actingAsDemo();
    $workout = $user->workouts()->first();
    
    // Ensure the photo exists
    expect(Storage::disk('public')->exists($workout->photo_path))->toBeTrue();
    
    // The route expects path after 'uploads/original/'
    $pathParam = str_replace('uploads/original/', '', $workout->photo_path);
    $response = $this->get(route('api.workouts.photo', ['path' => $pathParam]));
    
    $response->assertStatus(200);
    $response->assertHeader('content-type', 'image/jpeg');
});

test('returns 404 for non-existent photo', function () {
    $user = actingAsDemo();
    
    $response = $this->get(route('api.workouts.photo', ['path' => 'non-existent.jpg']));
    
    $response->assertStatus(404);
});

test('photo retrieval requires authentication', function () {
    $response = $this->get(route('api.workouts.photo', ['path' => 'demo-workout-01.jpg']));
    
    $response->assertRedirect(route('login'));
});

