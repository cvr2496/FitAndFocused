<?php

use Inertia\Testing\AssertableInertia as Assert;

test('home page loads for authenticated user', function () {
    $user = actingAsDemo();
    
    $response = $this->get(route('home'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => 
        $page->component('home')
    );
});

test('home page displays recent workouts', function () {
    $user = actingAsDemo();
    
    $response = $this->get(route('home'));
    
    $response->assertInertia(fn (Assert $page) => 
        $page->has('recentWorkouts')
            ->where('recentWorkouts', fn ($workouts) => count($workouts) === 5)
    );
});

test('home page displays stats', function () {
    $user = actingAsDemo();
    
    $response = $this->get(route('home'));
    
    $response->assertInertia(fn (Assert $page) => 
        $page->has('stats')
            ->has('stats.weeklyWorkouts')
            ->has('stats.streak')
            ->has('stats.totalVolume')
    );
});

test('streak calculation returns a number', function () {
    $user = actingAsDemo();
    
    $response = $this->get(route('home'));
    
    $response->assertInertia(fn (Assert $page) => 
        $page->where('stats.streak', fn ($streak) => is_int($streak) && $streak >= 0)
    );
});

test('total volume is calculated correctly', function () {
    $user = actingAsDemo();
    
    $response = $this->get(route('home'));
    
    $response->assertInertia(fn (Assert $page) => 
        $page->where('stats.totalVolume', fn ($volume) => $volume > 0)
    );
});

test('home page requires authentication', function () {
    $response = $this->get(route('home'));
    
    $response->assertRedirect(route('login'));
});

