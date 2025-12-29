<?php

namespace App\Http\Controllers;

use App\Models\Workout;
use App\Models\Set;
use App\Services\AnthropicService;
use App\Services\ImageProcessingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkoutUploadController extends Controller
{
    protected ImageProcessingService $imageService;
    protected AnthropicService $anthropicService;

    public function __construct(
        ImageProcessingService $imageService,
        AnthropicService $anthropicService
    ) {
        $this->imageService = $imageService;
        $this->anthropicService = $anthropicService;
    }

    /**
     * Handle workout photo upload and extract data via OCR
     *
     * @param Request $request
     * @return \Inertia\Response|\Illuminate\Http\JsonResponse
     */
    public function upload(Request $request): \Inertia\Response|\Illuminate\Http\JsonResponse
    {
        try {
            // Ensure user is authenticated
            if (!Auth::check()) {
                abort(401, 'Authentication required');
            }

            // Validate the upload
            $request->validate([
                'photo' => 'required|image|mimes:jpeg,jpg,png|max:10240', // Max 10MB
            ]);

            $file = $request->file('photo');
            $timestamp = now()->format('Y-m-d-His');
            $filename = $timestamp . '-' . Str::random(8);

            // Ensure storage directories exist
            Storage::disk('public')->makeDirectory('uploads/original');
            Storage::makeDirectory('uploads/processed');

            // Save original image to public disk
            $originalPath = $file->storeAs('uploads/original', $filename . '.' . $file->extension(), 'public');
            $originalFullPath = Storage::disk('public')->path($originalPath);

            Log::info('Photo uploaded', [
                'original_path' => $originalPath,
                'size' => $file->getSize()
            ]);

            // Preprocess image for better OCR
            $processedPath = 'uploads/processed/' . $filename . '.jpg';
            $processedFullPath = Storage::path($processedPath);
            
            $this->imageService->preprocessForOCR($originalFullPath, $processedFullPath);

            Log::info('Image preprocessed', ['processed_path' => $processedPath]);

            // Extract workout data using Claude Vision API
            $workoutData = $this->anthropicService->extractWorkoutData($processedFullPath);

            // Add the original photo path to the response
            $workoutData['photo_path'] = $originalPath;

            // Clean up processed image (keep original for verification UI)
            Storage::delete($processedPath);

            Log::info('Workout data extraction completed', [
                'exercises' => count($workoutData['exercises'] ?? [])
            ]);

            // Return Inertia response to verification page
            return \Inertia\Inertia::render('workouts/verify', [
                'workout' => $workoutData,
                'photoUrl' => asset('storage/' . $originalPath),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Workout upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Clean up files on error
            if (isset($originalPath)) {
                Storage::disk('public')->delete($originalPath);
            }
            if (isset($processedPath)) {
                Storage::delete($processedPath);
            }

            return response()->json([
                'success' => false,
                'error' => 'Upload processing failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save verified workout data to database
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(Request $request)
    {
        try {
            // Ensure user is authenticated
            if (!Auth::check()) {
                abort(401, 'Authentication required');
            }

            // Validate the workout data
            $validated = $request->validate([
                'date' => 'required|date',
                'title' => 'nullable|string|max:255',
                'photo_path' => 'nullable|string',
                'notes' => 'nullable|string',
                'exercises' => 'required|array',
                'exercises.*.name' => 'required|string',
                'exercises.*.sets' => 'required|array',
                'exercises.*.sets.*.reps' => 'nullable|integer|min:0',
                'exercises.*.sets.*.weight' => 'nullable|numeric|min:0',
                'exercises.*.sets.*.unit' => 'nullable|string|in:kg,lbs',
                'exercises.*.sets.*.notes' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // Create workout record
            $workout = Workout::create([
                'user_id' => Auth::id(),
                'date' => $validated['date'],
                'title' => $validated['title'] ?? null,
                'photo_path' => $validated['photo_path'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create sets for each exercise
            $setNumber = 1;
            foreach ($validated['exercises'] as $exercise) {
                foreach ($exercise['sets'] as $set) {
                    Set::create([
                        'workout_id' => $workout->id,
                        'exercise_name' => $exercise['name'],
                        'set_number' => $setNumber++,
                        'reps' => $set['reps'] ?? null,
                        'weight' => $set['weight'] ?? null,
                        'unit' => $set['unit'] ?? 'kg',
                        'notes' => $set['notes'] ?? null,
                    ]);
                }
            }

            DB::commit();

            Log::info('Workout saved successfully', [
                'workout_id' => $workout->id,
                'total_sets' => $setNumber - 1
            ]);

            // Redirect to workout detail page
            return redirect()->route('workouts.show', $workout->id)->with('success', 'Workout saved successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Workout save failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to save workout: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Get the original photo for display
     *
     * @param string $path
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function getPhoto(string $path)
    {
        $fullPath = 'uploads/original/' . $path;
        
        if (!Storage::exists($fullPath)) {
            abort(404, 'Photo not found');
        }

        return response()->file(Storage::path($fullPath));
    }
}

