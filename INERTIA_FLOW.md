# Workout Photo Upload - Inertia.js Flow

## Overview

This application uses [Inertia.js](https://inertiajs.com/docs/v2/getting-started/index) to create a seamless SPA-like experience while keeping server-side Laravel routing and controllers. Inertia acts as "glue" between the Laravel backend and React frontend.

## The Upload → Verify Flow

### 1. Upload Page (`/test-upload`)
**Component:** `resources/js/pages/workouts/upload-standalone.tsx`

User uploads a workout photo:
```tsx
router.post('/api/workouts/upload', formData, {
    forceFormData: true,
    onError: (errors) => { /* handle errors */ }
});
```

### 2. Backend Processing
**Controller:** `app/Http/Controllers/WorkoutUploadController.php`

```php
public function upload(Request $request): \Inertia\Response
{
    // 1. Validate upload
    $request->validate(['photo' => 'required|image|...']);
    
    // 2. Save photo to public storage
    $originalPath = $file->storeAs('uploads/original', $filename, 'public');
    
    // 3. Preprocess image (grayscale, contrast, sharpen)
    $this->imageService->preprocessForOCR($originalPath, $processedPath);
    
    // 4. Extract workout data via Claude Vision API
    $workoutData = $this->anthropicService->extractWorkoutData($processedPath);
    
    // 5. Return Inertia response (NOT JSON!)
    return Inertia::render('workouts/verify', [
        'workout' => $workoutData,
        'photoUrl' => asset('storage/' . $originalPath),
    ]);
}
```

### 3. Verification Page (Automatic Navigation)
**Component:** `resources/js/pages/workouts/verify.tsx`

Inertia automatically:
- Intercepts the backend response
- Swaps to the verification page component
- Passes `workout` and `photoUrl` as props
- **No page reload occurs** ✨

The user sees:
```tsx
export default function VerifyWorkout({ workout, photoUrl }: VerifyWorkoutProps) {
    // workout prop contains all extracted exercises
    // photoUrl contains the URL to the original photo
    
    return (
        <div>
            {/* Display extracted data in editable form */}
            {/* Show original photo for reference */}
            {/* Save button (TODO: implement) */}
        </div>
    );
}
```

## Key Inertia.js Concepts Used

### Server-Side (Laravel)

**Inertia Responses:**
```php
// Instead of:
return response()->json(['data' => $data]);

// Use Inertia:
return Inertia::render('ComponentName', [
    'propName' => $propValue,
]);
```

The response looks like this under the hood:
```json
{
    "component": "workouts/verify",
    "props": {
        "workout": {...},
        "photoUrl": "..."
    },
    "url": "/api/workouts/upload",
    "version": "..."
}
```

### Client-Side (React)

**Using Inertia Router:**
```tsx
import { router } from '@inertiajs/react';

// Navigate with data
router.post('/endpoint', formData, {
    forceFormData: true,
    onSuccess: () => { /* handle success */ },
    onError: (errors) => { /* handle errors */ }
});
```

**Receiving Props:**
```tsx
interface PageProps {
    workout: Workout;
    photoUrl: string;
}

export default function MyPage({ workout, photoUrl }: PageProps) {
    // Props are automatically passed by Inertia
    // No need for useEffect, fetch, or API calls
}
```

## Why Inertia.js?

According to the [official docs](https://inertiajs.com/docs/v2/getting-started/index):

> "Inertia allows you to create fully client-side rendered, single-page apps, without the complexity that comes with modern SPAs. It does this by leveraging existing server-side patterns that you already love."

### Benefits for This Project

1. **No API Layer Needed** - Controllers return views, not JSON
2. **No Client-Side Routing** - Laravel handles all routing
3. **Type-Safe Props** - TypeScript interfaces for props
4. **SPA Experience** - Page transitions without reloads
5. **Server-Side Validation** - Use Laravel's validation

### Traditional SPA vs. Inertia

**Traditional SPA:**
```
Client                          Server
------                          ------
1. POST /api/upload         →   
                            ←   2. JSON response
3. Parse JSON
4. Navigate to /verify
5. GET /api/workout/:id     →
                            ←   6. JSON response
7. Render component
```

**With Inertia:**
```
Client                          Server
------                          ------
1. POST /api/upload         →   
                            ←   2. Inertia response
3. Auto-render component
   (props already included!)
```

## File Structure

```
app/Http/Controllers/
  └── WorkoutUploadController.php     # Returns Inertia::render()

resources/js/pages/
  └── workouts/
      ├── upload-standalone.tsx       # Uses router.post()
      └── verify.tsx                  # Receives props from Inertia

routes/web.php                        # Standard Laravel routes
```

## Error Handling

### Validation Errors
Laravel validation errors are automatically passed to Inertia:

```tsx
router.post('/api/workouts/upload', formData, {
    onError: (errors) => {
        // errors.photo will contain validation message
        setError(errors.photo);
    }
});
```

### Server Errors
Catch exceptions in controller and return error response:

```php
try {
    // Process upload
} catch (\Exception $e) {
    return response()->json([
        'success' => false,
        'error' => $e->getMessage()
    ], 500);
}
```

## Next Steps

1. ✅ Upload photo → Extract data → Navigate to verify page
2. 🔄 **Current:** Verify page with editable form
3. 🔜 **Next:** Save verified workout to database
4. 🔜 **Next:** Workout list page
5. 🔜 **Next:** Workout detail page

## Testing the Flow

1. Visit `http://fitandfocused.test/test-upload`
2. Upload a workout photo
3. Watch the page transition (no reload!)
4. See the verification page with extracted data
5. Review and edit the data
6. Click "Save Workout" (TODO: implement database save)

---

**Resources:**
- [Inertia.js Documentation](https://inertiajs.com/docs/v2/getting-started/index)
- [Inertia Laravel Adapter](https://inertiajs.com/server-side-setup)
- [Inertia React Adapter](https://inertiajs.com/client-side-setup)

