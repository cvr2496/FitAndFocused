<?php

use App\Models\Workout;
use Inertia\Testing\AssertableInertia as Assert;

test('can save workout with flexible data types', function () {
    $user = actingAsDemo();
    
    $workoutData = [
        'date' => '2026-01-20',
        'title' => 'CrossFit WOD',
        'type' => 'crossfit',
        'raw_text' => "For Time:\n21-15-9\nThrusters\nPullups",
        'notes' => 'Tough one today',
        'metrics' => [
            'total_time_seconds' => 345,
            'score' => '5:45',
        ],
        // Exercises can be empty for pure text logs, or parsed loosely
        'exercises' => [],
    ];
    
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $response = $this->post(route('api.workouts.save'), $workoutData);
    
    $response->assertRedirect();
    
    $workout = Workout::where('user_id', $user->id)
        ->where('title', 'CrossFit WOD')
        ->first();
        
    expect($workout)->not->toBeNull();
    expect($workout->type)->toBe('crossfit');
    expect($workout->raw_text)->toContain('Thrusters');
    expect($workout->custom_content['metrics']['score'])->toBe('5:45');
    
    // Cleanup
    $workout->delete();
});

test('can save workout with both structured sets and flexible data', function () {
    $user = actingAsDemo();
    
    $workoutData = [
        'date' => '2026-01-20',
        'title' => 'Hybrid Workout',
        'type' => 'strength',
        'raw_text' => null,
        'metrics' => ['total_volume' => 5000],
        'exercises' => [
            [
                'name' => 'Squat',
                'sets' => [
                    ['reps' => 5, 'weight' => 100, 'unit' => 'kg', 'notes' => 'Easy'],
                ],
            ],
        ],
    ];
    
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $response = $this->post(route('api.workouts.save'), $workoutData);
    
    $response->assertRedirect();
    
    $workout = Workout::where('user_id', $user->id)
        ->where('title', 'Hybrid Workout')
        ->first();
        
    expect($workout)->not->toBeNull();
    expect($workout->sets()->count())->toBe(1);
    expect($workout->custom_content['metrics']['total_volume'])->toBe(5000);
    
    // Cleanup
    $workout->delete();
});
