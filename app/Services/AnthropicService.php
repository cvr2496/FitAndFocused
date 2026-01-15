<?php

namespace App\Services;

use Anthropic\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnthropicService
{
    protected Client $client;
    protected string $model = 'claude-sonnet-4-5-20250929';
    protected int $maxSteps = 10;

    public function __construct()
    {
        $apiKey = config('services.anthropic.api_key');
        
        if (empty($apiKey)) {
            throw new \Exception('Anthropic API key not configured. Please set ANTHROPIC_API_KEY in .env');
        }

        $this->client = new Client(apiKey: $apiKey);
    }

    /**
     * Extract workout data from an image using Claude Vision API
     *
     * @param string $imagePath Path to the image file
     * @return array Extracted workout data
     * @throws \Exception
     */
    public function extractWorkoutData(string $imagePath): array
    {
        try {
            Log::info('Starting Anthropic OCR extraction', ['image' => $imagePath]);

            // Read and encode image to base64
            $imageData = file_get_contents($imagePath);
            $base64Image = base64_encode($imageData);
            
            // Detect image type
            $imageInfo = getimagesize($imagePath);
            $mimeType = $imageInfo['mime'] ?? 'image/jpeg';

            // Call Claude API using official SDK
            $response = $this->client->messages->create([
                'model' => $this->model,
                'max_tokens' => 4096,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image',
                                'source' => [
                                    'type' => 'base64',
                                    'media_type' => $mimeType,
                                    'data' => $base64Image,
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => $this->getExtractionPrompt(),
                            ],
                        ],
                    ],
                ],
            ]);

            // Extract the text content from the response
            $extractedText = $response->content[0]->text ?? '';
            
            Log::info('Raw Anthropic response', ['text' => $extractedText]);

            // Parse the JSON from the response
            $workoutData = $this->parseJsonResponse($extractedText);
            $this->validateWorkoutData($workoutData);

            Log::info('Successfully extracted workout data', [
                'exercises_count' => count($workoutData['exercises'] ?? [])
            ]);

            return $workoutData;

        } catch (\Exception $e) {
            Log::error('Anthropic OCR extraction failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get the extraction prompt for Claude
     *
     * @return string
     */
    protected function getExtractionPrompt(): string
    {
        return <<<'PROMPT'
Extract workout data from this handwritten log photo.

Return ONLY valid JSON with this exact structure:
{
  "date": "YYYY-MM-DD",
  "title": "Workout title (e.g., 'Chest and Triceps')",
  "exercises": [
    {
      "name": "Exercise name",
      "sets": [
        {
          "reps": 10,
          "weight": 70,
          "unit": "kg",
          "notes": null,
          "confidence": "high"
        }
      ]
    }
  ],
  "notes": "Any workout-level notes"
}

NORMALIZATION RULES:
- "10x70kg" = "70kg x 10" = "70kg 10 reps" → {reps: 10, weight: 70, unit: "kg"}
- "12.5lbs" = "12.5 lbs" = "12.5lb" → {weight: 12.5, unit: "lbs"}
- When multiple sets listed with slashes (e.g., "10x60kg / 6x90kg"), create separate set objects
- Preserve order of exercises and sets as written

CONFIDENCE SCORING:
- Mark "confidence": "low" for unclear/illegible fields
- Use null for values you cannot read
- Be conservative - if unsure, mark as low confidence

Do not include any markdown, explanations, or text outside the JSON structure.
PROMPT;
    }

    /**
     * Parse JSON response from Claude, handling potential markdown wrappers
     *
     * @param string $text
     * @return array
     * @throws \Exception
     */
    protected function parseJsonResponse(string $text): array
    {
        // Remove markdown code blocks if present
        $text = preg_replace('/```json\s*/', '', $text);
        $text = preg_replace('/```\s*/', '', $text);
        
        // Find JSON object if mixed with text
        if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
            $text = $matches[0];
        }
        
        $text = trim($text);

        $data = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try to repair common issues or just log
            throw new \Exception('Failed to parse JSON response: ' . json_last_error_msg());
        }

        // Validate required fields if this was a workout log extraction
        // Note: For recommendations, this validation might be too strict if specific fields differ.
        // But generateRecommendation should follow its own structure. 
        // We will skip generic validation here or ensure it matches.
        // Actually, existing code calls validateWorkoutData. We should only call it if it's from extractWorkoutData context?
        // Or make validateWorkoutData check for "exercises" which recommendation HAS.
        // Recommendation has title, description, exercises.
        // Workout Log has date, title, exercises...
        // Date is missing in Recommendation?
        // Let's modify validateWorkoutData to be optional or adaptive.
        // For now, I will NOT call validateWorkoutData in parseJsonResponse, but call it in extractWorkoutData explicitly.
        
        return $data;
    }

    /**
     * Validate the structure of extracted workout data
     *
     * @param array $data
     * @throws \Exception
     */
    protected function validateWorkoutData(array $data): void
    {
        if (!isset($data['date'])) {
            throw new \Exception('Missing required field: date');
        }

        if (!isset($data['exercises']) || !is_array($data['exercises'])) {
            throw new \Exception('Missing or invalid field: exercises');
        }

        foreach ($data['exercises'] as $index => $exercise) {
            if (!isset($exercise['name'])) {
                throw new \Exception("Missing exercise name at index {$index}");
            }

            if (!isset($exercise['sets']) || !is_array($exercise['sets'])) {
                throw new \Exception("Missing or invalid sets for exercise: {$exercise['name']}");
            }
        }
    }
    /**
     * Generate a workout recommendation for the user.
     *
     * @param mixed $user
     * @return string
     */
    public function generateRecommendation($user)
    {
        $systemPrompt = "You are an expert fitness coach AI analyzing workout history to create personalized recommendations.

WORKFLOW:
1. Query the database to find the user's last 3-5 workouts
2. Analyze what muscle groups they trained and when
3. Identify what muscle group they should train TODAY based on recovery and balance
4. Generate a detailed workout with 6 exercises

CRITICAL REQUIREMENTS:
- Your description MUST start with contextual analysis like: \"Since you last trained [muscle group] on [date], today is perfect for [recommended focus]\"
- Include EXACTLY 6 exercises
- Each exercise needs: name, sets (e.g., '3-4'), reps (e.g., '8-12'), and specific form/technique notes
- Mix compound and isolation movements
- Progress from heavy compounds to lighter isolation work

OUTPUT FORMAT (JSON ONLY, NO MARKDOWN):
{
    \"title\": \"Pull Day: Back & Biceps Power\",
    \"description\": \"Since you last trained chest and triceps 2 days ago, today is perfect for a pull-focused session. Let's build a strong back and pump those biceps with progressive overload.\",
    \"exercises\": [
        {\"name\": \"Pull-ups or Lat Pulldown\", \"sets\": \"4\", \"reps\": \"6-10\", \"notes\": \"Wide grip for lat width. Use assisted or band if needed.\"},
        {\"name\": \"Barbell or Dumbbell Rows\", \"sets\": \"4\", \"reps\": \"8-12\", \"notes\": \"Focus on pulling with elbows, squeeze shoulder blades together.\"},
        {\"name\": \"Seated Cable Rows\", \"sets\": \"3\", \"reps\": \"10-12\", \"notes\": \"Keep chest up, pull to lower abs for mid-back thickness.\"},
        {\"name\": \"Face Pulls\", \"sets\": \"3\", \"reps\": \"15-20\", \"notes\": \"Light weight, focus on rear delts and upper back health.\"},
        {\"name\": \"Barbell or EZ-Bar Curls\", \"sets\": \"3\", \"reps\": \"8-12\", \"notes\": \"Strict form, no swinging. Control the negative.\"},
        {\"name\": \"Hammer Curls\", \"sets\": \"3\", \"reps\": \"10-15\", \"notes\": \"Targets brachialis for bicep thickness and forearm strength.\"}
    ]
}

IMPORTANT: Return ONLY the JSON object. No explanations, no markdown code blocks.";

        $response = $this->runLoop($systemPrompt, "Generate a workout recommendation for user_id {$user->id}. Query the database first to understand their recent training history.");
        
        // Debug logging
        Log::info('AI Recommendation Raw Response', ['response' => $response]);
        
        return $this->parseJsonResponse($response);
    }

    /**
     * Chat with the AI coach.
     *
     * @param string $userMessage
     * @param array $context
     * @return string
     */
    public function chat(string $userMessage, array $context = [])
    {
        $systemPrompt = "You are an expert fitness coach AI named LOG.AI. Helpful, motivating, and data-driven.
        You have access to the user's workout database via tools. Use SQL to answer questions about progress, history, or specific sets.
        
        Context provided about current recommendation: " . json_encode($context) . "
        
        Always verify your SQL before running it. Use the user's specific context.";

        return $this->runLoop($systemPrompt, $userMessage);
    }

    protected function runLoop(string $system, string $userMessage)
    {
        $messages = [
            ['role' => 'user', 'content' => $userMessage]
        ];

        $tools = [
            [
                'name' => 'query_database',
                'description' => 'Execute a read-only SQL query against the workouts database. Use this to find past workouts, sets, exercises, and volume.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'The SQL query to execute. MUST be a SELECT statement.'
                        ]
                    ],
                    'required' => ['query']
                ]
            ]
        ];

        for ($i = 0; $i < $this->maxSteps; $i++) {
            $response = $this->client->messages->create([
                'model' => $this->model,
                'max_tokens' => 4096,
                'system' => $system,
                'messages' => $messages,
                'tools' => $tools,
            ]);

            // Append assistant response to history
            $assistantMessage = ['role' => 'assistant', 'content' => $response->content];
            $messages[] = $assistantMessage;

            if ($response->stop_reason === 'tool_use') {
                foreach ($response->content as $contentBlock) {
                    if ($contentBlock->type === 'tool_use') {
                        $toolName = $contentBlock->name;
                        $toolInputs = $contentBlock->input;
                        $toolUseId = $contentBlock->id;

                        if ($toolName === 'query_database') {
                            $result = $this->executeQuery($toolInputs['query']);

                            $messages[] = [
                                'role' => 'user',
                                'content' => [
                                    [
                                        'type' => 'tool_result',
                                        'tool_use_id' => $toolUseId,
                                        'content' => json_encode($result)
                                    ]
                                ]
                            ];
                        }
                    }
                }
            } else {
                // Final response
                return $response->content[0]->text;
            }
        }

        return "I'm sorry, I needed too many steps to figure this out.";
    }

    protected function executeQuery(string $query)
    {
        // Safety check: ensure only SELECT statements
        if (stripos(trim($query), 'SELECT') !== 0) {
            return ['error' => 'Only SELECT queries are allowed for safety.'];
        }

        try {
            return DB::select($query);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

