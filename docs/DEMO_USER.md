# Demo User Documentation

## Overview

The demo user feature provides a pre-seeded account with realistic workout data for testing and browser automation purposes. This allows you to test the application's UI and features without needing to upload photos or make API calls.

## Demo User Credentials

```
Email: demo@fitandfocused.com
Password: demo123
```

⚠️ **Note**: These credentials are for testing purposes only. The demo user should only be used in local/testing environments.

## Setup

### Initial Seeding

There are two ways to seed the demo user data:

#### Option 1: Using the dedicated command (Recommended)

```bash
php artisan demo:seed
```

This will:
- Create the demo user account (if it doesn't exist)
- Remove any existing demo workouts
- Create 15 new workouts with varied exercises, dates, and data
- Generate placeholder images for each workout

#### Option 2: Using the database seeder

```bash
php artisan db:seed
```

This runs all seeders, including the `DemoUserSeeder` (only in local/testing environments).

### Resetting Demo Data

To completely reset the demo user's data:

```bash
php artisan demo:seed --fresh
```

The `--fresh` flag will delete all existing demo workouts before creating new ones.

## Demo Data Structure

The demo user comes pre-loaded with **15 workouts** spanning approximately 6 weeks, including:

### Workout Types
- Chest and Triceps
- Back and Biceps
- Leg Day (multiple variations)
- Shoulders and Arms
- Full Body Strength
- Arms and Abs
- PR Days (Personal Record testing)
- Light Recovery Sessions

### Data Characteristics

The demo data includes varied characteristics to test different features:

- **Mixed units**: Both kg and lbs
- **Varied rep ranges**: 1-60 reps (testing edge cases)
- **Different weights**: From bodyweight (0 kg) to heavy loads (160 kg)
- **Workout notes**: Some workouts have notes, others don't
- **Set notes**: Various notes like "felt heavy", "PR!", "bodyweight", etc.
- **Date distribution**: Creates a 3-day workout streak for testing streak calculation
- **File formats**: Mix of `.jpg` and `.jpeg` extensions

### Example Workouts

1. **Recent workouts** (this week): Create a streak for testing
2. **Varied exercises**: Bench Press, Squats, Deadlifts, Pull-ups, and more
3. **Progressive data**: Some exercises show progression over time
4. **Edge cases**: Single-set workouts, bodyweight exercises, very high reps

## Editing Demo Data

The demo workout data is stored in a JSON file that can be easily edited without touching PHP code:

**File location**: `database/seeders/data/demo-workouts.json`

### JSON Structure

```json
{
  "user": {
    "name": "Demo User",
    "email": "demo@fitandfocused.com",
    "password": "demo123"
  },
  "workouts": [
    {
      "date": "2025-01-06",
      "title": "Chest and Triceps",
      "photo_filename": "demo-workout-01.jpg",
      "notes": null,
      "exercises": [
        {
          "name": "Bench Press",
          "sets": [
            {"reps": 10, "weight": 60, "unit": "kg", "notes": null},
            {"reps": 8, "weight": 70, "unit": "kg", "notes": null}
          ]
        }
      ]
    }
  ]
}
```

### Adding New Workouts

1. Open `database/seeders/data/demo-workouts.json`
2. Add a new workout object to the `workouts` array
3. Specify a unique `photo_filename` (mix `.jpg` and `.jpeg` extensions)
4. Run `php artisan demo:seed` to regenerate the data

### Modifying Existing Workouts

1. Edit the JSON file directly
2. Change dates, exercises, sets, weights, etc.
3. Run `php artisan demo:seed` to apply changes

## Browser Testing Workflow

### Setup for Browser Automation

1. Ensure the application is running:
   ```bash
   php artisan serve
   ```

2. Seed the demo data:
   ```bash
   php artisan demo:seed
   ```

3. Access the app at `http://localhost:8000` (or your configured URL)

### Test Scenarios

#### 1. Login Flow
```
1. Navigate to /login
2. Fill in: demo@fitandfocused.com / demo123
3. Click "Log in"
4. Verify redirect to /home
```

#### 2. Dashboard/Home Page
```
1. Verify stats display:
   - Weekly workouts count
   - Workout streak (should be 3+)
   - Total volume in kg
2. Verify recent workouts list (5 most recent)
3. Verify each workout card shows:
   - Date
   - Title
   - Exercise count
   - Total volume
```

#### 3. Workout List
```
1. Navigate to /workouts
2. Verify all 15 workouts appear
3. Test sorting (if implemented)
4. Test filtering (if implemented)
5. Click on a workout to view details
```

#### 4. Workout Detail Page
```
1. Navigate to /workouts/{id}
2. Verify photo displays (placeholder image)
3. Verify workout title and date
4. Verify exercises list with all sets
5. Verify set data (reps, weight, unit, notes)
6. Test any interactive elements
```

#### 5. Stats and Analytics
```
1. Verify weekly workout count calculation
2. Verify streak calculation (consecutive days)
3. Verify total volume calculation (in kg)
4. Test any filtering by date range
```

### Resetting Between Tests

To ensure consistent test results, reset the demo data before each test run:

```bash
php artisan demo:seed --fresh
```

This guarantees the exact same data state for each test execution.

## Placeholder Images

All demo workouts use automatically generated placeholder images:

- **Resolution**: 800x600 pixels
- **Color**: Black background with white text
- **Text**: "Demo Workout Image"
- **Format**: JPEG (works for both `.jpg` and `.jpeg` extensions)
- **Location**: `storage/app/public/uploads/demo/`

These images are generated automatically by the seeder and don't need to be manually created.

## Benefits for Testing

1. **No file upload complexity**: Skip the hardest part to automate in browsers
2. **Deterministic**: Same data every test run (unless modified)
3. **Fast**: No API calls, instant page loads
4. **Real UI**: Tests actual production interface
5. **Comprehensive**: Can test viewing, navigation, stats, filtering
6. **Easy reset**: Re-run seeder to restore clean state
7. **Version control friendly**: JSON diffs are readable

## Troubleshooting

### Demo user already exists
The seeder is idempotent - it will find the existing user and recreate their workouts. Use `--fresh` flag if needed.

### Images not displaying
Ensure the storage link exists:
```bash
php artisan storage:link
```

### JSON parse error
Validate your JSON syntax:
```bash
php -r "json_decode(file_get_contents('database/seeders/data/demo-workouts.json'));"
```

### Permission issues
Ensure storage directories are writable:
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

## Environment Configuration

Add these to your `.env.example` for documentation:

```env
# Demo User Credentials (for testing)
DEMO_EMAIL=demo@fitandfocused.com
DEMO_PASSWORD=demo123
```

## Security Notes

- Demo user is **only seeded in local/testing environments**
- Password is hashed in the database
- Do NOT use demo credentials in production
- Demo data should not contain sensitive information

## Future Enhancements

Potential improvements to the demo user feature:

- Multiple demo users with different data profiles (beginner, advanced, etc.)
- Command to export real workouts as demo data for regression testing
- Parameterized seeding (e.g., `--count=5` to create only 5 workouts)
- Random data generation using Faker for varied test scenarios
- Demo mode indicator in UI (badge or ribbon)

