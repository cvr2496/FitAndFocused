# Eloquent Models Documentation

## Overview

The FitAndFocused application uses Laravel's Eloquent ORM for database interactions. This document describes the models, their relationships, and usage patterns.

## Model Structure

```
app/Models/
├── User.php          # Laravel default user model
├── Workout.php       # Workout entity
└── Set.php          # Individual exercise set
```

## Workout Model

**File:** `app/Models/Workout.php`

### Properties

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workout extends Model
{
    protected $fillable = [
        'date',
        'title',
        'photo_path',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
```

### Fillable Attributes

Mass-assignable attributes for `Workout::create()` and `$workout->fill()`:

- **`date`** (string/date) - Workout date in YYYY-MM-DD format
- **`title`** (string|null) - Optional workout title
- **`photo_path`** (string|null) - Path to original photo in storage
- **`notes`** (text|null) - Workout-level notes

### Casts

- **`date`** → Cast to Carbon instance for easy date manipulation

### Relationships

#### `sets()` - HasMany Relationship

Returns all sets belonging to this workout.

```php
public function sets(): HasMany
{
    return $this->hasMany(Set::class);
}
```

**Usage:**
```php
$workout = Workout::find(1);

// Get all sets
$sets = $workout->sets;  // Collection<Set>

// Query the relationship
$benchSets = $workout->sets()
    ->where('exercise_name', 'Bench Press')
    ->get();

// Count sets
$totalSets = $workout->sets()->count();

// Eager load to avoid N+1
$workouts = Workout::with('sets')->get();
```

### Custom Methods

#### `getTotalSets(): int`

Returns the total number of sets in this workout.

```php
public function getTotalSets(): int
{
    return $this->sets()->count();
}
```

**Usage:**
```php
$workout = Workout::find(1);
echo "Total sets: " . $workout->getTotalSets();
// Output: Total sets: 12
```

#### `getExercises(): array`

Returns exercises grouped by name with their sets.

```php
public function getExercises(): array
{
    return $this->sets()
        ->orderBy('set_number')
        ->get()
        ->groupBy('exercise_name')
        ->map(function ($sets) {
            return [
                'name' => $sets->first()->exercise_name,
                'sets' => $sets->values(),
            ];
        })
        ->values()
        ->toArray();
}
```

**Returns:**
```php
[
    [
        'name' => 'Bench Press',
        'sets' => [
            ['set_number' => 1, 'reps' => 10, 'weight' => 60, ...],
            ['set_number' => 2, 'reps' => 8, 'weight' => 70, ...],
        ]
    ],
    [
        'name' => 'Incline Bench Press',
        'sets' => [...]
    ]
]
```

**Usage:**
```php
$workout = Workout::find(1);
$exercises = $workout->getExercises();

foreach ($exercises as $exercise) {
    echo $exercise['name'] . ": " . count($exercise['sets']) . " sets\n";
}
```

### Usage Examples

#### Creating a Workout

```php
$workout = Workout::create([
    'date' => '2025-12-29',
    'title' => 'Chest and Triceps',
    'photo_path' => 'uploads/original/workout-123.jpg',
    'notes' => 'Felt strong today!',
]);
```

#### Updating a Workout

```php
$workout = Workout::find(1);
$workout->update([
    'title' => 'Updated Title',
    'notes' => 'Added some notes',
]);

// Or
$workout->title = 'Updated Title';
$workout->save();
```

#### Deleting a Workout

```php
$workout = Workout::find(1);
$workout->delete();  // Cascade deletes all sets automatically
```

#### Querying Workouts

```php
// All workouts, newest first
$workouts = Workout::orderBy('date', 'desc')->get();

// Workouts with their sets (eager loading)
$workouts = Workout::with('sets')->get();

// Workouts from a specific date range
$workouts = Workout::whereBetween('date', ['2025-01-01', '2025-12-31'])->get();

// Latest workout
$latest = Workout::latest('date')->first();

// Workout with most sets
$biggest = Workout::withCount('sets')
    ->orderBy('sets_count', 'desc')
    ->first();
```

---

## Set Model

**File:** `app/Models/Set.php`

### Properties

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Set extends Model
{
    public $timestamps = false;  // Sets don't need timestamps

    protected $fillable = [
        'workout_id',
        'exercise_name',
        'set_number',
        'reps',
        'weight',
        'unit',
        'notes',
    ];

    protected $casts = [
        'reps' => 'integer',
        'set_number' => 'integer',
        'weight' => 'float',
    ];
}
```

### Fillable Attributes

- **`workout_id`** (int) - Foreign key to workouts table
- **`exercise_name`** (string) - Name of the exercise
- **`set_number`** (int) - Sequential number within workout
- **`reps`** (int|null) - Number of repetitions
- **`weight`** (float|null) - Weight used
- **`unit`** (string) - 'kg' or 'lbs'
- **`notes`** (text|null) - Set-level notes

### Casts

- **`reps`** → integer
- **`set_number`** → integer
- **`weight`** → float (for calculations)

### Timestamps

**Disabled** - Sets don't need their own timestamps since they're tied to the workout's timestamp.

```php
public $timestamps = false;
```

### Relationships

#### `workout()` - BelongsTo Relationship

Returns the workout this set belongs to.

```php
public function workout(): BelongsTo
{
    return $this->belongsTo(Workout::class);
}
```

**Usage:**
```php
$set = Set::find(1);
$workout = $set->workout;  // Workout model

echo "This set belongs to: " . $workout->title;
```

### Usage Examples

#### Creating Sets

```php
// Single set
Set::create([
    'workout_id' => 1,
    'exercise_name' => 'Bench Press',
    'set_number' => 1,
    'reps' => 10,
    'weight' => 60,
    'unit' => 'kg',
]);

// Multiple sets for an exercise
$setNumber = 1;
foreach ($benchPressData as $setData) {
    Set::create([
        'workout_id' => $workout->id,
        'exercise_name' => 'Bench Press',
        'set_number' => $setNumber++,
        'reps' => $setData['reps'],
        'weight' => $setData['weight'],
        'unit' => 'kg',
    ]);
}
```

#### Querying Sets

```php
// All sets for a workout
$sets = Set::where('workout_id', 1)->get();

// All sets for an exercise
$benchSets = Set::where('exercise_name', 'Bench Press')->get();

// Sets with their workout (eager loading)
$sets = Set::with('workout')->get();

// Heaviest set for an exercise
$heaviest = Set::where('exercise_name', 'Deadlift')
    ->orderBy('weight', 'desc')
    ->first();

// Average reps for an exercise
$avgReps = Set::where('exercise_name', 'Squats')
    ->avg('reps');
```

#### Updating Sets

```php
$set = Set::find(1);
$set->update([
    'reps' => 12,
    'weight' => 65,
]);
```

#### Deleting Sets

```php
$set = Set::find(1);
$set->delete();

// Delete all sets for an exercise in a workout
Set::where('workout_id', 1)
    ->where('exercise_name', 'Bench Press')
    ->delete();
```

---

## Model Relationships Summary

```
┌─────────────┐              ┌─────────────┐
│   Workout   │              │     Set     │
├─────────────┤              ├─────────────┤
│ sets()      │──────────────│ workout()   │
│ HasMany     │    1 : N     │ BelongsTo   │
└─────────────┘              └─────────────┘
```

### Accessing Relationships

```php
// From Workout to Sets
$workout = Workout::find(1);
$sets = $workout->sets;                    // Get all sets
$setCount = $workout->sets()->count();     // Count sets
$benchSets = $workout->sets()              // Query sets
    ->where('exercise_name', 'Bench Press')
    ->get();

// From Set to Workout
$set = Set::find(1);
$workout = $set->workout;                  // Get parent workout
$workoutTitle = $set->workout->title;      // Access workout properties
```

---

## Advanced Queries

### Get Exercise History Across All Workouts

```php
$exerciseName = 'Bench Press';

$history = Set::where('exercise_name', $exerciseName)
    ->with('workout:id,date,title')
    ->orderBy('workout_id', 'desc')
    ->get()
    ->map(function ($set) {
        return [
            'date' => $set->workout->date,
            'workout' => $set->workout->title,
            'set_number' => $set->set_number,
            'reps' => $set->reps,
            'weight' => $set->weight,
            'unit' => $set->unit,
        ];
    });
```

### Calculate Total Volume for a Workout

```php
$workout = Workout::find(1);

$totalVolume = $workout->sets
    ->filter(fn($set) => $set->reps && $set->weight)
    ->sum(fn($set) => $set->reps * $set->weight);

echo "Total volume: {$totalVolume} {$workout->sets->first()->unit}";
```

### Find Personal Records

```php
// PR for each exercise (by weight)
$personalRecords = Set::selectRaw('
        exercise_name,
        MAX(weight) as max_weight,
        unit
    ')
    ->groupBy('exercise_name', 'unit')
    ->get();

// PR with context (which workout)
$benchPR = Set::where('exercise_name', 'Bench Press')
    ->with('workout:id,date,title')
    ->orderBy('weight', 'desc')
    ->first();
```

### Workout Statistics

```php
// Average sets per workout
$avgSetsPerWorkout = Set::count() / Workout::count();

// Most common exercises
$popularExercises = Set::selectRaw('
        exercise_name,
        COUNT(*) as total_sets
    ')
    ->groupBy('exercise_name')
    ->orderBy('total_sets', 'desc')
    ->limit(10)
    ->get();

// Weekly workout frequency
$recentWorkouts = Workout::where('date', '>=', now()->subDays(30))
    ->orderBy('date')
    ->get()
    ->groupBy(function ($workout) {
        return $workout->date->format('Y-W');
    });
```

---

## Model Events

You can add model events for automatic actions:

```php
// In Workout model
protected static function boot()
{
    parent::boot();
    
    static::deleting(function ($workout) {
        \Log::info("Deleting workout: {$workout->title}");
    });
    
    static::created(function ($workout) {
        \Log::info("Created workout: {$workout->title}");
    });
}
```

---

## Scopes

Add query scopes for reusable query logic:

```php
// In Workout model
public function scopeRecent($query, $days = 30)
{
    return $query->where('date', '>=', now()->subDays($days));
}

public function scopeWithExerciseCount($query)
{
    return $query->withCount([
        'sets as exercise_count' => function ($q) {
            $q->select(DB::raw('COUNT(DISTINCT exercise_name)'));
        }
    ]);
}

// Usage
$recentWorkouts = Workout::recent(7)->get();
$workoutsWithCounts = Workout::withExerciseCount()->get();
```

---

## Testing Models

### Unit Tests

```php
use Tests\TestCase;
use App\Models\Workout;
use App\Models\Set;

class WorkoutModelTest extends TestCase
{
    public function test_workout_has_sets_relationship()
    {
        $workout = Workout::factory()->create();
        $set = Set::factory()->create(['workout_id' => $workout->id]);
        
        $this->assertTrue($workout->sets->contains($set));
    }
    
    public function test_deleting_workout_deletes_sets()
    {
        $workout = Workout::factory()->create();
        $set = Set::factory()->create(['workout_id' => $workout->id]);
        
        $workout->delete();
        
        $this->assertDatabaseMissing('sets', ['id' => $set->id]);
    }
    
    public function test_get_total_sets()
    {
        $workout = Workout::factory()->create();
        Set::factory()->count(5)->create(['workout_id' => $workout->id]);
        
        $this->assertEquals(5, $workout->getTotalSets());
    }
}
```

---

## Best Practices

### 1. Always Use Eager Loading

```php
// ❌ N+1 Problem
$workouts = Workout::all();
foreach ($workouts as $workout) {
    echo $workout->sets->count();
}

// ✅ Eager Loading
$workouts = Workout::with('sets')->get();
foreach ($workouts as $workout) {
    echo $workout->sets->count();
}
```

### 2. Use Mass Assignment with Validation

```php
// ✅ Good
$validated = $request->validate([
    'date' => 'required|date',
    'title' => 'nullable|string',
]);

$workout = Workout::create($validated);
```

### 3. Use Transactions for Related Models

```php
DB::beginTransaction();
try {
    $workout = Workout::create([...]);
    
    foreach ($sets as $setData) {
        Set::create([
            'workout_id' => $workout->id,
            ...$setData
        ]);
    }
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

### 4. Leverage Relationship Methods

```php
// ✅ Good - uses relationship
$set = $workout->sets()->create([
    'exercise_name' => 'Bench Press',
    'set_number' => 1,
    'reps' => 10,
]);

// ❌ Less clear
$set = Set::create([
    'workout_id' => $workout->id,
    'exercise_name' => 'Bench Press',
    'set_number' => 1,
    'reps' => 10,
]);
```

---

## Troubleshooting

### Mass Assignment Exception

```
Illuminate\Database\Eloquent\MassAssignmentException
Add [column_name] to fillable property
```

**Solution:** Add the column to `$fillable` array in the model.

### Relationship Returns Null

```php
$set = Set::find(1);
$workout = $set->workout;  // NULL
```

**Causes:**
1. Foreign key is null or invalid
2. Related record doesn't exist
3. Relationship method name doesn't match

**Debug:**
```php
dd($set->workout_id);  // Check FK value
dd(Workout::find($set->workout_id));  // Check if workout exists
```

### Cascade Delete Not Working

**Check:**
1. Foreign key constraint exists in migration
2. Foreign keys are enabled in SQLite: `PRAGMA foreign_keys = ON;`
3. Using `$model->delete()` not `Model::where()->delete()`

---

## Additional Resources

- [Laravel Eloquent Documentation](https://laravel.com/docs/eloquent)
- [Eloquent Relationships](https://laravel.com/docs/eloquent-relationships)
- [Query Builder](https://laravel.com/docs/queries)
- [Model Events](https://laravel.com/docs/eloquent#events)

