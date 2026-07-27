# E2E Testler — Playwright

Playwright ile end-to-end tarayıcı testleri.

## Kurulum

```bash
npm install
npx playwright install chromium
```

## Çalıştırma

```bash
# Tüm testler
npm run test:e2e

# UI mode (interaktif)
npm run test:e2e:ui

# Sadece bir dosya
npx playwright test tests/E2E/public.spec.js

# HTML raporu görüntüle
npm run test:e2e:report
```

## Test Senaryoları

### `public.spec.js` — 10 test
- Anasayfa yüklenir + brand görünür
- Hosting fiyat kartları
- Domain sorgu formu
- 16 site aracı listelenir
- Marketplace ilan kartları
- Site Builder demo (11 sektör)
- AI widget aç/kapa
- Tema switcher (5 tema)
- Çerez banner
- 404 sayfası

### `auth-checkout.spec.js` — 2 test
- Müşteri: giriş → sepete ekle → kupon → havale ile ödeme
- Admin: giriş → dashboard
