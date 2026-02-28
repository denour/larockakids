import { test, expect } from '@playwright/test';

test.describe('QR Scanner - Check In', () => {
    test('check-in page loads correctly', async ({ page }) => {
        await page.goto('/scanner/check-in');

        await expect(page).toHaveTitle(/ENTRADA|Scanner|Piedritas/i);
        await expect(page.locator('text=ENTRADA').or(page.locator('text=Entrada'))).toBeVisible();
    });

    test('check-in page has QR scanner container', async ({ page }) => {
        await page.goto('/scanner/check-in');

        // Should have a container for the QR scanner (html5-qrcode)
        await expect(
            page.locator('#reader')
                .or(page.locator('#qr-reader'))
                .or(page.locator('[id*="qr"]'))
        ).toBeVisible({ timeout: 5000 });
    });

    test('check-in page has scanner UI elements', async ({ page }) => {
        await page.goto('/scanner/check-in');

        // The page should have visible content indicating it's the check-in scanner
        const bodyText = await page.textContent('body');
        expect(bodyText).toContain('ENTRADA');
    });
});

test.describe('QR Scanner - Check Out', () => {
    test('check-out page loads correctly', async ({ page }) => {
        await page.goto('/scanner/check-out');

        await expect(page.locator('text=SALIDA').or(page.locator('text=Salida'))).toBeVisible();
    });

    test('check-out page has QR scanner container', async ({ page }) => {
        await page.goto('/scanner/check-out');

        await expect(
            page.locator('#reader')
                .or(page.locator('#qr-reader'))
                .or(page.locator('[id*="qr"]'))
        ).toBeVisible({ timeout: 5000 });
    });

    test('check-out page has scanner UI elements', async ({ page }) => {
        await page.goto('/scanner/check-out');

        const bodyText = await page.textContent('body');
        expect(bodyText).toContain('SALIDA');
    });
});
