<?php

use App\Services\AnthropicService;

test('anthropic service can be instantiated', function () {
    $service = app(AnthropicService::class);
    
    expect($service)->toBeInstanceOf(AnthropicService::class);
});

test('anthropic service has extractWorkoutData method', function () {
    $service = app(AnthropicService::class);
    
    expect(method_exists($service, 'extractWorkoutData'))->toBeTrue();
});

// Note: We don't test actual API calls here to avoid:
// 1. Incurring API costs during tests
// 2. Depending on external service availability
// 3. Slow test execution
// In a production app, you would mock the API responses for testing

