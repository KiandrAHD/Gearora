import { test, expect } from '@playwright/test';
import { shot, expectNoHorizontalScroll } from './helpers.js';

function isoDate(daysFromNow) {
    const d = new Date();
    d.setDate(d.getDate() + daysFromNow);
    return d.toISOString().slice(0, 10);
}

test.describe('Trip Planner Gunung Prau', () => {
    test('wizard lengkap sampai pratinjau paket', async ({ page }) => {
        await page.goto('/rekomendasi-trip', { waitUntil: 'networkidle' });

        await expect(page.getByRole('heading', { level: 1 })).toContainText('Ke mana langkahmu');
        await expect(page.locator('.trip-destination-mark')).toContainText('GUNUNG PRAU');
        await expectNoHorizontalScroll(page);
        await shot(page, 'trip-step-1');

        // Langkah 1: jadwal
        await page.getByLabel('Basecamp / lokasi opsional').fill('Patak Banteng');
        await page.getByLabel('Tanggal keberangkatan').fill(isoDate(5));
        await page.getByLabel('Waktu berkumpul opsional').fill('04:30');
        await page.getByRole('button', { name: /Lanjutkan/ }).click();

        // Langkah 2: peserta
        await expect(page.getByText('Siapa yang ikut bertualang?')).toBeVisible();
        await page.getByLabel('Jumlah peserta').fill('3');
        await shot(page, 'trip-step-2');
        await page.getByRole('button', { name: /Lanjutkan/ }).click();

        // Langkah 3: kebutuhan & mode
        await expect(page.getByText('Apa yang dibutuhkan selama perjalanan?')).toBeVisible();
        await page.getByText('Perlindungan hujan').click();
        await page.locator('.trip-mode-option', { hasText: 'Sewa' }).filter({ hasText: 'Tanggal dicek' }).click();
        await shot(page, 'trip-step-3');
        await page.getByRole('button', { name: /Lanjutkan/ }).click();

        // Langkah 4: ringkasan
        await expect(page.getByText('Periksa kembali rencana perjalananmu.')).toBeVisible();
        await expect(page.locator('.trip-summary-review')).toContainText('Patak Banteng');
        await shot(page, 'trip-step-4');

        await page.getByRole('button', { name: /Lihat rekomendasi/ }).click();
        await page.waitForLoadState('networkidle');

        // Langkah 5: hasil
        await expect(page.getByText('Pilih perlengkapan petualanganmu.')).toBeVisible();
        await shot(page, 'trip-step-5-results');

        const results = page.locator('.trip-result');
        expect(await results.count()).toBeGreaterThan(0);

        await results.first().locator('input[type="checkbox"]').check();
        await page.getByRole('button', { name: /Pratinjau paket/ }).click();
        await page.waitForLoadState('networkidle');

        await expect(page.getByRole('heading', { level: 1 })).toContainText('Periksa paket trip');
        await expect(page.getByText('Ringkasan biaya')).toBeVisible();
        await expectNoHorizontalScroll(page);
        await shot(page, 'trip-preview-paket');
    });

    test('validasi menahan langkah tanpa tanggal keberangkatan', async ({ page }) => {
        await page.goto('/rekomendasi-trip', { waitUntil: 'networkidle' });
        await page.getByRole('button', { name: /Lanjutkan/ }).click();
        // Tetap di langkah 1 karena tanggal wajib kosong.
        await expect(page.getByText('Ceritakan rencana perjalananmu.')).toBeVisible();
    });
});
