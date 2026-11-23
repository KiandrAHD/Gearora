import { expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

const shotDir = path.resolve('storage/qa/screenshots');

export async function shot(page, name) {
    fs.mkdirSync(shotDir, { recursive: true });
    await page.screenshot({ path: path.join(shotDir, `${name}.png`), fullPage: true });
}

/** Tidak boleh ada scroll horizontal pada viewport aktif. */
export async function expectNoHorizontalScroll(page) {
    const overflow = await page.evaluate(() => {
        const doc = document.documentElement;
        return doc.scrollWidth - doc.clientWidth;
    });
    expect(overflow, 'horizontal overflow (px)').toBeLessThanOrEqual(1);
}

/** Semua gambar tampil (naturalWidth > 0) kecuali yang ditandai dekoratif. */
export async function expectImagesLoaded(page) {
    const broken = await page.evaluate(() =>
        Array.from(document.images)
            .filter((img) => img.getAttribute('loading') !== 'lazy' || img.complete)
            .filter((img) => !img.complete || img.naturalWidth === 0)
            .map((img) => img.currentSrc || img.src),
    );
    expect(broken, 'gambar gagal dimuat').toEqual([]);
}
