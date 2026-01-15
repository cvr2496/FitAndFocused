<?php

namespace App\Features\AiCoach;

use App\Services\AnthropicService;
use App\Features\AiCoach\Tools\WorkoutQueryTool;
use Illuminate\Support\Facades\Log;

class AiCoachService
{
    protected AnthropicService $ai;
    protected WorkoutQueryTool $queryTool;

    public function __construct(AnthropicService $ai, WorkoutQueryTool $queryTool)
    {
        $this->ai = $ai;
        $this->queryTool = $queryTool;
    }

    /**
     * Generate a workout recommendation for the user.
     *
     * @param mixed $user
     * @return array
     */
    public function generateRecommendation($user): array
    {
        $systemPrompt = "You are an expert fitness coach AI analyzing workout history to create personalized recommendations.
        Your goal is to suggest the next workout based on what the user has done recently.

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
}";

        $response = $this->runToolLoop(
            $systemPrompt, 
            "Generate a workout recommendation for user_id {$user->id}. Query the database first to understand their recent training history.",
            $user
        );
        
        Log::info('AI Recommendation Generated', ['user_id' => $user->id]);
        
        return $this->parseJsonResponse($response);
    }

    /**
     * Chat with the AI coach.
     *
     * @param string $userMessage
     * @param array $context
     * @param mixed $user
     * @return string
     */
    public function chat(string $userMessage, array $context = [], $user = null): string
    {
        if (!$user) {
            throw new \Exception('User must be authenticated to use AI chat');
        }

        $systemPrompt = "You are an expert fitness coach AI named LOG.AI. Helpful, motivating, and data-driven.
        You have access to the user's workout database via tools. Use SQL to answer questions about progress, history, or specific sets.
        
        IMPORTANT: You are querying data for user_id {$user->id}. All queries will be automatically scoped to this user.
        Do NOT include WHERE user_id = {$user->id} in your queries - this is handled automatically for security.
        
        Context provided about current recommendation: " . json_encode($context) . "
        
        Always verify your SQL before running it. Use the user's specific context.";

        return $this->runToolLoop($systemPrompt, $userMessage, $user);
    }

    /**
     * Runs the tool loop with the AI provider
     */
    protected function runToolLoop(string $system, string $userMessage, $user): string
    {
        $messages = [
            ['role' => 'user', 'content' => $userMessage]
        ];

        $tools = [
            [
                'name' => 'query_database',
                'description' => 'Execute a read-only SQL query against the workouts database. Use this to find past workouts, sets, exercises, and volume. Queries are automatically scoped to the authenticated user.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'The SQL query to execute. MUST be a SELECT statement. Do NOT include user_id filtering - it is added automatically.'
                        ]
                    ],
                    'required' => ['query']
                ]
            ]
        ];

        // We use the AnthropicService as the low-level provider
        return $this->ai->executeToolLoop($system, $messages, $tools, function($toolName, $input) use ($user) {
            if ($toolName === 'query_database') {
                return $this->queryTool->executeSecureQuery($input['query'], $user);
            }
            return ['error' => "Unknown tool: {$toolName}"];
        });
    }

    /**
     * Parse JSON response from AI
     */
    protected function parseJsonResponse(string $text): array
    {
        // Remove markdown code blocks if present
        $text = preg_replace('/```json\s*/', '', $text);
        $text = preg_replace('/```\s*/', '', $text);
        
        if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
            $text = $matches[0];
        }
        
        $text = trim($text);
        $data = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse AI JSON response: ' . json_last_error_msg());
        }

        return $data;
    }
}
