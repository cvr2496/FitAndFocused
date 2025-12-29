# API Documentation

## Overview

The FitAndFocused API provides endpoints for uploading workout photos, verifying extracted data, and viewing workout history. The API uses Laravel's Inertia.js integration for seamless SPA-like experience.

## Base URL

```
http://fitandfocused.test
```

For production, replace with your actual domain.

## Authentication

All workout endpoints require authentication using Laravel Fortify:

- Login: `POST /login`
- Logout: `POST /logout`
- Register: `POST /register`

**Important:** All workouts are scoped to the authenticated user. Users can only view and manage their own workouts.

---

## Routes

### Workout Routes

| Method | Endpoint | Auth Required | Description |
|--------|----------|---------------|-------------|
| POST | `/api/workouts/upload` | No* | Upload workout photo for OCR extraction |
| POST | `/api/workouts/save` | No* | Save verified workout data to database |
| GET | `/workouts` | Yes | List all workouts |
| GET | `/workouts/{id}` | Yes | View specific workout details |
| GET | `/workouts/upload` | Yes | Workout upload page (authenticated) |

*Currently public for testing. Add auth middleware in production.

### Test Routes

| Method | Endpoint | Auth Required | Description |
|--------|----------|---------------|-------------|
| GET | `/test-upload` | No | Public upload page for testing |

---

## Controllers

### WorkoutUploadController

**File:** `app/Http/Controllers/WorkoutUploadController.php`

Handles photo uploads, OCR extraction, and saving workout data.

#### `upload(Request $request)`

Upload a workout photo and extract data using AI/OCR.

**Endpoint:** `POST /api/workouts/upload`

**Request:**

```http
POST /api/workouts/upload HTTP/1.1
Content-Type: multipart/form-data

------FormBoundary
Content-Disposition: form-data; name="photo"; filename="workout.jpg"
Content-Type: image/jpeg

[Binary image data]
------FormBoundary--
```

**Validation Rules:**

```php
[
    'photo' => 'required|image|mimes:jpeg,jpg,png|max:10240' // Max 10MB
]
```

**Process Flow:**

1. Validate image upload
2. Store original image to `storage/app/public/uploads/original/`
3. Preprocess image for better OCR (contrast, compression)
4. Send to Anthropic Claude API for data extraction
5. Parse JSON response
6. Delete processed image (keep original)
7. Return Inertia response to verification page

**Response:**

Redirects to `/workouts/verify` page via Inertia with:

```php
[
    'workout' => [
        'date' => '2025-12-28',
        'title' => 'Chest and Triceps',
        'exercises' => [
            [
                'name' => 'Bench Press',
                'sets' => [
                    [
                        'reps' => 10,
                        'weight' => 60,
                        'unit' => 'kg',
                        'notes' => null,
                        'confidence' => 'high'
                    ],
                    // ... more sets
                ]
            ],
            // ... more exercises
        ],
        'notes' => 'Good workout!',
        'photo_path' => 'uploads/original/2025-12-28-abc123.jpg'
    ],
    'photoUrl' => 'http://fitandfocused.test/storage/uploads/original/...'
]
```

**Error Responses:**

```json
{
    "success": false,
    "error": "Validation failed",
    "message": "The photo field is required.",
    "errors": {
        "photo": ["The photo field is required."]
    }
}
```

```json
{
    "success": false,
    "error": "Upload processing failed",
    "message": "Failed to extract workout data"
}
```

**Example cURL:**

```bash
curl -X POST http://fitandfocused.test/api/workouts/upload \
  -F "photo=@/path/to/workout.jpg" \
  -H "Accept: application/json"
```

---

#### `save(Request $request)`

Save verified workout data to the database.

**Endpoint:** `POST /api/workouts/save`

**Request Body:**

```json
{
    "date": "2025-12-28",
    "title": "Chest and Triceps",
    "photo_path": "uploads/original/2025-12-28-abc123.jpg",
    "notes": "Good workout! Felt strong today",
    "exercises": [
        {
            "name": "Bench Press",
            "sets": [
                {
                    "reps": 10,
                    "weight": 60,
                    "unit": "kg",
                    "notes": null
                },
                {
                    "reps": 8,
                    "weight": 70,
                    "unit": "kg",
                    "notes": null
                }
            ]
        },
        {
            "name": "Incline Bench Press",
            "sets": [
                {
                    "reps": 10,
                    "weight": 50,
                    "unit": "kg",
                    "notes": "Felt easy"
                }
            ]
        }
    ]
}
```

**Validation Rules:**

```php
[
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
]
```

**Process Flow:**

1. Validate request data
2. Begin database transaction
3. Create `Workout` record
4. Create `Set` records for each exercise's sets
   - Assigns sequential `set_number` across all exercises
5. Commit transaction
6. Redirect to workout detail page

**Response:**

Redirects to `/workouts/{id}` with success flash message.

**Error Responses:**

Validation errors (422):
```json
{
    "message": "The date field is required.",
    "errors": {
        "date": ["The date field is required."]
    }
}
```

Server errors (500):
```json
{
    "success": false,
    "error": "Failed to save workout",
    "message": "Database connection failed"
}
```

**Example JavaScript (Inertia):**

```javascript
import { useForm } from '@inertiajs/react';

const { data, setData, post, processing } = useForm(workoutData);

const handleSave = () => {
    post('/api/workouts/save', {
        onSuccess: () => {
            // Redirected to detail page
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
        }
    });
};
```

---

#### `getPhoto(string $path)`

Retrieve an uploaded workout photo.

**Endpoint:** `GET /api/workouts/photos/{path}`

**Parameters:**

- `path` - Photo filename (e.g., `2025-12-28-abc123.jpg`)

**Response:**

Binary image file with appropriate `Content-Type` header.

**Error Response:**

404 if photo not found.

**Example:**

```html
<img src="http://fitandfocused.test/api/workouts/photos/2025-12-28-abc123.jpg" />
```

---

### WorkoutController

**File:** `app/Http/Controllers/WorkoutController.php`

Handles viewing workouts and workout history.

#### `index()`

List all workouts with summary statistics.

**Endpoint:** `GET /workouts`

**Authentication:** Required

**Response:**

Inertia page `workouts/index` with data:

```php
[
    'workouts' => [
        [
            'id' => 1,
            'date' => '2025-12-28',
            'title' => 'Chest and Triceps',
            'photo_path' => 'uploads/original/...',
            'notes' => 'Good workout!',
            'total_sets' => 12,
            'total_exercises' => 4,
            'created_at' => '2025-12-28 14:30:00'
        ],
        // ... more workouts
    ]
]
```

**Ordering:**

- Primary: `date DESC` (newest first)
- Secondary: `created_at DESC`

**Includes:**

- Sets are eagerly loaded for counting
- Exercise count calculated using `DISTINCT exercise_name`

---

#### `show(Workout $workout)`

Display detailed workout information with all exercises and sets.

**Endpoint:** `GET /workouts/{id}`

**Authentication:** Required

**Parameters:**

- `{id}` - Workout ID (route model binding)

**Response:**

Inertia page `workouts/show` with data:

```php
[
    'workout' => [
        'id' => 1,
        'date' => '2025-12-28',
        'title' => 'Chest and Triceps',
        'photo_path' => 'uploads/original/...',
        'photo_url' => 'http://fitandfocused.test/storage/uploads/original/...',
        'notes' => 'Good workout!',
        'created_at' => '2025-12-28 14:30:00'
    ],
    'exercises' => [
        [
            'name' => 'Bench Press',
            'sets' => [
                [
                    'id' => 1,
                    'set_number' => 1,
                    'reps' => 10,
                    'weight' => 60,
                    'unit' => 'kg',
                    'notes' => null
                ],
                [
                    'id' => 2,
                    'set_number' => 2,
                    'reps' => 8,
                    'weight' => 70,
                    'unit' => 'kg',
                    'notes' => null
                ]
            ]
        ],
        [
            'name' => 'Incline Bench Press',
            'sets' => [
                [
                    'id' => 3,
                    'set_number' => 3,
                    'reps' => 10,
                    'weight' => 50,
                    'unit' => 'kg',
                    'notes' => 'Felt easy'
                ]
            ]
        ]
    ]
]
```

**Data Structure:**

- Exercises are grouped by `exercise_name`
- Sets within each exercise are ordered by `set_number`
- Photo URL is generated using `asset()` helper

**Error Response:**

404 if workout not found.

---

## Data Formats

### Workout Data Structure

```typescript
interface Workout {
    id?: number;
    date: string;              // YYYY-MM-DD
    title: string | null;
    photo_path: string | null;
    notes: string | null;
    exercises: Exercise[];
    created_at?: string;
    updated_at?: string;
}

interface Exercise {
    name: string;
    sets: Set[];
}

interface Set {
    id?: number;
    set_number?: number;
    reps: number | null;
    weight: number | null;
    unit: 'kg' | 'lbs';
    notes: string | null;
    confidence?: 'high' | 'low';  // Only in OCR response
}
```

### OCR Response Format

The Anthropic API returns structured JSON:

```json
{
    "date": "2025-12-28",
    "title": "Chest and Triceps",
    "exercises": [
        {
            "name": "Bench Press",
            "sets": [
                {
                    "reps": 10,
                    "weight": 60,
                    "unit": "kg",
                    "notes": null,
                    "confidence": "high"
                }
            ]
        }
    ],
    "notes": "Good workout!"
}
```

**Confidence Levels:**

- `high` - OCR is confident in the extracted value
- `low` - Value might need user verification

---

## Frontend Integration

### Inertia.js Pages

| Page | File | Purpose |
|------|------|---------|
| Upload | `resources/js/pages/workouts/upload.tsx` | Photo upload (authenticated) |
| Upload (Test) | `resources/js/pages/workouts/upload-standalone.tsx` | Photo upload (public) |
| Verify | `resources/js/pages/workouts/verify.tsx` | Verify and edit extracted data |
| List | `resources/js/pages/workouts/index.tsx` | View all workouts |
| Detail | `resources/js/pages/workouts/show.tsx` | View single workout |

### Using Wayfinder Routes

The app uses Laravel Wayfinder for type-safe routing:

```typescript
import workouts from '@/routes/workouts';

// Navigate to workout list
<Link href={workouts.index().url}>View Workouts</Link>

// Navigate to workout detail
<Link href={workouts.show(workoutId).url}>View Details</Link>

// Navigate to upload page
<Link href={workouts.upload().url}>Upload Workout</Link>
```

### Form Submission Example

```typescript
import { useForm } from '@inertiajs/react';

const { data, setData, post, processing, errors } = useForm({
    date: '2025-12-28',
    title: 'My Workout',
    exercises: [...]
});

const handleSubmit = (e) => {
    e.preventDefault();
    
    post('/api/workouts/save', {
        onSuccess: () => {
            console.log('Workout saved!');
        },
        onError: (errors) => {
            console.error('Validation failed:', errors);
        }
    });
};
```

---

## Error Handling

### HTTP Status Codes

| Code | Meaning | When It Occurs |
|------|---------|----------------|
| 200 | OK | Successful request |
| 302 | Redirect | Inertia redirects after successful POST |
| 404 | Not Found | Workout or photo doesn't exist |
| 422 | Validation Error | Invalid request data |
| 500 | Server Error | Database or processing error |

### Error Response Format

```json
{
    "success": false,
    "error": "Error category",
    "message": "Human-readable error message",
    "errors": {
        "field_name": ["Field-specific error"]
    }
}
```

### Common Errors

**Validation Failed (422)**

```json
{
    "message": "The photo field is required.",
    "errors": {
        "photo": ["The photo field is required."]
    }
}
```

**File Too Large (422)**

```json
{
    "message": "The photo may not be greater than 10240 kilobytes.",
    "errors": {
        "photo": ["The photo may not be greater than 10240 kilobytes."]
    }
}
```

**OCR Processing Failed (500)**

```json
{
    "success": false,
    "error": "Upload processing failed",
    "message": "Failed to extract workout data from image"
}
```

**Workout Not Found (404)**

```
404 | Not Found
```

---

## Rate Limiting

Currently no rate limiting is implemented. For production, consider:

```php
// In routes/web.php
Route::middleware(['throttle:10,1'])->group(function () {
    Route::post('api/workouts/upload', ...);
});
```

This limits to 10 requests per minute.

---

## Security Considerations

### Current State (Development)

- Upload endpoint is public for testing
- No CSRF protection on API routes (Inertia handles this)
- File uploads limited to 10MB
- Only image types allowed (jpeg, jpg, png)

### Recommended for Production

1. **Add Authentication:**
   ```php
   Route::middleware(['auth'])->group(function () {
       Route::post('api/workouts/upload', ...);
       Route::post('api/workouts/save', ...);
   });
   ```

2. **Rate Limiting:**
   - Limit upload frequency to prevent abuse
   - Limit API calls per user

3. **File Validation:**
   - Scan for malicious content
   - Verify file integrity
   - Store in private storage (not public)

4. **Input Sanitization:**
   - Already using Laravel validation
   - Consider additional sanitization for text fields

5. **Access Control:**
   ```php
   // Only allow users to view their own workouts
   public function show(Workout $workout)
   {
       $this->authorize('view', $workout);
       // ...
   }
   ```

---

## Performance Optimization

### N+1 Query Prevention

```php
// ❌ Bad
$workouts = Workout::all();
foreach ($workouts as $workout) {
    echo $workout->sets->count();
}

// ✅ Good
$workouts = Workout::with('sets')->get();
foreach ($workouts as $workout) {
    echo $workout->sets->count();
}
```

### Pagination

For large datasets, implement pagination:

```php
// In controller
$workouts = Workout::with('sets')
    ->orderBy('date', 'desc')
    ->paginate(20);

return Inertia::render('workouts/index', [
    'workouts' => $workouts
]);
```

### Caching

Cache expensive queries:

```php
$workouts = Cache::remember('user.' . $userId . '.workouts', 3600, function () {
    return Workout::with('sets')->get();
});
```

---

## Testing the API

### Using cURL

```bash
# Upload a workout photo
curl -X POST http://fitandfocused.test/api/workouts/upload \
  -F "photo=@workout.jpg" \
  -H "Accept: application/json"

# Save workout data
curl -X POST http://fitandfocused.test/api/workouts/save \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "date": "2025-12-28",
    "title": "Test Workout",
    "exercises": [{
      "name": "Bench Press",
      "sets": [{"reps": 10, "weight": 60, "unit": "kg"}]
    }]
  }'
```

### Using Postman

1. Import collection with endpoints
2. Set base URL: `http://fitandfocused.test`
3. Add CSRF token if needed (Inertia handles this)
4. Test each endpoint with sample data

### Automated Tests

```php
// tests/Feature/WorkoutUploadTest.php
public function test_can_upload_workout_photo()
{
    Storage::fake('public');
    
    $file = UploadedFile::fake()->image('workout.jpg');
    
    $response = $this->post('/api/workouts/upload', [
        'photo' => $file
    ]);
    
    $response->assertStatus(302);
    Storage::disk('public')->assertExists('uploads/original/' . $file->hashName());
}
```

---

## Logging

The application logs important events:

```php
// Photo uploaded
Log::info('Photo uploaded', [
    'original_path' => $originalPath,
    'size' => $file->getSize()
]);

// Image preprocessed
Log::info('Image preprocessed', ['processed_path' => $processedPath]);

// Workout data extraction completed
Log::info('Workout data extraction completed', [
    'exercises' => count($workoutData['exercises'] ?? [])
]);

// Workout saved successfully
Log::info('Workout saved successfully', [
    'workout_id' => $workout->id,
    'total_sets' => $setNumber - 1
]);

// Errors
Log::error('Workout upload failed', [
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString()
]);
```

View logs:
```bash
tail -f storage/logs/laravel.log
```

---

## Future API Endpoints (Not Yet Implemented)

These are planned but not yet built:

- `GET /api/workouts/:id/summary` - AI-generated workout summary
- `GET /api/exercises/:name/history` - Exercise history across workouts
- `GET /api/stats` - User statistics (total workouts, volume, etc.)
- `DELETE /api/workouts/:id` - Delete a workout
- `PUT /api/workouts/:id` - Update a workout

---

## Additional Resources

- [Laravel Routing Documentation](https://laravel.com/docs/routing)
- [Inertia.js Documentation](https://inertiajs.com/)
- [Laravel Validation](https://laravel.com/docs/validation)
- [Laravel Wayfinder](https://github.com/laravel/wayfinder)

