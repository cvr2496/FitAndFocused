# Database Documentation

## Overview

The FitAndFocused workout logger uses SQLite as its database engine. The database stores workout data extracted from photos via OCR, maintaining relationships between workouts and individual sets.

## Database Schema

### Entity Relationship Diagram

```
┌─────────────────┐         ┌─────────────────┐
│    workouts     │         │      sets       │
├─────────────────┤         ├─────────────────┤
│ id (PK)         │────┐    │ id (PK)         │
│ date            │    │    │ workout_id (FK) │
│ title           │    └───<│ exercise_name   │
│ photo_path      │         │ set_number      │
│ notes           │         │ reps            │
│ created_at      │         │ weight          │
│ updated_at      │         │ unit            │
└─────────────────┘         │ notes           │
                            └─────────────────┘

Relationship: One workout has many sets (1:N)
Cascade Delete: Deleting a workout deletes all its sets
```

## Tables

### `workouts`

The main table storing workout metadata and date information.

| Column      | Type      | Nullable | Default | Description                                    |
|-------------|-----------|----------|---------|------------------------------------------------|
| id          | INTEGER   | NO       | AUTO    | Primary key                                    |
| date        | DATE      | NO       | -       | Date the workout was performed (YYYY-MM-DD)    |
| title       | VARCHAR   | YES      | NULL    | Workout title (e.g., "Chest and Triceps")      |
| photo_path  | VARCHAR   | YES      | NULL    | Path to original workout photo in storage      |
| notes       | TEXT      | YES      | NULL    | Workout-level notes and observations           |
| created_at  | TIMESTAMP | NO       | NOW()   | Record creation timestamp                      |
| updated_at  | TIMESTAMP | NO       | NOW()   | Record last update timestamp                   |

**Indexes:**
- Primary key on `id`
- No additional indexes (small dataset expected)

**Sample Data:**
```sql
INSERT INTO workouts (date, title, photo_path, notes) VALUES
  ('2025-12-28', 'Chest and Triceps', 'uploads/original/2025-12-28-abc123.jpg', 'Good workout! Felt strong today');
```

---

### `sets`

Individual exercise sets belonging to workouts. Each row represents a single set.

| Column        | Type         | Nullable | Default | Description                                    |
|---------------|--------------|----------|---------|------------------------------------------------|
| id            | INTEGER      | NO       | AUTO    | Primary key                                    |
| workout_id    | INTEGER      | NO       | -       | Foreign key to workouts.id (CASCADE DELETE)    |
| exercise_name | VARCHAR      | NO       | -       | Name of the exercise (e.g., "Bench Press")     |
| set_number    | INTEGER      | NO       | -       | Sequential set number within the workout       |
| reps          | INTEGER      | YES      | NULL    | Number of repetitions performed                |
| weight        | DECIMAL(6,2) | YES      | NULL    | Weight used (up to 9999.99)                    |
| unit          | VARCHAR      | NO       | 'kg'    | Weight unit: 'kg' or 'lbs'                     |
| notes         | TEXT         | YES      | NULL    | Set-level notes (e.g., "felt easy", "failed")  |

**Indexes:**
- Primary key on `id`
- Index on `workout_id` (for faster joins with workouts)
- Index on `exercise_name` (for history queries across workouts)

**Constraints:**
- Foreign key: `workout_id` references `workouts(id)` ON DELETE CASCADE
- `unit` should be validated at application level to be 'kg' or 'lbs'

**Sample Data:**
```sql
INSERT INTO sets (workout_id, exercise_name, set_number, reps, weight, unit, notes) VALUES
  (1, 'Bench Press', 1, 10, 60.00, 'kg', NULL),
  (1, 'Bench Press', 2, 8, 70.00, 'kg', NULL),
  (1, 'Incline Bench Press', 3, 10, 50.00, 'kg', NULL);
```

## Relationships

### One-to-Many: Workout → Sets

A workout can have many sets, but each set belongs to exactly one workout.

**Database Level:**
- Foreign key constraint: `sets.workout_id → workouts.id`
- ON DELETE CASCADE: When a workout is deleted, all its sets are automatically deleted

**Application Level (Laravel Eloquent):**
```php
// In Workout model
public function sets(): HasMany
{
    return $this->hasMany(Set::class);
}

// In Set model
public function workout(): BelongsTo
{
    return $this->belongsTo(Workout::class);
}
```

**Usage Examples:**
```php
// Get all sets for a workout
$workout = Workout::find(1);
$sets = $workout->sets;

// Get workout for a set
$set = Set::find(1);
$workout = $set->workout;

// Eager loading to avoid N+1 queries
$workouts = Workout::with('sets')->get();

// Count sets
$totalSets = $workout->sets()->count();
```

## Cascade Delete Behavior

**What is Cascade Delete?**

Cascade delete is a database feature that automatically deletes related child records when a parent record is deleted. In this application, when you delete a workout, all associated sets are automatically removed.

**Why Use It?**

Sets without a workout are meaningless orphaned data. Cascade delete ensures data integrity by preventing orphaned records.

**Example:**

```php
// Without cascade delete (old way)
$workout = Workout::find(1);
Set::where('workout_id', 1)->delete();
$workout->delete();

// With cascade delete (our way)
$workout = Workout::find(1);
$workout->delete();  // ✅ Automatically deletes all sets too!
```

**Migration Definition:**
```php
$table->foreignId('workout_id')
      ->constrained()           // References workouts(id)
      ->onDelete('cascade');    // Delete sets when workout is deleted
```

## Data Types

### Date Format
- Database stores dates as `DATE` type
- Application uses format: `YYYY-MM-DD`
- Laravel casts to Carbon instances for easy manipulation

### Decimal Precision
- Weight stored as `DECIMAL(6,2)`
- Allows values from -9999.99 to 9999.99
- Sufficient for workout weights in kg or lbs

### Timestamps
- `created_at` and `updated_at` managed automatically by Laravel
- Stored as UNIX timestamps
- Automatically cast to Carbon instances

## Querying Examples

### Get all workouts with exercise count
```php
$workouts = Workout::with('sets')
    ->orderBy('date', 'desc')
    ->get()
    ->map(function ($workout) {
        return [
            'id' => $workout->id,
            'date' => $workout->date,
            'title' => $workout->title,
            'total_sets' => $workout->sets->count(),
            'total_exercises' => $workout->sets->pluck('exercise_name')->unique()->count(),
        ];
    });
```

### Get all sets for a specific exercise across all workouts
```php
$benchPressSets = Set::where('exercise_name', 'Bench Press')
    ->with('workout')
    ->orderBy('workout_id', 'desc')
    ->get();
```

### Get workout with exercises grouped
```php
$workout = Workout::with('sets')->find(1);
$exercises = $workout->sets()
    ->orderBy('set_number')
    ->get()
    ->groupBy('exercise_name')
    ->map(function ($sets) {
        return [
            'name' => $sets->first()->exercise_name,
            'sets' => $sets->values(),
        ];
    })
    ->values();
```

### Find heaviest lift for an exercise
```php
$heaviestBenchPress = Set::where('exercise_name', 'Bench Press')
    ->orderBy('weight', 'desc')
    ->first();
```

## Migrations

### Running Migrations

```bash
# Run all pending migrations
php artisan migrate

# Rollback last batch
php artisan migrate:rollback

# Reset and re-run all migrations
php artisan migrate:fresh

# Reset and seed
php artisan migrate:fresh --seed
```

### Migration Files

1. **`2025_12_29_170032_create_workouts_table.php`**
   - Creates the `workouts` table
   - Defines all workout columns and constraints

2. **`2025_12_29_170041_create_sets_table.php`**
   - Creates the `sets` table
   - Defines foreign key relationship with cascade delete
   - Creates indexes for performance

## Testing the Database

### Using Tinker

```bash
php artisan tinker
```

```php
// Create a test workout
$workout = App\Models\Workout::create([
    'date' => '2025-12-29',
    'title' => 'Test Workout',
    'notes' => 'This is a test'
]);

// Create test sets
App\Models\Set::create([
    'workout_id' => $workout->id,
    'exercise_name' => 'Bench Press',
    'set_number' => 1,
    'reps' => 10,
    'weight' => 60,
    'unit' => 'kg'
]);

// Verify relationships work
$workout->sets;  // Should return collection of sets
$workout->sets()->count();  // Should return 1

// Test cascade delete
$workout->delete();  // Should delete workout AND all sets

// Verify sets were deleted
App\Models\Set::where('workout_id', $workout->id)->count();  // Should be 0
```

### Database Inspection

```bash
# View all tables
php artisan tinker --execute="
DB::select('SELECT name FROM sqlite_master WHERE type=\'table\'');
"

# Count records
php artisan tinker --execute="
echo 'Workouts: ' . App\Models\Workout::count() . PHP_EOL;
echo 'Sets: ' . App\Models\Set::count() . PHP_EOL;
"

# View latest workout with sets
php artisan tinker --execute="
\$workout = App\Models\Workout::with('sets')->latest()->first();
print_r(\$workout->toArray());
"
```

## Best Practices

### 1. Always Use Eager Loading
```php
// ❌ Bad (N+1 query problem)
$workouts = Workout::all();
foreach ($workouts as $workout) {
    echo $workout->sets->count();
}

// ✅ Good (single query with JOIN)
$workouts = Workout::with('sets')->get();
foreach ($workouts as $workout) {
    echo $workout->sets->count();
}
```

### 2. Use Transactions for Multiple Related Inserts
```php
DB::beginTransaction();
try {
    $workout = Workout::create([...]);
    
    foreach ($exercises as $exercise) {
        Set::create([
            'workout_id' => $workout->id,
            ...
        ]);
    }
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

### 3. Validate Data Before Insertion
```php
$validated = $request->validate([
    'date' => 'required|date',
    'title' => 'nullable|string|max:255',
    'exercises.*.sets.*.reps' => 'nullable|integer|min:0',
    'exercises.*.sets.*.weight' => 'nullable|numeric|min:0',
    'exercises.*.sets.*.unit' => 'required|in:kg,lbs',
]);
```

### 4. Use Model Methods for Business Logic
```php
// In Workout model
public function getTotalVolume(): float
{
    return $this->sets()
        ->whereNotNull('reps')
        ->whereNotNull('weight')
        ->get()
        ->sum(function ($set) {
            return $set->reps * $set->weight;
        });
}
```

## Backup and Maintenance

### Backup Database
```bash
# SQLite backup is simple - just copy the file
cp database/database.sqlite database/backups/database-$(date +%Y%m%d).sqlite
```

### Optimize Database
```bash
php artisan tinker --execute="
DB::statement('VACUUM');  // Reclaim space
DB::statement('ANALYZE'); // Update statistics
"
```

## Troubleshooting

### Foreign Key Constraint Errors
```bash
# Check if foreign keys are enabled
php artisan tinker --execute="
DB::select('PRAGMA foreign_keys');
"

# If disabled, enable them
DB::statement('PRAGMA foreign_keys = ON');
```

### Database Locked Errors
SQLite can lock during writes. Solutions:
1. Close other connections to the database
2. Increase busy timeout: `DB::statement('PRAGMA busy_timeout = 5000');`
3. Use transactions properly

### Orphaned Records (If Cascade Didn't Work)
```php
// Find orphaned sets
$orphanedSets = Set::whereNotIn('workout_id', Workout::pluck('id'))->get();

// Clean them up
Set::whereNotIn('workout_id', Workout::pluck('id'))->delete();
```

## Additional Resources

- [Laravel Eloquent Documentation](https://laravel.com/docs/eloquent)
- [Laravel Migrations](https://laravel.com/docs/migrations)
- [SQLite Documentation](https://www.sqlite.org/docs.html)
- [Database Relationships](https://laravel.com/docs/eloquent-relationships)

