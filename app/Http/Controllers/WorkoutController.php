<?php

namespace App\Http\Controllers;

use App\Models\Workout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class WorkoutController extends Controller
{
    /**
     * Display a listing of workouts
     */
    public function index(): Response
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            abort(401, 'Authentication required');
        }

        $workouts = Workout::where('user_id', Auth::id())
            ->with('sets')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($workout) {
                return [
                    'id' => $workout->id,
                    'date' => $workout->date->format('Y-m-d'),
                    'title' => $workout->title,
                    'photo_path' => $workout->photo_path,
                    'notes' => $workout->notes,
                    'total_sets' => $workout->sets->count(),
                    'total_exercises' => $workout->sets->pluck('exercise_name')->unique()->count(),
                    'created_at' => $workout->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return Inertia::render('workouts/index', [
            'workouts' => $workouts,
        ]);
    }

    /**
     * Display a specific workout
     */
    public function show(Workout $workout): Response
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            abort(401, 'Authentication required');
        }

        // Ensure the workout belongs to the authenticated user
        if ($workout->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $workout->load('sets');

        // Group sets by exercise for better display
        $exercises = $workout->sets()
            ->orderBy('set_number')
            ->get()
            ->groupBy('exercise_name')
            ->map(function ($sets, $exerciseName) {
                return [
                    'name' => $exerciseName,
                    'sets' => $sets->map(function (\App\Models\Set $set) {
                        return [
                            'id' => $set->id,
                            'set_number' => $set->set_number,
                            'reps' => $set->reps,
                            'weight' => $set->weight,
                            'unit' => $set->unit,
                            'notes' => $set->notes,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return Inertia::render('workouts/show', [
            'workout' => [
                'id' => $workout->id,
                'date' => $workout->date->format('Y-m-d'),
                'title' => $workout->title,
                'photo_path' => $workout->photo_path,
                'photo_url' => $workout->photo_path ? asset('storage/' . $workout->photo_path) : null,
                'notes' => $workout->notes,
                'created_at' => $workout->created_at->format('Y-m-d H:i:s'),
            ],
            'exercises' => $exercises,
        ]);
    }
}
