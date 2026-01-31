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
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse
     */
    public function upload(Request $request): \Inertia\Response|\Illuminate\Http\RedirectResponse
    {
        try {
            // Ensure user is authenticated
            if (!Auth::check()) {
                abort(401, 'Authentication required');
            }

            // Validate the upload
            $request->validate([
                'photo' => 'required_without:content|image|mimes:jpeg,jpg,png|max:10240', // Max 10MB
                'content' => 'required_without:photo|string|max:10000',
            ]);

            if ($request->hasFile('photo')) {
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
                
                // Track source type
                $workoutData['raw_text'] = null; // OCR result is implicit in data

            } else {
                // Handle text input
                $content = $request->input('content');
                $workoutData = $this->anthropicService->extractFromText($content);
                $workoutData['photo_path'] = null; // No photo
                $workoutData['raw_text'] = $content;
            }

            Log::info('Workout data extraction completed', [
                'exercises' => count($workoutData['exercises'] ?? [])
            ]);

            // Store workout data in session and redirect to verify page (Post/Redirect/Get pattern)
            session([
                'workout_data' => $workoutData,
                'workout_photo_url' => $workoutData['photo_path'] ? asset('storage/' . $workoutData['photo_path']) : null,
            ]);

            return redirect()->route('workouts.verify');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Inertia handles validation exceptions automatically
            return back()->withErrors($e->errors())->withInput();

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

            return back()->with('error', 'Failed to process upload: ' . $e->getMessage());
        }
    }

    /**
     * Show the workout verification page
     *
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse
     */
    public function showVerify()
    {
        // Check if we have workout data in session
        if (!session()->has('workout_data')) {
            return redirect()->route('workouts.upload')
                ->with('error', 'No workout data found. Please upload a photo first.');
        }

        $workoutData = session('workout_data');
        $photoUrl = session('workout_photo_url');

        // Keep the data in session in case they refresh
        return \Inertia\Inertia::render('workouts/verify', [
            'workout' => $workoutData,
            'photoUrl' => $photoUrl,
        ]);
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
                'raw_text' => 'nullable|string',
                'type' => 'nullable|string|in:strength,crossfit,cardio,other',
                'notes' => 'nullable|string',
                'metrics' => 'nullable|array', // Allow metrics
                'exercises' => 'nullable|array', // Exercises optional? Let's say yes for max flexibility
                'exercises.*.name' => 'required_with:exercises|string',
                'exercises.*.sets' => 'nullable|array',
                'exercises.*.sets.*.reps' => 'nullable|integer|min:0',
                'exercises.*.sets.*.weight' => 'nullable|numeric|min:0',
                'exercises.*.sets.*.unit' => 'nullable|string', // Removed strict in:kg,lbs
                'exercises.*.sets.*.notes' => 'nullable|string',
                'exercises.*.sets.*.time_seconds' => 'nullable|integer',
                'exercises.*.sets.*.distance_meters' => 'nullable|numeric',
            ]);

            DB::beginTransaction();

            // Create workout record
            $workout = Workout::create([
                'user_id' => Auth::id(),
                'date' => $validated['date'],
                'title' => $validated['title'] ?? null,
                'photo_path' => $validated['photo_path'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'type' => $validated['type'] ?? 'strength',
                'raw_text' => $validated['raw_text'] ?? null,
                'custom_content' => $validated, // Store everything as flexible JSON
            ]);

            // Create sets for each exercise if they exist
            if (!empty($validated['exercises'])) {
                $setNumber = 1;
                foreach ($validated['exercises'] as $exercise) {
                    if (empty($exercise['sets'])) continue;
                    
                    foreach ($exercise['sets'] as $set) {
                        // Best effort mapping to strict "sets" table
                        Set::create([
                            'workout_id' => $workout->id,
                            'exercise_name' => $exercise['name'],
                            'set_number' => $setNumber++,
                            'reps' => $set['reps'] ?? null,
                            'weight' => $set['weight'] ?? null,
                            'unit' => $set['unit'] ?? 'kg',
                            'notes' => $set['notes'] ?? null,
                            // Note: time/distance are not in sets table yet, 
                            // maybe put them in notes if present?
                            // For now, custom_content holds the robust data.
                        ]);
                    }
                }
            }

            DB::commit();

            // Clear session data
            session()->forget(['workout_data', 'workout_photo_url']);

            Log::info('Workout saved successfully', [
                'workout_id' => $workout->id
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
        // Try multiple possible locations
        $possiblePaths = [
            'uploads/original/' . $path,
            'uploads/demo/' . $path,
            $path, // Full path might be provided
        ];
        
        foreach ($possiblePaths as $fullPath) {
            if (Storage::disk('public')->exists($fullPath)) {
                return response()->file(Storage::disk('public')->path($fullPath));
            }
        }
        
        abort(404, 'Photo not found');
    }
}

