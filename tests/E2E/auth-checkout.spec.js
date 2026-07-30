// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Customer Auth & Checkout Flow', () => {
    test('müşteri giriş → sepete ekle → kupon → ödeme', async ({ page }) => {
        // Login
        await page.goto('/giris');
        await page.fill('input[name="email"]', 'test@ahost.web.tr');
        await page.fill('input[name="password"]', 'Test1234!');
        await page.click('button[type="submit"]');
        await expect(page).toHaveURL(/\/panel/);

        // Ürün sayfası
        await page.goto('/urun/hosting-business');
        await expect(page.locator('h1')).toContainText('Hosting Business');
        await page.click('button[type="submit"]:has-text("Sepete Ekle")');

        // Sepette
        await expect(page).toHaveURL(/\/sepet/);
        await expect(page.locator('.aho-cart-item__name')).toContainText('Hosting Business');

        // Kupon
        await page.fill('input[name="coupon_code"]', 'WELCOME10');
        await page.click('button:has-text("Uygula")');
        await expect(page.locator('.aho-cart-summary__row').filter({ hasText: 'İndirim' })).toBeVisible();

        // Ödemeye geç
        await page.click('a:has-text("Ödemeye Geç")');
        await expect(page).toHaveURL(/\/odeme/);

        // Havale seç ve tamamla
        await page.click('.aho-payment-method:has(input[value="bank_transfer"])');
        await page.click('button:has-text("Siparişi Onayla")');
        await expect(page).toHaveURL(/\/odeme\/basarili/);
        await expect(page.locator('h2')).toContainText('Teşekkürler');
    });

    test('admin login çalışır', async ({ page }) => {
        await page.goto('/admin/giris');
        await page.fill('input[name="email"]', 'admin@ahost.web.tr');
        await page.fill('input[name="password"]', 'AhostOne2026!');
        await page.click('button[type="submit"]');
        await expect(page).toHaveURL(/\/admin/);
        await expect(page.locator('h1')).toContainText(/Kontrol|Dashboard/);
    });
});
