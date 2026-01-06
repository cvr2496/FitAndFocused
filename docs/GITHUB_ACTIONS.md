# GitHub Actions Setup Guide

## Quick Start

Your tests are now configured to run automatically on GitHub! 🎉

### What Happens Automatically

Every time you push code or create a pull request to `main` or `develop` branches:

1. **Backend tests** run (56 Pest/PHPUnit tests)
2. **E2E tests** run (10 Playwright browser tests)
3. **Results** appear in the GitHub Actions tab
4. **Reports** are saved as downloadable artifacts

## Viewing Test Results

### In Pull Requests
1. Open your PR on GitHub
2. Scroll to the bottom - you'll see check status
3. Click "Details" to see the full workflow run

### In the Actions Tab
1. Go to your repository on GitHub
2. Click the "Actions" tab
3. Select a workflow run to see details
4. Download artifacts (Playwright reports, screenshots)

## Adding Status Badges

Add these badges to your `README.md` to show test status:

### Separate Jobs Workflow
```markdown
![Tests](https://github.com/YOUR_USERNAME/YOUR_REPO/actions/workflows/tests.yml/badge.svg)
```

### Combined Workflow
```markdown
![Tests](https://github.com/YOUR_USERNAME/YOUR_REPO/actions/workflows/tests-combined.yml/badge.svg)
```

Replace `YOUR_USERNAME` and `YOUR_REPO` with your actual GitHub username and repository name.

## First Time Setup

### 1. Push to GitHub

```bash
git push origin main
```

### 2. Enable GitHub Actions
- GitHub Actions should be enabled by default
- If not, go to Settings → Actions → Enable workflows

### 3. Watch Your First Run
- Go to the Actions tab
- You should see a workflow running
- Wait 2-3 minutes for completion

## Workflow Details

### Backend Tests Job
- **Duration**: ~30-45 seconds
- **Environment**: Ubuntu, PHP 8.2, SQLite
- **Tests**: 56 tests (models, controllers, APIs, services, seeders)

### E2E Tests Job
- **Duration**: ~2-3 minutes
- **Environment**: Ubuntu, Node 20, Chromium
- **Tests**: 10 browser automation tests
- **Artifacts**: Playwright HTML reports, failure screenshots

## Troubleshooting

### Tests Fail on GitHub but Pass Locally

**Check environment differences:**
```bash
# Ensure migrations work
php artisan migrate:fresh

# Ensure demo seeder works
php artisan demo:seed --fresh

# Rebuild assets
npm run build
```

### Playwright Tests Timeout

Add this to `playwright.config.ts` if needed:
```typescript
timeout: 30000, // 30 seconds per test
```

### Runner Out of Disk Space

This is rare, but if it happens:
- Remove `node_modules` caching
- Use `npm ci` instead of `npm install`
- Clean up old artifacts

## Cost Considerations

### Public Repositories
- ✅ **FREE** unlimited minutes
- ✅ Use `tests.yml` (parallel jobs)

### Private Repositories
- ⚠️ Limited free minutes (2,000/month)
- Consider using `tests-combined.yml` (saves ~50% minutes)
- Or disable E2E tests temporarily

### Optimizing for Private Repos

1. **Disable one workflow:**
   ```bash
   git mv .github/workflows/tests.yml .github/workflows/tests.yml.disabled
   ```

2. **Use combined workflow:**
   Already included as `tests-combined.yml`

3. **Run only on main branch:**
   Edit workflow to remove `develop` branch:
   ```yaml
   on:
     push:
       branches: [ main ]
   ```

## Advanced Configuration

### Run Tests Only on Specific Paths

Add this to workflow triggers:
```yaml
on:
  push:
    paths:
      - 'app/**'
      - 'tests/**'
      - 'resources/**'
```

### Skip Tests with Commit Message

Add to commit message:
```bash
git commit -m "docs: update README [skip ci]"
```

### Manual Workflow Trigger

Add to workflow:
```yaml
on:
  workflow_dispatch:  # Adds "Run workflow" button in UI
  push:
    branches: [ main ]
```

## Maintenance

### Updating PHP Version

In both workflow files, change:
```yaml
php-version: '8.2'  # Change to '8.3' when ready
```

### Updating Node Version

In both workflow files, change:
```yaml
node-version: '20'  # Change to '22' when ready
```

### Updating Browser

In E2E tests, change:
```bash
npx playwright install chromium  # or firefox, webkit
```

And in `playwright.config.ts`:
```typescript
projects: [
  { name: 'chromium' },
  { name: 'firefox' },  // Add more browsers
]
```

## Getting Help

### Check Workflow Logs
1. Go to Actions tab
2. Click failed workflow
3. Click failed job
4. Expand failed step

### Common Log Locations
- Backend test output: "Run backend tests" step
- E2E test output: "Run E2E tests" step
- Playwright report: Download artifact after run

### Local Reproduction

Run the same commands locally:
```bash
# Exact steps from workflow
composer install
touch database/database.sqlite
php artisan migrate --force
php artisan demo:seed
php artisan storage:link
npm ci
npm run build
php artisan test
npx playwright install chromium --with-deps
npm run test:e2e
```

## Success Criteria

✅ All tests pass on GitHub  
✅ Workflow runs in <5 minutes  
✅ Artifacts are uploaded on failure  
✅ Status checks block broken PRs  

You're all set! 🚀

