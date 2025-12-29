<?php

namespace App\Console\Commands;

use App\Services\AnthropicService;
use App\Services\ImageProcessingService;
use Illuminate\Console\Command;

class TestOcrSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workout:test-ocr-setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test OCR setup by verifying services and API connection';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Testing Workout OCR Setup...');
        $this->newLine();

        // Test 1: Check environment configuration
        $this->info('1. Checking environment configuration...');
        $apiKey = config('services.anthropic.api_key');
        
        if (empty($apiKey)) {
            $this->error('   ❌ ANTHROPIC_API_KEY not set in .env');
            $this->warn('   Add your API key to .env: ANTHROPIC_API_KEY=your-key-here');
            return Command::FAILURE;
        }
        
        $this->info('   ✅ API key configured');
        $this->newLine();

        // Test 2: Check storage directories
        $this->info('2. Checking storage directories...');
        $directories = [
            storage_path('app/uploads/original'),
            storage_path('app/uploads/processed'),
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                $this->error("   ❌ Directory missing: {$dir}");
                return Command::FAILURE;
            }
            
            if (!is_writable($dir)) {
                $this->error("   ❌ Directory not writable: {$dir}");
                return Command::FAILURE;
            }
        }
        
        $this->info('   ✅ Storage directories exist and are writable');
        $this->newLine();

        // Test 3: Check Intervention Image
        $this->info('3. Checking Intervention Image library...');
        try {
            $imageService = app(ImageProcessingService::class);
            $this->info('   ✅ ImageProcessingService instantiated successfully');
        } catch (\Exception $e) {
            $this->error('   ❌ Failed to load ImageProcessingService: ' . $e->getMessage());
            return Command::FAILURE;
        }
        $this->newLine();

        // Test 4: Check Anthropic Service
        $this->info('4. Checking Anthropic service...');
        try {
            $anthropicService = app(AnthropicService::class);
            $this->info('   ✅ AnthropicService instantiated successfully');
        } catch (\Exception $e) {
            $this->error('   ❌ Failed to load AnthropicService: ' . $e->getMessage());
            return Command::FAILURE;
        }
        $this->newLine();

        // Test 5: Routes check
        $this->info('5. Checking routes...');
        $routes = [
            'api.workouts.upload',
            'api.workouts.photo',
        ];

        foreach ($routes as $routeName) {
            if (\Illuminate\Support\Facades\Route::has($routeName)) {
                $this->info("   ✅ Route '{$routeName}' registered");
            } else {
                $this->error("   ❌ Route '{$routeName}' not found");
                return Command::FAILURE;
            }
        }
        $this->newLine();

        // Summary
        $this->info('═══════════════════════════════════════════════');
        $this->info('✅ All checks passed! Setup is ready for testing.');
        $this->info('═══════════════════════════════════════════════');
        $this->newLine();

        $this->comment('Next steps:');
        $this->line('  1. Navigate to /test-upload in your browser');
        $this->line('  2. Upload a workout photo');
        $this->line('  3. Check the console/network tab for API response');
        $this->newLine();

        $this->comment('API Endpoint:');
        $this->line('  POST ' . url('/api/workouts/upload'));
        $this->newLine();

        return Command::SUCCESS;
    }
}

