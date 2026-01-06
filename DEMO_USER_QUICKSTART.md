# Demo User Quick Start Guide

## TL;DR

```bash
# Seed demo data
php artisan demo:seed

# Login credentials
Email: demo@fitandfocused.com
Password: demo123
```

## What You Get

- ✅ **15 pre-seeded workouts** spanning 6 weeks
- ✅ **139 sets** across varied exercises
- ✅ **Mix of kg and lbs** for unit testing
- ✅ **Placeholder images** (auto-generated black images)
- ✅ **Varied data**: notes, different rep ranges, bodyweight exercises, PRs
- ✅ **Workout streak** (3+ consecutive days)
- ✅ **No API calls** during testing
- ✅ **No file uploads** needed

## Quick Commands

```bash
# Create/update demo data
php artisan demo:seed

# Reset demo data (delete and recreate)
php artisan demo:seed --fresh

# Run all seeders (includes demo user in local/testing env)
php artisan db:seed
```

## Browser Testing Flow

1. **Start app**: `php artisan serve`
2. **Seed data**: `php artisan demo:seed`
3. **Navigate to**: `http://localhost:8000/login`
4. **Login**: demo@fitandfocused.com / demo123
5. **Test features**: Browse workouts, view stats, check detail pages

## Test Scenarios

- ✅ Login flow
- ✅ Dashboard stats (streak, weekly workouts, total volume)
- ✅ Workout list (15 workouts)
- ✅ Workout detail pages (photos, exercises, sets)
- ✅ Filtering and sorting (if implemented)
- ✅ Analytics calculations

## Editing Demo Data

**File**: `database/seeders/data/demo-workouts.json`

Edit the JSON to add/modify workouts, then run:
```bash
php artisan demo:seed --fresh
```

## Files Created

- `database/seeders/data/demo-workouts.json` - Data definition
- `database/seeders/DemoUserSeeder.php` - Seeder logic
- `app/Console/Commands/SeedDemoUser.php` - Artisan command
- `storage/app/public/uploads/demo/*.jpg` - Placeholder images (auto-generated)
- `docs/DEMO_USER.md` - Full documentation

## Benefits for MCP Chrome Testing

🎯 **Perfect for browser automation** because:
- No file upload dialogs to handle
- Deterministic data (same every time)
- Fast (no AI API calls)
- Uses real production UI
- Easy to reset between test runs

## Full Documentation

See `docs/DEMO_USER.md` for complete details.

