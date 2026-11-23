import { test, expect } from '@playwright/test';
import { shot, expectNoHorizontalScroll } from './helpers.js';

test.describe('Panduan (guide library)', () => {
    test('daftar panduan menampilkan kartu dan filter bekerja', async ({ page }) => {
        await page.goto('/panduan', { waitUntil: 'networkidle' });
        await expect(page.getByRole('heading', { name: /Bekal pengetahuan/i })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Semua panduan' })).toBeVisible();
        await expect(page.locator('.guide-card').first()).toBeVisible();

        await page.getByLabel('Cari panduan').fill('Tenda');
        await page.getByRole('button', { name: 'Filter' }).click();
        await page.waitForLoadState('networkidle');
        expect(page.url()).toContain('q=Tenda');
        await shot(page, 'panduan-filter-tenda');
    });

    test('membuka detail panduan dan menampilkan konten aman', async ({ page }) => {
        await page.goto('/panduan', { waitUntil: 'networkidle' });
        const firstCard = page.locator('.guide-card h3 a').first();
        const title = (await firstCard.textContent()).trim();
        await firstCard.click();
        await page.waitForLoadState('networkidle');

        await expect(page.getByRole('heading', { level: 1 })).toContainText(title);
        await expect(page.locator('.guide-content')).toBeVisible();
        await expect(page.locator('.guide-safety-note')).toContainText('bukan pengganti');

        // Tidak ada HTML mentah yang lolos: tidak boleh ada script yang berjalan.
        const injected = await page.evaluate(() => document.querySelectorAll('main script:not([data-csrf])').length);
        expect(injected).toBe(0);

        await expectNoHorizontalScroll(page);
        await shot(page, 'panduan-detail');
    });

    test('kategori pada filter mengubah hasil', async ({ page }) => {
        await page.goto('/panduan', { waitUntil: 'networkidle' });
        const options = page.locator('select[name="kategori"] option');
        const count = await options.count();
        if (count > 1) {
            const value = await options.nth(1).getAttribute('value');
            await page.selectOption('select[name="kategori"]', value);
            await page.getByRole('button', { name: 'Filter' }).click();
            await page.waitForLoadState('networkidle');
            expect(page.url()).toContain(`kategori=${value}`);
        }
    });
});
