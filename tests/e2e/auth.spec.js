import { test, expect } from '@playwright/test';
import { shot } from './helpers.js';

const CUSTOMER = { email: 'pelanggan@gearora.test', password: 'Password123!' };
const ADMIN = { email: 'admin@gearora.test', password: 'Password123!' };

test.describe('Autentikasi & akun', () => {
    test('pelanggan dapat masuk dan membuka dashboard', async ({ page }) => {
        await page.goto('/masuk', { waitUntil: 'networkidle' });
        await page.locator('.auth-card input[name="email"]').fill(CUSTOMER.email);
        await page.locator('.auth-card input[name="password"]').fill(CUSTOMER.password);
        await page.locator('.auth-card').getByRole('button', { name: 'Masuk' }).click();
        await page.waitForLoadState('networkidle');

        expect(page.url()).toContain('/akun');
        await expect(page.locator('main')).toBeVisible();
        await shot(page, 'akun-dashboard');
    });

    test('kredensial salah menolak masuk', async ({ page }) => {
        await page.goto('/masuk', { waitUntil: 'networkidle' });
        await page.locator('.auth-card input[name="email"]').fill('salah@gearora.test');
        await page.locator('.auth-card input[name="password"]').fill('salahbanget');
        await page.locator('.auth-card').getByRole('button', { name: 'Masuk' }).click();
        await page.waitForLoadState('networkidle');
        expect(page.url()).toContain('/masuk');
    });

    test('staf dapat masuk konsol admin', async ({ page }) => {
        await page.goto('/admin/login', { waitUntil: 'networkidle' });
        await page.locator('input[name="email"]').fill(ADMIN.email);
        await page.locator('input[name="password"]').fill(ADMIN.password);
        await page.getByRole('button', { name: /Masuk/ }).click();
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/\/admin/);
        await shot(page, 'admin-dashboard');
    });
});
