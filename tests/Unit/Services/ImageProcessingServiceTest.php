<?php

use App\Services\ImageProcessingService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->service = new ImageProcessingService();
});

test('image processing service can be instantiated', function () {
    expect($this->service)->toBeInstanceOf(ImageProcessingService::class);
});

test('image processing service has preprocessForOCR method', function () {
    expect(method_exists($this->service, 'preprocessForOCR'))->toBeTrue();
});

test('preprocessForOCR processes demo image successfully', function () {
    // Use one of the demo images for testing
    $inputPath = Storage::disk('public')->path('uploads/demo/demo-workout-01.jpg');
    $outputPath = storage_path('app/test-processed.jpg');
    
    // Skip if demo image doesn't exist
    if (!file_exists($inputPath)) {
        $this->markTestSkipped('Demo image not found');
    }
    
    $this->service->preprocessForOCR($inputPath, $outputPath);
    
    expect(file_exists($outputPath))->toBeTrue();
    
    // Cleanup
    if (file_exists($outputPath)) {
        unlink($outputPath);
    }
});

