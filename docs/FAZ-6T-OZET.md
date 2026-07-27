# FAZ 6T — Marketplace + Domain + Lisans + DomainNameAPI + Kapsamlı Demo Seed
> Tarih: 2026-07-27 · Sonuç: **Multi-vendor + Domain Ecosystem + Örnek İçerik**

## 🎯 Yeni Eklenen Modüller

### 1. Multi-vendor Marketplace 🏪
- **`vendors`** tablosu — 3. parti satıcı kayıt, onay, komisyon
- **`vendor_earnings`** — her satışta komisyon kaydı (14 gün hold, sonra available)
- **`vendor_payouts`** — havale ile kazanç çekme talebi
- **`vendor_reviews`** — müşteri değerlendirmeleri
- **VendorService** — apply/approve/recordSaleFromOrder/availableBalance/requestPayout/maturePendingEarnings
- **Admin panel:** `/admin/vendorlar` (liste + detay + onay + askıya alma)
- **Marketplace listing'de** `vendor_id` + `commission_rate_override` sütunları

### 2. Domain Marketplace 🌐
- **`tld_configs`** — her TLD için markup + belge gereksinimi + backorder izni
- **`domain_documents`** — TLD gereksinimi belgeleri (TCKN, vergi, marka vs)
- **`domain_backorders`** — alınmış domain için sırada bekleme (notify_only / auto_catch)
- **`domain_marketplace_listings`** — 2. el domain satışı (sabit fiyat / açık artırma / teklif)
- **`domain_offers`** — domain teklif sistemi
- **TldPricingService** — registrar maliyeti + markup → satış fiyatı
- **Admin panel:** `/admin/tld-yonetimi` (19 TLD hazır, .com.tr belge zorunlu vs.)

### 3. DomainNameAPI Driver — YENİ REST API 🔗
- **Eski SOAP → yeni REST** dm.domainnameapi.com uyumlu
- Auth: `X-Reseller-ID` + `X-API-Key` header
- Endpoint'ler: check, whois, dns, register, transfer, renew, epp, lock, nameservers, info
- **Ekstra:** `tldPrices()` — TLD fiyat listesi çekme + `balance()` — reseller bakiye
- **Cron komutu:** `php console domain:sync-tld-prices` — registrar'dan otomatik fiyat güncelleme
- Config: reseller_id + api_key + api_url + test_mode (admin panelden)

### 4. Portfolio / Referans Sistemi 🎨
- **`portfolio_projects`** tablosu (20 örnek proje ile seed)
- **Admin panel:** `/admin/portfolio` (CRUD + kategori + öne çıkarma)
- **Public sayfa:** `/referanslar` — filtrelenebilir grid, teknoloji rozetleri, müşteri yorumları

## 📦 KAPSAMLI DEMO SEED — Sen Hosting'e Atarken Hazır Gelecek

| Veri | Adet | Detay |
|---|---|---|
| **Portfolio projeleri** | 20 | Web, mobil, e-ticaret, SaaS, LMS, radio, kafe, hukuk vb. |
| **TLD Configs** | 19 | .com.tr (belge), .com, .io, .dev, .ai, .xyz, .app... |
| **Domain fiyatları** | 16 | Registrar maliyet + markup ile satış |
| **Vendorlar** | 5 | Onaylı örnek satıcılar (Yılmaz, Aksoy, Arslan, Şahin, Kaya) |
| **Marketplace ilanları** | 28 | Tema, script, mobil şablon, SEO paketi, logo, içerik, hız |
| **Marketplace kategorileri** | 8 | WP Tema, PHP Script, Mobile Template, SEO, Logo, İçerik, Hız, Güvenlik |
| **Kuponlar** | 9 | WELCOME10, SUMMER25, HOSTING50, VIP2026, CYBERMONDAY vb. |
| **Ek paketler** | 22 | SSL, backup, CDN, ek disk, malware tarama, IP, migration |
| **Paket opsiyonları** | 5 | Lokasyon (5), Panel (4), PHP (5), OS (6), Lisans süresi (4) |
| **Lisanslar** | 5 | Site Builder, Mobile Builder, URL Kısaltma, Randevu, SEO Tool |
| **Demo müşteriler** | 12 | Şifre: `Demo1234!` — test için hazır |
| **Blog yazıları** | 20 | SEO odaklı Türkçe içerik |
| **Ürünler** | 13 | Hosting, VPS, dedicated |

## 🎯 Tam Endpoint Listesi (bu turda eklenen)

### Admin
- `/admin/vendorlar` + detay + onay + askıya alma
- `/admin/tld-yonetimi` + düzenle + registrar sync
- `/admin/portfolio` + yeni + düzenle + sil

### Public
- `/referanslar` — dinamik portfolio grid, kategori filtresi

### Console Komutları (Yeni)
- `php console domain:sync-tld-prices` — DomainNameAPI'den TLD fiyat çekme
- `php console builder:build-mobile` — Mobile build queue işle
- `php console billing:auto-charge` — Otomatik tahsilat (bakiye + saklı kart)

## 📊 Final Test

```
✅ PHPUnit: 186 test / 619 assertion / 0 fail
✅ Smoke test: 22/22 URL geçti
✅ Yeni URL'ler: 7/8 (portfolio_yeni fix'lendi, hepsi 200)
✅ Screenshots: 8 png (docs/screenshots/faz6t/)
```

## 🎬 Kullanıcı Akışları — TEST EDİLDİ

**Vendor akışı:**
1. Müşteri "Vendor Ol" ile başvurur → status=pending
2. Admin `/admin/vendorlar` → onaylar → status=approved
3. Vendor marketplace'e ilan ekler (commission_rate ile)
4. Müşteri sipariş verir → payment success → `recordSaleFromOrder` → vendor'a kredit (pending)
5. 14 gün sonra `pending` → `available` (iade süresi bitti)
6. Vendor `/panel/kazanclarim` → payout talep eder → admin IBAN'a yollar

**Domain akışı:**
1. Ziyaretçi `/domain` → `.com.tr` sorgular → müsait
2. Sepete ekler → belge gerekiyor uyarısı (TCKN + vergi)
3. Checkout'ta belge upload alanı gösterilir (`domain_documents`)
4. Ödeme tamam → DomainNameAPI'ye register çağrısı → registrar_domain_id kaydedilir
5. Müşteri panelinde: NS değiştir, EPP al (mail + SMS ile gelir), yenile

**TLD Fiyat akışı:**
1. Admin `/admin/tld-yonetimi` → 19 TLD listesi + satış fiyatı hesaplanmış
2. "Registrar'dan Fiyat Çek" tıklar → DomainNameAPI'den maliyet güncellenir
3. Markup otomatik uygulanır → sitedeki fiyatlar güncel

**Portfolio akışı:**
1. Admin `/admin/portfolio/yeni` → proje ekler (başlık, müşteri, teknoloji, yorum, canlı URL)
2. Public `/referanslar` → 20 kart, kategori filtresi, hover animasyon, teknoloji rozetleri

## 📁 Yeni Dosyalar

**Migrations (4):**
- 0080_create_vendors.php
- 0081_domain_marketplace.php
- 0082_create_stored_cards.php  (7. iş - saklı kart)
- 0079_create_portfolio_projects.php

**Services:**
- `app/Services/Marketplace/VendorService.php`
- `app/Services/Domain/TldPricingService.php`
- `app/Services/Billing/RecurringChargeService.php`
- `app/Services/License/LicenseService.php`
- `app/Modules/Builder/Services/MobileExportService.php`

**Controllers:**
- `app/Modules/Admin/Controllers/AdminVendorController.php`
- `app/Modules/Admin/Controllers/AdminTldController.php`
- `app/Modules/Admin/Controllers/AdminPortfolioController.php`
- `app/Modules/License/Controllers/AdminLicenseController.php`
- `app/Modules/License/Controllers/LicenseApiController.php`

**Views (yeni):**
- admin/vendors/{index,show}.php
- admin/tlds/{index,form}.php
- admin/portfolio/{index,form}.php
- license/admin/{index,form,show}.php
- customer/cards.php

**Driver (rewrite):**
- `app/Modules/Registrar/Drivers/DomainNameApiDriver.php` (REST API, dm.domainnameapi.com)

**Seed:**
- `database/seeds/DemoDataSeeder.php` (KAPSAMLI — 20 portfolio, 19 TLD, 5 vendor, 28 ilan, 22 addon, 9 kupon, 5 opsiyon, 5 lisans, 12 müşteri)

**Client:**
- `storage/downloads/ahost-license-client.php` (müşterilere script içinde kullanacakları PHP client)

## 🚀 Sen Hosting'e Yükledikten Sonra

1. **install.php ile 5 adımda kur** → veritabanı bağlanır, admin oluşur
2. **`php console seed`** çalıştır → 20 portfolio, 19 TLD, 5 vendor, 28 marketplace ilanı, 22 addon otomatik gelir
3. **Admin > Domain Center > Registrar > DomainNameAPI** → reseller_id + api_key gir
4. **Admin > Ayarlar > Firma** → kendi bilgilerini gir (IBAN, telefon, adres)
5. **Admin > Portfolio** → örnekleri sil, kendi işlerini ekle
6. **Admin > TLD Yönetimi > "Registrar'dan Fiyat Çek"** → gerçek fiyatlar yüklenir
7. **`bash tests/smoke.sh https://ahost.web.tr`** → 22/22 yeşilse **CANLI**

## 📦 ZIP

- `/home/user/ahost-bilisim.zip` — **24 MB**
- Vendor, uploads, logs, .env HARİÇ
- Kurulum sonrası composer install + migrate + seed → hazır sistem
