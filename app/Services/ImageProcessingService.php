<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Log;

class ImageProcessingService
{
    protected ImageManager $manager;

    public function __construct() {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Preprocess an image for OCR by enhancing contrast, sharpness, and converting to grayscale.
     *
     * @param string $inputPath Path to the original image
     * @param string $outputPath Path where processed image will be saved
     * @return void
     * @throws \Exception
     */
    public function preprocessForOCR(string $inputPath, string $outputPath): void
    {
        try {
            Log::info('Starting image preprocessing', [
                'input' => $inputPath,
                'output' => $outputPath
            ]);

            // Load the image
            $image = $this->manager->read($inputPath);

            // Apply preprocessing pipeline for better OCR accuracy
            $image->greyscale()  // Remove color distractions, focus on text structure
                  ->contrast(30)  // Increase contrast to make handwriting stand out
                  ->sharpen(10);  // Enhance text edges for better recognition

            // Save the processed image
            $image->save($outputPath);

            Log::info('Image preprocessing completed successfully');
        } catch (\Exception $e) {
            Log::error('Image preprocessing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get image dimensions
     *
     * @param string $imagePath
     * @return array{width: int, height: int}
     */
    public function getDimensions(string $imagePath): array
    {
        $image = $this->manager->read($imagePath);
        
        return [
            'width' => $image->width(),
            'height' => $image->height()
        ];
    }

    /**
     * Validate if file is a valid image
     *
     * @param string $path
     * @return bool
     */
    public function isValidImage(string $path): bool
    {
        try {
            $this->manager->read($path);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

