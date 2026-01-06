# GitHub Actions Workflows

This directory contains CI/CD workflows for automated testing.

## Available Workflows

### 1. `tests.yml` - Separate Jobs (Recommended)
Runs backend and E2E tests in **parallel** as separate jobs.

**Pros:**
- ✅ Faster execution (parallel jobs)
- ✅ Better visualization in GitHub UI
- ✅ Can run independently
- ✅ Clearer failure isolation

**Cons:**
- Uses more runner minutes (2 jobs)

### 2. `tests-combined.yml` - Single Job
Runs all tests in **one sequential job**.

**Pros:**
- ✅ Uses fewer runner minutes
- ✅ Simpler workflow
- ✅ Better for private repos with limited minutes

**Cons:**
- Slower (sequential execution)
- Less granular failure reporting

## Which One to Use?

**Default:** Use `tests.yml` (parallel jobs) for the best developer experience.

**If you have limited GitHub Actions minutes:** Rename `tests-combined.yml` to be the active workflow.

## Workflow Triggers

Both workflows run on:
- Push to `main` or `develop` branches
- Pull requests to `main` or `develop` branches

## What Gets Tested

### Backend Tests (56 tests)
- Model tests (User, Workout, Set)
- Controller tests (Home, Workout, WorkoutUpload)
- API tests (photo retrieval)
- Service tests (ImageProcessing, Anthropic)
- Seeder tests (DemoUserSeeder)

### E2E Tests (10 tests)
- Login flow
- Dashboard display
- Workout list and detail pages

## Test Reports

- **Playwright reports**: Uploaded as artifacts (available for 7 days)
- **Screenshots on failure**: Automatically captured and uploaded
- **Backend test output**: Shown directly in workflow logs

## Local Testing

Before pushing, run tests locally:

```bash
# Backend tests
php artisan test

# E2E tests
npm run test:e2e
```

## Disabling Workflows

To temporarily disable a workflow:
1. Rename the file (e.g., `tests.yml.disabled`)
2. Or add this to the top of the workflow:

```yaml
on:
  workflow_dispatch:  # Only run manually
```

## Environment Variables

The workflows use these defaults:
- PHP: 8.2
- Node: 20
- Database: SQLite (in-memory for CI)
- Browser: Chromium only (faster)

To customize, edit the workflow files directly.

