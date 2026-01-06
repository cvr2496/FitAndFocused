<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workout;
use App\Models\Set;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DemoUserSeeder extends Seeder
{
    /**
     * Seed the demo user with pre-defined workout data.
     */
    public function run(): void
    {
        // Load JSON data
        $jsonPath = database_path('seeders/data/demo-workouts.json');
        
        if (!file_exists($jsonPath)) {
            $this->command->error("Demo workouts JSON file not found at: {$jsonPath}");
            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);
        
        if (!$data) {
            $this->command->error("Failed to parse demo workouts JSON file");
            return;
        }

        // Create or find demo user
        $user = User::firstOrCreate(
            ['email' => $data['user']['email']],
            [
                'name' => $data['user']['name'],
                'password' => Hash::make($data['user']['password']),
                'email_verified_at' => now(),
            ]
        );

        // Ensure email is verified even if user already existed
        if (!$user->email_verified_at) {
            $user->email_verified_at = now();
            $user->save();
        }

        $this->command->info("Demo user: {$user->email}");

        // Delete existing demo workouts if they exist (for idempotency)
        $existingWorkouts = Workout::where('user_id', $user->id)->get();
        if ($existingWorkouts->count() > 0) {
            $this->command->info("Removing {$existingWorkouts->count()} existing demo workouts...");
            foreach ($existingWorkouts as $workout) {
                // Delete associated photo if it exists
                if ($workout->photo_path) {
                    Storage::disk('public')->delete($workout->photo_path);
                }
                $workout->delete();
            }
        }

        // Ensure storage directory exists
        Storage::disk('public')->makeDirectory('uploads/demo');

        // Process each workout
        $workoutCount = 0;
        $setCount = 0;

        foreach ($data['workouts'] as $workoutData) {
            // Generate placeholder image
            $photoPath = $this->generatePlaceholderImage($workoutData['photo_filename']);

            // Create workout record
            $workout = Workout::create([
                'user_id' => $user->id,
                'date' => $workoutData['date'],
                'title' => $workoutData['title'],
                'photo_path' => $photoPath,
                'notes' => $workoutData['notes'],
            ]);

            $workoutCount++;

            // Create sets for each exercise
            $setNumber = 1;
            foreach ($workoutData['exercises'] as $exercise) {
                foreach ($exercise['sets'] as $set) {
                    Set::create([
                        'workout_id' => $workout->id,
                        'exercise_name' => $exercise['name'],
                        'set_number' => $setNumber++,
                        'reps' => $set['reps'],
                        'weight' => $set['weight'],
                        'unit' => $set['unit'],
                        'notes' => $set['notes'],
                    ]);
                    $setCount++;
                }
            }
        }

        $this->command->info("✅ Created {$workoutCount} workouts with {$setCount} sets for demo user");
        $this->command->info("   Email: {$user->email}");
        $this->command->info("   Password: {$data['user']['password']}");
    }

    /**
     * Generate a simple placeholder image
     */
    private function generatePlaceholderImage(string $filename): string
    {
        $path = 'uploads/demo/' . $filename;
        $fullPath = Storage::disk('public')->path($path);

        // Ensure parent directory exists
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Create a simple black 800x600 image
        $image = imagecreatetruecolor(800, 600);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $black);

        // Add some text to identify it as a demo image
        $white = imagecolorallocate($image, 255, 255, 255);
        $text = "Demo Workout Image";
        $font = 5; // Built-in font
        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);
        $x = (800 - $textWidth) / 2;
        $y = (600 - $textHeight) / 2;
        imagestring($image, $font, $x, $y, $text, $white);

        // Save as JPEG (works for both .jpg and .jpeg extensions)
        imagejpeg($image, $fullPath, 90);

        return $path;
    }
}

