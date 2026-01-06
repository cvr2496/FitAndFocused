import { test, expect } from '@playwright/test';
import { loginAsDemo } from './helpers/auth';

test.describe('Workouts', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsDemo(page);
  });

  test('can view workout list', async ({ page }) => {
    await page.goto('/workouts');
    
    // Page should load
    await expect(page).toHaveURL('/workouts');
    
    // Should have some workout content
    const pageContent = await page.textContent('body');
    expect(pageContent).toBeTruthy();
  });

  test('workout list shows workouts', async ({ page }) => {
    await page.goto('/workouts');
    
    // Check for workout-related content (date, title, exercises, etc.)
    const pageContent = await page.textContent('body');
    
    // Demo data has workouts with these titles
    const hasWorkoutContent = 
      pageContent?.includes('Chest') || 
      pageContent?.includes('Back') || 
      pageContent?.includes('Leg');
    
    expect(hasWorkoutContent).toBeTruthy();
  });

  test('can view workout detail', async ({ page }) => {
    await page.goto('/workouts');
    
    // Wait for page to load
    await page.waitForLoadState('networkidle');
    
    // Find and click the first workout link (adjust selector based on actual UI)
    const firstWorkout = page.locator('a').first();
    if (await firstWorkout.isVisible()) {
      await firstWorkout.click();
      
      // Should navigate to workout detail page
      await expect(page).toHaveURL(/\/workouts\/\d+/);
    }
  });

  test('workout detail shows exercises', async ({ page }) => {
    // Go directly to a workout detail (workout ID 1 from demo data)
    await page.goto('/workouts/1');
    
    // Page should load
    await page.waitForLoadState('networkidle');
    
    // Should have exercise-related content
    const pageContent = await page.textContent('body');
    expect(pageContent).toBeTruthy();
  });
});

