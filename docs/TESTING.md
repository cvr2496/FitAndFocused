# Testing Guide

## Overview

This application has a comprehensive testing suite covering backend logic and frontend E2E flows. All tests use the demo user seeder for deterministic, reproducible results.

## Test Structure

```
tests/
├── Feature/           # Feature/integration tests (Laravel)
│   ├── Models/       # Model relationship and behavior tests
│   ├── Controllers/  # Controller tests  
│   ├── Api/          # API endpoint tests
│   └── Seeders/      # Seeder tests
├── Unit/             # Unit tests (isolated components)
│   └── Services/     # Service class tests
└── e2e/              # End-to-end browser tests (Playwright)
    ├── helpers/      # Test helper functions
    ├── setup.ts      # Global test setup
    ├── login.spec.ts
    ├── dashboard.spec.ts
    └── workouts.spec.ts
```

## Backend Tests (Pest/PHPUnit)

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test --filter=HomeControllerTest

# Run only feature tests
php artisan test tests/Feature

# Run only unit tests
php artisan test tests/Unit

# Run with coverage
php artisan test --coverage

# Stop on first failure
php artisan test --stop-on-failure
```

### Test Categories

#### Model Tests (`tests/Feature/Models/`)

Test Eloquent models, relationships, and data handling:
- User → Workouts relationship
- Workout → Sets relationship
- Cascading deletes
- Date casting
- Attribute access

**Example:**
```php
test('workout belongs to user', function () {
    $user = actingAsDemo();
    $workout = $user->workouts()->first();
    
    expect($workout->user)->toBeInstanceOf(User::class);
});
```

#### Controller Tests (`tests/Feature/Controllers/`)

Test HTTP responses, Inertia rendering, and business logic:
- HomeController (stats calculation, recent workouts)
- WorkoutController (index, show, destroy)
- WorkoutUploadController (verification flow, save)

**Example:**
```php
test('home page displays stats', function () {
    $user = actingAsDemo();
    
    $response = $this->get(route('home'));
    
    $response->assertInertia(fn (Assert $page) => 
        $page->has('stats')
            ->has('stats.weeklyWorkouts')
            ->has('stats.streak')
    );
});
```

#### API Tests (`tests/Feature/Api/`)

Test API endpoints:
- Photo retrieval
- Upload validation
- Save workflow

#### Service Tests (`tests/Unit/Services/`)

Test service classes in isolation:
- ImageProcessingService
- AnthropicService (without actual API calls)

#### Seeder Tests (`tests/Feature/Seeders/`)

Test the demo user seeder:
- Creates user correctly
- Creates 15 workouts
- Creates 139 sets
- Generates images
- Is idempotent

### Test Helpers

Available helper functions in `tests/Pest.php`:

```php
// Authenticate as demo user
$user = actingAsDemo();

// Seed demo data (fresh)
seedDemo();
```

### Writing New Tests

1. **Feature tests** should extend `Tests\TestCase` and use `RefreshDatabase` trait (already configured in Pest.php)
2. **Use demo user** for consistent data via `actingAsDemo()` helper
3. **Follow naming convention**: `test('it does something', function () { ... })`
4. **Use descriptive assertions**: `expect($value)->toBe($expected)`

## E2E Tests (Playwright)

### Setup

```bash
# Install Playwright (already done)
npm install -D @playwright/test

# Install browsers
npx playwright install chromium

# Install all browsers (optional)
npx playwright install
```

### Running E2E Tests

```bash
# Run all E2E tests
npm run test:e2e

# Run in headed mode (see browser)
npm run test:e2e:headed

# Debug mode
npm run test:e2e:debug

# Run specific test file
npx playwright test login

# Run with UI mode
npx playwright test --ui
```

### Test Files

- `login.spec.ts` - Login flow, authentication
- `dashboard.spec.ts` - Home page, stats display  
- `workouts.spec.ts` - Workout list and detail pages

### Helper Functions

Located in `tests/e2e/helpers/auth.ts`:

```typescript
import { loginAsDemo } from './helpers/auth';

// Login as demo user
await loginAsDemo(page);
```

### Global Setup

The `tests/e2e/setup.ts` file runs before all tests:
- Seeds demo user data via `php artisan demo:seed --fresh`
- Ensures clean, consistent state for each test run

### Writing New E2E Tests

1. Import test helpers: `import { test, expect } from '@playwright/test';`
2. Use `loginAsDemo()` for authenticated tests
3. Use semantic selectors when possible
4. Add waits for dynamic content: `await page.waitForLoadState('networkidle')`
5. Make assertions clear and specific

**Example:**
```typescript
test('can view workout list', async ({ page }) => {
  await loginAsDemo(page);
  await page.goto('/workouts');
  
  await expect(page).toHaveURL('/workouts');
  
  const pageContent = await page.textContent('body');
  expect(pageContent).toContain('Workout');
});
```

## Demo User & Test Data

### Demo User Credentials
```
Email: demo@fitandfocused.com
Password: demo123
```

### Demo Data Characteristics
- **15 workouts** spanning 6 weeks (Dec 11, 2024 - Jan 6, 2025)
- **139 total sets** across all workouts
- **Mixed units**: Both kg and lbs
- **Varied exercises**: Chest, Back, Legs, Shoulders, Arms
- **Workout streak**: 3+ consecutive days for streak testing
- **Notes**: Some sets/workouts have notes, others don't
- **Image formats**: Mix of `.jpg` and `.jpeg`

### Resetting Demo Data

```bash
# Reset demo data before tests
php artisan demo:seed --fresh
```

E2E tests automatically reset demo data via global setup.

## Test Execution Times

**Expected durations:**
- **All backend tests**: ~3-5 seconds
- **Model tests**: ~2 seconds
- **Controller tests**: ~3 seconds
- **API tests**: ~1 second
- **Service tests**: <1 second
- **Seeder tests**: ~1 second
- **E2E tests**: ~30-60 seconds (includes browser startup)

## Coverage

### Current Test Coverage

- ✅ 17 Model tests
- ✅ 24 Controller tests
- ✅ 3 API tests
- ✅ 5 Service tests
- ✅ 7 Seeder tests
- ✅ 10 E2E tests

**Total: 66 tests**

### Coverage Goals

- Models: 100% (all relationships, behaviors)
- Controllers: >90% (all public methods, edge cases)
- APIs: >80% (happy paths, validation, errors)
- Services: 100% (structure verified, mocked external calls)
- E2E: Smoke tests only (critical user paths)

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  backend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: php artisan test
  
  e2e:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Install dependencies
        run: |
          composer install
          npm install
      - name: Setup demo data
        run: php artisan demo:seed --fresh
      - name: Run Playwright tests
        run: npx playwright test
      - name: Upload test results
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: playwright-report
          path: playwright-report/
```

## Debugging Tests

### Backend Tests

```bash
# Use dd() or dump() in tests
test('debug example', function () {
    $user = actingAsDemo();
    dd($user->workouts()->count()); // Dump and die
});

# Run with verbose output
php artisan test --verbose
```

### E2E Tests

```bash
# Debug mode (step through test)
npx playwright test --debug

# Headed mode (see browser)
npx playwright test --headed

# Slow down execution
npx playwright test --headed --slow-mo=1000

# View trace
npx playwright show-trace trace.zip
```

## Common Issues & Solutions

### Backend Tests

**Issue**: Tests fail with "RefreshDatabase" errors
**Solution**: Make sure database is migrated: `php artisan migrate:fresh`

**Issue**: "Demo user not found"
**Solution**: Run `php artisan demo:seed` manually

**Issue**: Tests are slow
**Solution**: Use in-memory SQLite (already configured in `phpunit.xml`)

### E2E Tests

**Issue**: "Browser not found"
**Solution**: Run `npx playwright install chromium`

**Issue**: "Connection refused" to localhost:8000
**Solution**: Ensure `php artisan serve` is running, or let Playwright start it (webServer config)

**Issue**: Tests timeout
**Solution**: Increase timeout in test: `test.setTimeout(60000)`

**Issue**: Selectors not found
**Solution**: Add waits: `await page.waitForSelector('selector')`

## Best Practices

### General
1. **Isolation**: Each test should be independent
2. **Clean state**: Use `seedDemo()` to reset between tests
3. **Descriptive names**: Test names should describe what they test
4. **Fast tests**: Avoid unnecessary delays or sleeps
5. **Deterministic**: Always produce the same result

### Backend
1. Use `actingAsDemo()` for authenticated tests
2. Test both happy and sad paths
3. Verify database changes with assertions
4. Mock external services (APIs, file systems when needed)
5. Keep tests focused (one thing per test)

### E2E
1. Use semantic selectors (`text=Login`, `role=button`)
2. Wait for network/load states
3. Test critical user journeys only
4. Take screenshots on failure (automatic)
5. Keep tests maintainable (use helper functions)

## Additional Resources

- [Pest Documentation](https://pestphp.com/)
- [Playwright Documentation](https://playwright.dev/)
- [Laravel Testing Guide](https://laravel.com/docs/testing)
- [Inertia Testing](https://inertiajs.com/testing)

## Extending the Test Suite

To add new tests:

1. **Backend**: Create test file in appropriate directory (`tests/Feature/` or `tests/Unit/`)
2. **E2E**: Create `.spec.ts` file in `tests/e2e/`
3. **Run tests** to verify they pass
4. **Update this documentation** if adding new test categories or patterns

## Test Maintenance

### When to Update Tests

- ✏️ When changing business logic
- 🎨 When refactoring (tests should still pass)
- 🆕 When adding new features
- 🐛 When fixing bugs (add regression test)
- 📋 When updating demo data structure

### Keeping Tests Fast

- Use database transactions (RefreshDatabase)
- Mock external services
- Minimize browser tests
- Run tests in parallel (Playwright)
- Keep test data minimal

## Summary

This testing suite provides comprehensive coverage of the FitAndFocused application:

- **Backend tests** validate business logic, database interactions, and API responses
- **E2E tests** verify critical user workflows work end-to-end
- **Demo user seeder** provides consistent, realistic test data
- **Fast execution** (<5s backend, <60s E2E)
- **Easy to extend** with clear patterns and helpers

Happy testing! 🧪✨

