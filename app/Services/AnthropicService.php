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
     * Executes a tool-use loop with Claude.
     *
     * @param string $system System prompt
     * @param array $messages Message history
     * @param array $tools Tool definitions
     * @param callable $toolExecutor Function to execute tool calls
     * @return string Final response text
     */
    public function executeToolLoop(string $system, array $messages, array $tools, callable $toolExecutor): string
    {
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
                $toolResults = [];
                foreach ($response->content as $contentBlock) {
                    if ($contentBlock->type === 'tool_use') {
                        $toolName = $contentBlock->name;
                        $toolInputs = $contentBlock->input;
                        $toolUseId = $contentBlock->id;

                        $result = $toolExecutor($toolName, $toolInputs);

                        $toolResults[] = [
                            'type' => 'tool_result',
                            'tool_use_id' => $toolUseId,
                            'content' => json_encode($result)
                        ];
                    }
                }

                if (!empty($toolResults)) {
                    $messages[] = [
                        'role' => 'user',
                        'content' => $toolResults
                    ];
                }
            } else {
                // Final response
                return $response->content[0]->text ?? '';
            }
        }

        return "I'm sorry, I needed too many steps to figure this out.";
    }
}

