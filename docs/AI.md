# AI Workout Recommendations

FitAndFocused integrates Claude 3.5 Sonnet to provide intelligent, contextual workout recommendations based on your training history.

## Overview

The AI system analyzes your recent workout history and generates personalized daily workout recommendations with specific exercises, sets, reps, and form cues.

## Features

### 🎯 Daily Workout Recommendations
- **Contextual Analysis**: "Since you last trained [muscle group] on [date]..."
- **6 Detailed Exercises**: Each with sets, reps, and specific technique notes
- **Recovery-Aware**: Recommends muscle groups based on rest needs
- **Progressive Structure**: Heavy compounds → lighter isolation work

### 💬 AI Chat Assistant
- Ask questions about your workout history
- Get exercise form advice
- Receive motivation and coaching tips
- Context-aware responses based on current recommendation

## Architecture

```
┌──────────────────────────────────────┐
│         HomeController               │
│   - Generates daily recommendation   │
│   - Caches for 12 hours              │
└───────────┬──────────────────────────┘
            │
┌───────────▼──────────────────────────┐
│       AnthropicService               │
│   - Claude 3.5 Sonnet integration    │
│   - Tool-use for database queries    │
│   - JSON extraction & parsing        │
└───────────┬──────────────────────────┘
            │
            ├─► generateRecommendation()
            ├─► chat()
            ├─► runLoop() (tool-use handler)
            └─► executeQuery() (safe SELECT only)
```

## API Integration

### Model
- **Name**: Claude 3.5 Sonnet
- **ID**: `claude-sonnet-4-5-20250929`
- **Provider**: Anthropic
- **Max Tokens**: 4096

### Tool Use
The AI has access to the `query_database` tool:

```php
[
    'name' => 'query_database',
    'description' => 'Execute a read-only SQL query against the workouts database.',
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
```

**Security**: Only SELECT statements are allowed. Any attempt to modify data will be rejected.

## Recommendation Generation

### System Prompt Structure

The AI follows this workflow:
1. Query database for last 3-5 workouts
2. Analyze muscle groups and recovery status
3. Identify optimal focus for today
4. Generate 6 exercises with progression

### Output Format

```json
{
    "title": "Pull Day: Back & Biceps Power",
    "description": "Since you last trained chest and triceps on December 28th, today is perfect for a pull-focused session...",
    "exercises": [
        {
            "name": "Pull-ups or Lat Pulldown",
            "sets": "4",
            "reps": "6-10",
            "notes": "Wide grip for lat width. Control the negative for 2-3 seconds."
        },
        ...
    ]
}
```

## Usage

### Frontend (Home Page)

The recommendation is automatically displayed on the home page:

```tsx
<Home 
    recentWorkouts={workouts}
    stats={stats}
    recommendation={recommendation}  // AI-generated
/>
```

### Backend (Controller)

```php
use App\Services\AnthropicService;

public function index(AnthropicService $ai)
{
    $user = auth()->user();
    
    // Cached for 12 hours per user
    $recommendation = Cache::remember(
        "workout_recommendation_{$user->id}_" . now()->toDateString(),
        now()->addHours(12),
        fn() => $ai->generateRecommendation($user)
    );
    
    return Inertia::render('home', [
        'recommendation' => $recommendation
    ]);
}
```

### CLI Testing

Test recommendation generation without the frontend:

```bash
# Generate recommendation for user ID 2
php artisan ai:test-recommendation 2

# Output:
# ✅ Success!
# 
# Title: Pull Day: Back & Biceps Power
# 
# Description:
# Since you last trained chest and triceps on December 28th...
# 
# Exercises: 6
# 
# 1. Pull-ups or Lat Pulldown
#    Sets: 4, Reps: 6-10
#    Notes: Wide grip for lat width...
```

## Chat Functionality

### Endpoint
```
POST /ai/chat
```

### Request
```json
{
    "message": "What should I focus on today?",
    "context": {
        "title": "Pull Day: Back & Biceps Power",
        "description": "...",
        "exercises": [...]
    }
}
```

### Response
```json
{
    "response": "Based on your recent training, focus on pull movements today..."
}
```

## Configuration

### Environment Variables

Add to your `.env`:

```env
ANTHROPIC_API_KEY=your_api_key_here
```

Or configure in `config/services.php`:

```php
'anthropic' => [
    'api_key' => env('ANTHROPIC_API_KEY'),
],
```

## Caching Strategy

### Recommendation Cache
- **Key**: `workout_recommendation_{user_id}_{date}`
- **Duration**: 12 hours
- **Reason**: Minimize API calls, consistent recommendation per day

### Clear Cache
```bash
# Clear specific user's recommendation
php artisan cache:forget workout_recommendation_2_2026-01-11

# Clear all cache
php artisan cache:clear
```

## Error Handling

### Fallback Recommendations

If AI generation fails, a default structure is returned:

```php
[
    'title' => 'Daily Recommendation',
    'description' => 'Unable to generate recommendation at this time. Please try again later.',
    'exercises' => []
]
```

### Logging

Errors are logged to `storage/logs/laravel.log`:

```
[2026-01-11 01:22:49] local.ERROR: AI Recommendation Failed: Model not found
```

## Testing

### Unit Tests
```bash
# Test AI service methods
php artisan test --filter=AnthropicServiceTest
```

### Manual Testing
```bash
# Test via CLI
php artisan ai:test-recommendation 2

# Test via browser
# Navigate to http://fitandfocused.test/home
# View recommendation card

# Test chat
# Type in "Ask AI" input at bottom of screen
```

## Performance Considerations

### API Costs
- ~1000 tokens per recommendation generation
- Cached for 12 hours = max 2 calls/user/day
- Consider: 100 users × 2 calls = 200,000 tokens/day

### Response Time
- First load: 10-15 seconds (tool-use loop)
- Cached load: < 100ms
- Navigation timeout increased to handle AI processing

## Troubleshooting

### Issue: "Model not found"
**Solution**: Verify model ID is correct in `AnthropicService.php`

### Issue: Empty exercises array
**Solution**: Check AI response logs for JSON parsing errors

### Issue: "Property 'stopReason' does not exist"
**Solution**: Use `$response->stop_reason` (snake_case) not camelCase

### Issue: Tools not executing
**Solution**: Verify database queries are valid SELECT statements

## Future Enhancements

- [ ] Voice input for chat
- [ ] Exercise variation suggestions based on equipment
- [ ] Progressive overload tracking (auto-suggest weight increases)
- [ ] Workout plan generation (multi-week programs)
- [ ] Exercise form video recommendations
- [ ] Nutrition recommendations based on training goals

## API Reference

### AnthropicService Methods

#### `generateRecommendation(User $user): array`
Generates a daily workout recommendation.

**Parameters:**
- `$user` - User model instance

**Returns:**
```php
[
    'title' => string,
    'description' => string,
    'exercises' => [
        ['name' => string, 'sets' => string, 'reps' => string, 'notes' => string],
        ...
    ]
]
```

#### `chat(string $message, array $context = []): string`
Handles AI chat interactions.

**Parameters:**
- `$message` - User's question/message
- `$context` - Optional context (current recommendation)

**Returns:** String response from AI

---

**Last Updated:** January 11, 2026  
**AI Model:** Claude 3.5 Sonnet (`claude-sonnet-4-5-20250929`)
