# FitAndFocused Documentation

Welcome to the FitAndFocused documentation! This folder contains comprehensive technical documentation for the workout logging application.

## 📚 Documentation Index

### Core Documentation

- **[DATABASE.md](DATABASE.md)** - Complete database schema, relationships, and SQL queries
  - Entity relationship diagrams
  - Table structures and constraints
  - Cascade delete behavior
  - Query examples and best practices
  
- **[MODELS.md](MODELS.md)** - Eloquent models and ORM usage
  - Workout and Set models
  - Relationships and methods
  - Advanced queries and scopes
  - Testing examples

- **[API.md](API.md)** - API endpoints and controllers
  - Route definitions
  - Request/response formats
  - Controller methods
  - Frontend integration

### Quick Links

| Topic | File | Description |
|-------|------|-------------|
| Schema | [DATABASE.md](DATABASE.md#database-schema) | Tables and relationships |
| Relationships | [DATABASE.md](DATABASE.md#relationships) | One-to-many, cascade delete |
| Models | [MODELS.md](MODELS.md) | Workout and Set models |
| Queries | [DATABASE.md](DATABASE.md#querying-examples) | Common database queries |
| API Routes | [API.md](API.md#routes) | HTTP endpoints |
| Controllers | [API.md](API.md#controllers) | Backend logic |

## 🚀 Quick Start

### For Developers

1. **Understanding the Schema**
   ```bash
   # View database structure
   cat docs/DATABASE.md
   
   # Run migrations
   php artisan migrate
   
   # Inspect with tinker
   php artisan tinker
   >>> Workout::with('sets')->first()
   ```

2. **Working with Models**
   ```bash
   # Read model documentation
   cat docs/MODELS.md
   
   # Create test data
   php artisan tinker
   >>> $workout = Workout::create(['date' => '2025-12-29', 'title' => 'Test'])
   >>> $workout->sets()->create(['exercise_name' => 'Bench Press', ...])
   ```

3. **Testing the API**
   ```bash
   # Read API documentation
   cat docs/API.md
   
   # Test upload endpoint
   curl -X POST http://fitandfocused.test/api/workouts/upload \
     -F "photo=@workout.jpg"
   ```

### For Database Administrators

- **Backup**: `cp database/database.sqlite backups/db-$(date +%Y%m%d).sqlite`
- **Restore**: `cp backups/db-YYYYMMDD.sqlite database/database.sqlite`
- **Optimize**: Run `VACUUM` and `ANALYZE` in tinker
- **Inspect**: Use DB Browser for SQLite or `php artisan tinker`

## 🏗️ Architecture Overview

```
┌─────────────────────────────────────────────────────┐
│                  Frontend (React)                    │
│              resources/js/pages/workouts/            │
│         - upload.tsx (Photo upload)                  │
│         - verify.tsx (Data verification)             │
│         - index.tsx (Workout list)                   │
│         - show.tsx (Workout details)                 │
└────────────────────┬────────────────────────────────┘
                     │ Inertia.js
┌────────────────────┴────────────────────────────────┐
│            Controllers (Laravel)                     │
│         app/Http/Controllers/                        │
│         - WorkoutUploadController.php                │
│         - WorkoutController.php                      │
└────────────────────┬────────────────────────────────┘
                     │ Eloquent ORM
┌────────────────────┴────────────────────────────────┐
│              Models (Eloquent)                       │
│              app/Models/                             │
│              - Workout.php                           │
│              - Set.php                               │
└────────────────────┬────────────────────────────────┘
                     │ Database Abstraction
┌────────────────────┴────────────────────────────────┐
│            Database (SQLite)                         │
│         database/database.sqlite                     │
│         - workouts table                             │
│         - sets table (with FK cascade)               │
└──────────────────────────────────────────────────────┘
```

## 🗄️ Database Design Philosophy

### One Row Per Set Approach

We chose to store each set as a separate database row rather than grouping sets by exercise. This design decision offers several advantages:

**Benefits:**
- ✅ Easy to query individual sets
- ✅ Standard SQL approach (normalized)
- ✅ Flexible for future analytics
- ✅ Simple to filter/sort/aggregate

**Trade-offs:**
- More rows in the database
- Slightly more complex grouping queries

**Example:**
```
Instead of:
workout_id | exercise | sets_json
1          | Bench    | "[{reps:10,weight:60}, {reps:8,weight:70}]"

We use:
workout_id | exercise | set_number | reps | weight
1          | Bench    | 1          | 10   | 60
1          | Bench    | 2          | 8    | 70
```

This makes queries like "all sets where weight > 80kg" trivial to write and fast to execute.

## 🔗 Relationships and Data Integrity

### Cascade Delete

When you delete a workout, all its sets are automatically deleted:

```php
$workout = Workout::find(1);
$workout->delete();  // Also deletes all sets where workout_id = 1
```

This is enforced at the database level with a foreign key constraint:

```sql
FOREIGN KEY (workout_id) REFERENCES workouts(id) ON DELETE CASCADE
```

**Why this matters:**
- Prevents orphaned data (sets without a workout)
- Maintains data integrity automatically
- Simplifies application code (no manual cleanup)

## 📊 Data Flow

```
1. User uploads photo
   └─> WorkoutUploadController@upload
       └─> AnthropicService extracts data
           └─> Returns to verify.tsx

2. User verifies & saves
   └─> WorkoutUploadController@save
       ├─> Creates Workout record
       └─> Creates Set records (in transaction)
           └─> Redirects to show page

3. User views workouts
   └─> WorkoutController@index
       └─> Returns workout list with stats

4. User views details
   └─> WorkoutController@show
       └─> Returns workout with exercises grouped
```

## 🧪 Testing

### Database Testing

```bash
# Run all tests
php artisan test

# Test specific feature
php artisan test --filter=WorkoutTest

# Test with database refresh
php artisan test --migrate

# Interactive testing
php artisan tinker
```

### Manual Testing Checklist

See the main [Testing Guide](../TEST_UPLOAD.md) for step-by-step manual testing instructions.

## 📝 Common Tasks

### Create Test Workout

```php
php artisan tinker

$workout = Workout::create([
    'date' => now()->toDateString(),
    'title' => 'Test Workout',
    'notes' => 'Testing the system'
]);

$workout->sets()->createMany([
    ['exercise_name' => 'Bench Press', 'set_number' => 1, 'reps' => 10, 'weight' => 60, 'unit' => 'kg'],
    ['exercise_name' => 'Bench Press', 'set_number' => 2, 'reps' => 8, 'weight' => 70, 'unit' => 'kg'],
    ['exercise_name' => 'Squats', 'set_number' => 3, 'reps' => 12, 'weight' => 80, 'unit' => 'kg'],
]);

echo "Created workout ID: {$workout->id}\n";
```

### Query Workout History

```php
// Get all workouts this month
$thisMonth = Workout::whereMonth('date', now()->month)
    ->whereYear('date', now()->year)
    ->with('sets')
    ->get();

// Get exercise history
$benchHistory = Set::where('exercise_name', 'Bench Press')
    ->with('workout:id,date,title')
    ->orderBy('workout_id', 'desc')
    ->get();

// Find personal records
$prs = Set::selectRaw('
        exercise_name,
        MAX(weight) as max_weight,
        MAX(reps) as max_reps,
        unit
    ')
    ->groupBy('exercise_name', 'unit')
    ->get();
```

### Reset Database

```bash
# WARNING: This deletes all data!
php artisan migrate:fresh

# With seeder (if you have one)
php artisan migrate:fresh --seed
```

## 🔧 Troubleshooting

### Common Issues

| Issue | Solution | Reference |
|-------|----------|-----------|
| Foreign key errors | Check `PRAGMA foreign_keys = ON` | [DATABASE.md](DATABASE.md#troubleshooting) |
| N+1 query problem | Use eager loading: `with('sets')` | [MODELS.md](MODELS.md#best-practices) |
| Mass assignment error | Add column to `$fillable` | [MODELS.md](MODELS.md#troubleshooting) |
| Cascade delete not working | Use `$model->delete()` not `::delete()` | [DATABASE.md](DATABASE.md#cascade-delete-behavior) |
| API validation fails | Check request structure | [API.md](API.md#validation) |

### Debug Commands

```bash
# Check database connection
php artisan tinker --execute="DB::connection()->getPdo();"

# Count records
php artisan tinker --execute="
echo 'Workouts: ' . Workout::count() . PHP_EOL;
echo 'Sets: ' . Set::count() . PHP_EOL;
"

# View latest workout
php artisan tinker --execute="
\$w = Workout::with('sets')->latest()->first();
print_r(\$w->toArray());
"

# Check foreign keys
php artisan tinker --execute="
DB::select('PRAGMA foreign_keys');
"
```

## 🎯 Best Practices Summary

### Database
- ✅ Use transactions for related inserts
- ✅ Always eager load relationships to avoid N+1
- ✅ Rely on cascade delete for data integrity
- ✅ Use proper indexes (already set up)

### Models
- ✅ Validate before mass assignment
- ✅ Use relationship methods
- ✅ Keep business logic in models
- ✅ Write unit tests for model methods

### API
- ✅ Validate all inputs
- ✅ Return consistent response formats
- ✅ Use HTTP status codes correctly
- ✅ Log errors properly

## 📚 Further Reading

- [Laravel Documentation](https://laravel.com/docs)
- [Eloquent ORM](https://laravel.com/docs/eloquent)
- [SQLite Documentation](https://www.sqlite.org/docs.html)
- [Inertia.js Guide](https://inertiajs.com/)

## 🤝 Contributing

When adding new features:

1. Update the relevant documentation file
2. Add examples and usage patterns
3. Document any new database columns or tables
4. Include troubleshooting notes
5. Update this README if adding new docs

## 📞 Getting Help

If you encounter issues:

1. Check the [Troubleshooting](#troubleshooting) section
2. Review the specific documentation file for your concern
3. Use `php artisan tinker` to inspect the database state
4. Check Laravel logs: `storage/logs/laravel.log`

---

**Last Updated:** December 29, 2025  
**Database Version:** Initial schema (migrations `2025_12_29_*`)  
**Laravel Version:** 12.x

