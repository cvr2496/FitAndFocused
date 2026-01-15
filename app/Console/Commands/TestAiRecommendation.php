<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Features\AiCoach\AiCoach;
use Illuminate\Console\Command;

class TestAiRecommendation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:test-recommendation {user_id=2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test AI workout recommendation generation';

    /**
     * Execute the console command.
     */
    public function handle(AiCoach $ai)
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);

        if (!$user) {
            $this->error("User {$userId} not found");
            return 1;
        }

        $this->info("Generating recommendation for user {$user->name} (ID: {$user->id})...");
        $this->newLine();

        try {
            $recommendation = $ai->generateRecommendation($user);

            $this->info("✅ Success!");
            $this->newLine();
            
            $this->line("Title: " . ($recommendation['title'] ?? 'N/A'));
            $this->newLine();
            
            $this->line("Description:");
            $this->line($recommendation['description'] ?? 'N/A');
            $this->newLine();
            
            $exerciseCount = count($recommendation['exercises'] ?? []);
            $this->line("Exercises: {$exerciseCount}");
            
            if ($exerciseCount > 0) {
                $this->newLine();
                foreach ($recommendation['exercises'] as $i => $exercise) {
                    $num = $i + 1;
                    $this->line("{$num}. {$exercise['name']}");
                    $this->line("   Sets: {$exercise['sets']}, Reps: {$exercise['reps']}");
                    $this->line("   Notes: {$exercise['notes']}");
                    $this->newLine();
                }
            }

            $this->newLine();
            $this->line("Raw JSON:");
            $this->line(json_encode($recommendation, JSON_PRETTY_PRINT));

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Failed: " . $e->getMessage());
            $this->newLine();
            $this->line("Stack trace:");
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}
