import { test, expect } from '@playwright/test';

test.describe('Login Flow', () => {
  test('demo user can login', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'demo@fitandfocused.com');
    await page.fill('input[name="password"]', 'demo123');
    await page.click('button[type="submit"]');

    // Should redirect to home page (via / which redirects to /home)
    await expect(page).toHaveURL(/\/(home)?$/);
  });

  test('redirects to login if not authenticated', async ({ page }) => {
    await page.goto('/home');

    // Should redirect to login
    await expect(page).toHaveURL(/\/login/);
  });

  test('shows error for invalid credentials', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'wrong@example.com');
    await page.fill('input[name="password"]', 'wrongpassword');
    await page.click('button[type="submit"]');

    // Should stay on login page or show error
    await expect(page).toHaveURL(/\/login/);
  });
});

