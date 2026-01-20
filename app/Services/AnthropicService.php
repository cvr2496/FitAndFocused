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

    protected bool $mockMode = false;

    public function __construct()
    {
        $apiKey = config('services.anthropic.api_key');
        
        if (empty($apiKey)) {
            if (app()->environment(['local', 'testing'])) {
                $this->mockMode = true;
                Log::warning('Anthropic API key not configured. Running in MOCK MODE.');
                return;
            }
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
    /**
     * Extract workout data from an image using Claude Vision API
     *
     * @param string $imagePath Path to the image file
     * @return array Extracted workout data
     * @throws \Exception
     */
    public function extractWorkoutData(string $imagePath): array
    {
        if ($this->mockMode) {
            Log::info('Returning MOCK data for image extraction');
            return [
                'date' => now()->format('Y-m-d'),
                'title' => 'Photo Workout (Mock)',
                'type' => 'strength',
                'exercises' => [
                    [
                        'name' => 'Bench Press',
                        'sets' => [
                            ['reps' => 10, 'weight' => 60, 'unit' => 'kg', 'confidence' => 'high'],
                            ['reps' => 8, 'weight' => 70, 'unit' => 'kg', 'confidence' => 'high']
                        ]
                    ],
                    [
                        'name' => 'Squats',
                        'sets' => [
                            ['reps' => 5, 'weight' => 100, 'unit' => 'kg', 'confidence' => 'high']
                        ]
                    ]
                ],
                'metrics' => [
                    'total_time_seconds' => 3600,
                    'score' => null
                ],
                'notes' => 'Mock data from photo upload.',
                'raw_text' => null
            ];
        }

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

            return $this->processResponse($response);

        } catch (\Exception $e) {
            Log::error('Anthropic OCR extraction failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Extract workout data from raw text (manual entry)
     *
     * @param string $text Raw workout log text
     * @return array Extracted workout data
     * @throws \Exception
     */
    public function extractFromText(string $text): array
    {
        if ($this->mockMode) {
            Log::info('Returning MOCK data for text extraction', ['text_start' => substr($text, 0, 50)]);
            return [
                'date' => now()->format('Y-m-d'),
                'title' => 'Murph (Mock)',
                'type' => 'crossfit',
                'exercises' => [
                    [
                        'name' => 'Run',
                        'sets' => [
                            ['distance_meters' => 1600, 'notes' => '1 mile', 'confidence' => 'high']
                        ]
                    ],
                    [
                        'name' => 'Pull-ups',
                        'sets' => [
                            ['reps' => 100, 'confidence' => 'high']
                        ]
                    ],
                    [
                        'name' => 'Push-ups',
                        'sets' => [
                            ['reps' => 200, 'confidence' => 'high']
                        ]
                    ],
                    [
                        'name' => 'Squats',
                        'sets' => [
                            ['reps' => 300, 'confidence' => 'high']
                        ]
                    ],
                    [
                        'name' => 'Run',
                        'sets' => [
                            ['distance_meters' => 1600, 'notes' => '1 mile', 'confidence' => 'high']
                        ]
                    ]
                ],
                'metrics' => [
                    'total_time_seconds' => 2730, // 45:30
                    'score' => '45:30'
                ],
                'notes' => 'Partitioned as 20 rounds of Cindy (5/10/15). Wore a 20lb vest.',
                'raw_text' => $text
            ];
        }

        try {
            Log::info('Starting Anthropic Text extraction');

            $response = $this->client->messages->create([
                'model' => $this->model,
                'max_tokens' => 4096,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $this->getExtractionPrompt() . "\n\nHere is the workout log text:\n" . $text,
                    ],
                ],
            ]);

            return $this->processResponse($response);

        } catch (\Exception $e) {
            Log::error('Anthropic Text extraction failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Process the API response
     */
    protected function processResponse($response): array
    {
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
    }

    /**
     * Get the extraction prompt for Claude
     *
     * @return string
     */
    protected function getExtractionPrompt(): string
    {
        return <<<'PROMPT'
Extract workout data from this workout log (image or text).

Return ONLY valid JSON with this exact structure:
{
  "date": "YYYY-MM-DD",
  "title": "Workout title",
  "type": "strength" | "crossfit" | "cardio" | "other",
  "exercises": [
    {
      "name": "Exercise name",
      "sets": [
        {
          "reps": 10,
          "weight": 70,
          "unit": "kg" | "lbs",
          "time_seconds": null,
          "distance_meters": null,
          "notes": null,
          "confidence": "high"
        }
      ]
    }
  ],
  "metrics": {
    "total_time_seconds": null,
    "total_rounds": null,
    "score": null
  },
  "notes": "Any workout-level notes"
}

NORMALIZATION RULES:
- Detect the "type" based on the content. Standard lifting is "strength", WODs/Metcons are "crossfit".
- "10x70kg" = "70kg x 10" = "70kg 10 reps" → {reps: 10, weight: 70, unit: "kg"}
- For CrossFit/Circuit: Break down into exercises if possible. 
  - "21-15-9 Thrusters and Pullups" → Thrusters (21 reps, 15 reps, 9 reps), Pullups (21 reps, 15 reps, 9 reps).
- Preserve order of exercises and sets as written.

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

