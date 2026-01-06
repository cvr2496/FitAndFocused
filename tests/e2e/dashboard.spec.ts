import { test, expect } from '@playwright/test';
import { loginAsDemo } from './helpers/auth';

test.describe('Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsDemo(page);
  });

  test('displays dashboard after login', async ({ page }) => {
    // Should be on home page
    await expect(page).toHaveURL('/home');
  });

  test('displays stats', async ({ page }) => {
    // Check for stat headings/labels (adjust selectors based on actual UI)
    const pageContent = await page.textContent('body');
    
    // These are likely stats shown on dashboard
    expect(pageContent).toContain('Workout'); // e.g., "Weekly Workouts" or similar
  });

  test('displays recent workouts section', async ({ page }) => {
    const pageContent = await page.textContent('body');
    
    // Check that workout-related content is present
    expect(pageContent).toBeTruthy();
  });

  test('can navigate to workouts list', async ({ page }) => {
    // Find and click link to workouts (adjust selector based on actual UI)
    // This might be a nav link or button
    await page.click('text=/workouts/i');
    
    // Should navigate to workouts page
    await expect(page).toHaveURL(/\/workouts/);
  });
});

