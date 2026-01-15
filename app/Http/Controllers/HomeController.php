<?php

namespace App\Http\Controllers;

use App\Models\Workout;
use App\Features\AiCoach\AiCoach;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Display the home screen with stats and recent workouts
     */
    public function index(AiCoach $ai): Response
    {
        $user = Auth::user();

        // Get recent workouts (last 5)
        $recentWorkouts = Workout::where('user_id', $user->id)
            ->with('sets')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($workout) {
                return [
                    'id' => $workout->id,
                    'date' => \Illuminate\Support\Carbon::parse($workout->date)->format('Y-m-d'),
                    'title' => $workout->title,
                    'total_exercises' => $workout->sets->pluck('exercise_name')->unique()->count(),
                    'total_volume' => $workout->sets->sum(function ($set) {
                        // Convert to kg if needed, then calculate volume
                        $weight = $set->weight;
                        if ($set->unit === 'lbs') {
                            $weight = $weight * 0.453592; // Convert to kg
                        }
                        return $set->reps * $weight;
                    }),
                ];
            });

        // Calculate stats
        $weekStart = now()->startOfWeek();

        $stats = [
            'weeklyWorkouts' => Workout::where('user_id', $user->id)
                ->where('date', '>=', $weekStart)
                ->count(),
            'totalWorkouts' => Workout::where('user_id', $user->id)->count(),
            'daysSinceLastWorkout' => $this->calculateDaysSinceLastWorkout($user->id),
            'streak' => $this->calculateStreak($user->id),
            'totalVolume' => $this->calculateTotalVolume($user->id),
        ];

        // Get AI Recommendation (Cache for 12 hours)
        $recommendation = \Illuminate\Support\Facades\Cache::remember(
            'workout_recommendation_' . $user->id . '_' . now()->format('Y-m-d'),
            now()->addHours(12),
            function () use ($ai, $user) {
                try {
                    $recommendation = $ai->generateRecommendation($user);
                    return $recommendation ?? [
                        'title' => 'Daily Recommendation', 
                        'description' => 'Could not generate workout.', 
                        'exercises' => []
                    ];
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('AI Recommendation Failed: ' . $e->getMessage());
                    return [
                        'title' => 'Daily Recommendation', 
                        'description' => 'Unable to generate recommendation at this time. Please try again later.', 
                        'exercises' => []
                    ];
                }
            }
        );

        return Inertia::render('home', [
            'recentWorkouts' => $recentWorkouts,
            'stats' => $stats,
            'recommendation' => $recommendation
        ]);
    }

    /**
     * Calculate workout streak (consecutive days with workouts)
     */
    private function calculateStreak(int $userId): int
    {
        $workoutDates = Workout::where('user_id', $userId)
            ->orderBy('date', 'desc')
            ->pluck('date')
            ->map(fn($date) => $date->format('Y-m-d'))
            ->unique()
            ->values();

        if ($workoutDates->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $expectedDate = now()->startOfDay();

        foreach ($workoutDates as $workoutDate) {
            $date = \Carbon\Carbon::parse($workoutDate)->startOfDay();

            // Check if workout is on expected date
            if ($date->equalTo($expectedDate)) {
                $streak++;
                $expectedDate->subDay();
            }
            // Allow for today not having a workout yet if it's the first check
            elseif ($streak === 0 && $date->equalTo($expectedDate->subDay())) {
                $streak++;
                $expectedDate->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Calculate total volume in kg across all workouts
     */
    private function calculateTotalVolume(int $userId): float
    {
        $workouts = Workout::where('user_id', $userId)
            ->with('sets')
            ->get();

        $totalVolume = 0;

        foreach ($workouts as $workout) {
            foreach ($workout->sets as $set) {
                $weight = $set->weight;
                // Convert lbs to kg
                if ($set->unit === 'lbs') {
                    $weight = $weight * 0.453592;
                }
                $totalVolume += $set->reps * $weight;
            }
        }

        return round($totalVolume, 2);
    }

    /**
     * Calculate days since last workout
     */
    private function calculateDaysSinceLastWorkout(int $userId): int
    {
        $lastWorkout = Workout::where('user_id', $userId)
            ->orderBy('date', 'desc')
            ->first();

        if (!$lastWorkout) {
            return 0; // Or handle as "never"
        }

        return \Carbon\Carbon::parse($lastWorkout->date)->startOfDay()->diffInDays(now()->startOfDay());
    }
}


