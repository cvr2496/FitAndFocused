<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @method \Illuminate\Testing\TestResponse get(string $uri, array $headers = [])
 * @method \Illuminate\Testing\TestResponse post(string $uri, array $data = [], array $headers = [])
 */

test('upload page loads for authenticated user', function () {
    $user = actingAsDemo();
    
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $response = $this->get(route('workouts.upload'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => 
        $page->component('workouts/upload')
    );
});

test('verify page loads with session data', function () {
    $user = actingAsDemo();
    
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    // Set session data
    session([
        'workout_data' => [
            'date' => '2025-01-06',
            'title' => 'Test Workout',
            'exercises' => [],
        ],
        'workout_photo_url' => 'http://example.com/photo.jpg',
    ]);
    
    $response = $this->get(route('workouts.verify'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => 
        $page->component('workouts/verify')
            ->has('workout')
            ->has('photoUrl')
    );
});

test('verify page redirects without session data', function () {
    $user = actingAsDemo();
    
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $response = $this->get(route('workouts.verify'));
    
    $response->assertRedirect(route('workouts.upload'));
});

test('save creates workout and sets', function () {
    $user = actingAsDemo();
    
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $workoutData = [
        'date' => '2025-01-07',
        'title' => 'Test Workout',
        'photo_path' => 'test/photo.jpg',
        'notes' => 'Test notes',
        'exercises' => [
            [
                'name' => 'Bench Press',
                'sets' => [
                    ['reps' => 10, 'weight' => 60, 'unit' => 'kg', 'notes' => null],
                    ['reps' => 8, 'weight' => 70, 'unit' => 'kg', 'notes' => null],
                ],
            ],
        ],
    ];
    
    $response = $this->post(route('api.workouts.save'), $workoutData);
    
    $response->assertRedirect();
    
    // Verify workout was created
    $workout = \App\Models\Workout::where('user_id', $user->id)
        ->where('title', 'Test Workout')
        ->first();
    
    expect($workout)->not->toBeNull();
    expect($workout->sets()->count())->toBe(2);
    
    // Cleanup
    $workout->delete();
});

test('save validates required fields', function () {
    $user = actingAsDemo();
    
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $response = $this->post(route('api.workouts.save'), []);
    
    $response->assertSessionHasErrors(['date']);
});

test('save validates exercise structure', function () {
    $user = actingAsDemo();
    
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $workoutData = [
        'date' => '2025-01-07',
        'title' => 'Test Workout',
        'exercises' => [
            [
                'name' => 'Bench Press',
                'sets' => [
                    ['reps' => -1, 'weight' => 60, 'unit' => 'kg'], // Invalid reps
                ],
            ],
        ],
    ];
    
    $response = $this->post(route('api.workouts.save'), $workoutData);
    
    $response->assertSessionHasErrors();
});

test('upload page requires authentication', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $response = $this->get(route('workouts.upload'));
    
    $response->assertRedirect(route('login'));
});

test('save requires authentication', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $response = $this->post(route('api.workouts.save'), [
        'date' => '2025-01-07',
        'title' => 'Test',
        'exercises' => [],
    ]);
    
    $response->assertRedirect(route('login'));
});

