import { test, expect } from '@playwright/test';
import { shot, expectNoHorizontalScroll, expectImagesLoaded } from './helpers.js';

test.describe('Halaman publik', () => {
    for (const [name, url] of [
        ['home', '/'],
        ['katalog', '/katalog'],
        ['panduan', '/panduan'],
        ['trip-planner', '/rekomendasi-trip'],
        ['masuk', '/masuk'],
        ['daftar', '/daftar'],
    ]) {
        test(`${name} merender tanpa error`, async ({ page }) => {
            const response = await page.goto(url, { waitUntil: 'networkidle' });
            expect(response.status()).toBeLessThan(400);
            await expect(page.locator('main')).toBeVisible();
            await expectImagesLoaded(page);
            await expectNoHorizontalScroll(page);
            await shot(page, `public-${name}`);
        });
    }

    test('navigasi utama menandai halaman aktif', async ({ page }) => {
        await page.goto('/panduan', { waitUntil: 'networkidle' });
        const current = page.locator('.primary-nav a[aria-current="page"]');
        await expect(current).toHaveCount(1);
        await expect(current).toHaveText(/Panduan/i);
    });

    test('halaman tidak ditemukan memberi 404 terkendali', async ({ page }) => {
        const response = await page.goto('/halaman-tidak-ada-xyz');
        expect(response.status()).toBe(404);
    });
});
