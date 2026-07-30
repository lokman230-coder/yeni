// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Public Pages', () => {
    test('anasayfa yüklenir ve brand görünür', async ({ page }) => {
        await page.goto('/');
        await expect(page).toHaveTitle(/Ahost Bilişim/);
        await expect(page.locator('.aho-hdr__brand-text')).toContainText('Ahost');
        await expect(page.locator('.aho-hdr__brand-text b')).toContainText('Bilişim');
    });

    test('hosting sayfası ve fiyat kartları görünür', async ({ page }) => {
        await page.goto('/hosting');
        await expect(page.locator('h1')).toContainText('Web Hosting');
        await expect(page.locator('.aho-plan-card')).toHaveCount(3);
    });

    test('domain sorgu formu çalışır', async ({ page }) => {
        await page.goto('/domain');
        await page.fill('input[name="q"]', 'example.com');
        await page.click('button[type="submit"]');
        await expect(page.locator('.aho-tool-domain-main__name')).toContainText('example.com');
    });

    test('16 site aracı listelenir', async ({ page }) => {
        await page.goto('/site-araclari');
        const tools = page.locator('.aho-feature-grid > a');
        await expect(tools).toHaveCount(16);
    });

    test('marketplace sayfası + ilan kartları', async ({ page }) => {
        await page.goto('/marketplace');
        await expect(page.locator('h1')).toContainText('Marketplace');
        // En az bir ilan (seeder'dan)
        await expect(page.locator('.aho-mp-card').first()).toBeVisible();
    });

    test('site builder demo — 11 sektör görünür', async ({ page }) => {
        await page.goto('/site-builder');
        await expect(page.locator('.aho-bldr-demo-hero__title')).toBeVisible();
        const sectors = page.locator('.aho-bldr-sector');
        expect(await sectors.count()).toBeGreaterThanOrEqual(11);
    });

    test('AI widget floating butonu görünür', async ({ page }) => {
        await page.goto('/');
        await expect(page.locator('[data-aho-ai-widget]')).toBeVisible();
        await page.click('[data-aho-ai-toggle]');
        await expect(page.locator('.aho-ai-panel')).toBeVisible();
    });

    test('tema switcher açılır ve 5 tema listelenir', async ({ page }) => {
        await page.goto('/');
        await page.click('.aho-theme-switch__toggle');
        await expect(page.locator('.aho-theme-swatch')).toHaveCount(5);
    });

    test('çerez banner görünür ve kabul edilince kaybolur', async ({ page }) => {
        await page.goto('/');
        const banner = page.locator('#ahoCookieBanner');
        await expect(banner).toBeVisible();
        await page.click('[data-aho-cookie="accept"]');
        await expect(banner).toBeHidden();
    });

    test('404 sayfası düzgün render olur', async ({ page }) => {
        const resp = await page.goto('/bulunamayan-sayfa-xyz');
        expect(resp.status()).toBe(404);
        await expect(page.locator('h1')).toContainText(/404|Bulunamadı/);
    });
});
