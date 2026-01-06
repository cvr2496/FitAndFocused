import { Page } from '@playwright/test';

export async function loginAsDemo(page: Page) {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'demo@fitandfocused.com');
  await page.fill('input[name="password"]', 'demo123');
  await page.click('button[type="submit"]');
  await page.waitForURL('/home', { timeout: 10000 });
}

