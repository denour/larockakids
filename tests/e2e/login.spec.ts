import { test, expect } from '@playwright/test';

test.describe('Login Flow', () => {
    test('login page loads correctly', async ({ page }) => {
        await page.goto('/admin/login');

        await expect(page).toHaveTitle(/Piedritas|La Rocka|Login/i);
        await expect(page.locator('input[name="email"]')).toBeVisible();
        await expect(page.locator('input[name="password"]')).toBeVisible();
    });

    test('shows validation errors for empty credentials', async ({ page }) => {
        await page.goto('/admin/login');

        await page.click('button[type="submit"]');

        await expect(page.locator('text=required').or(page.locator('.fi-fo-field-wrp-error-message'))).toBeVisible();
    });

    test('shows error for invalid credentials', async ({ page }) => {
        await page.goto('/admin/login');

        await page.fill('input[name="email"]', 'invalid@example.com');
        await page.fill('input[name="password"]', 'wrongpassword');
        await page.click('button[type="submit"]');

        await expect(
            page.locator('.fi-fo-field-wrp-error-message')
                .or(page.locator('[role="alert"]'))
                .or(page.locator('text=credentials'))
        ).toBeVisible({ timeout: 5000 });
    });

    test('unauthenticated user is redirected to login', async ({ page }) => {
        await page.goto('/admin');

        await expect(page).toHaveURL(/\/admin\/login/);
    });
});
