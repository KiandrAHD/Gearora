import { test, expect } from '@playwright/test';
import { shot, expectNoHorizontalScroll } from './helpers.js';

const KEY_PAGES = ['/', '/katalog', '/panduan', '/rekomendasi-trip'];

test.describe('Responsif', () => {
    for (const url of KEY_PAGES) {
        test(`${url} tidak overflow horizontal`, async ({ page }) => {
            await page.goto(url, { waitUntil: 'networkidle' });
            await expectNoHorizontalScroll(page);

            const label = url.replaceAll('/', '-').replace(/^-$/, 'home') || 'home';
            const size = page.viewportSize();
            await shot(page, `responsive${label.replace('--', '-')}-${size ? size.width : 'na'}`);
        });
    }

    test('drawer mobile dapat dibuka dan ditutup', async ({ page, viewport }) => {
        test.skip(!viewport || viewport.width > 760, 'Hanya untuk viewport mobile');

        await page.goto('/', { waitUntil: 'networkidle' });
        const menu = page.locator('.mobile-menu');
        await menu.click();
        await expect(page.locator('.mobile-nav')).toBeVisible();
        await shot(page, 'mobile-drawer-open');
        await page.locator('.mobile-nav-close').click();
        await expect(page.locator('.mobile-nav')).toBeHidden();
    });
});
