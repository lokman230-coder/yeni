# Ahost Bilişim

**Modern, premium, Türkçe odaklı, modüler hosting / domain / dijital hizmet yönetim platformu.**

🌐 https://ahost.web.tr

WHMCS + WISECP + Blesta'nın toplam kapsamını hedefler; sıfırdan, temiz mimariyle,
tam modüler, yamasız ve genişletilebilir yazılır. Site Builder = **Elementor + Visual Composer + AI**.

---

## 🎯 Durum: v1.0.0 — Üretime Hazır

**137 PHPUnit test, 506 assertion — %100 geçer** · **64 migration** · **30+ modül** · **10 admin CRUD ekranı**

- ✅ **Faz 0** — Planlama, mimari, ERD, dokümantasyon (11 doküman)
- ✅ **Faz 1** — Çekirdek iskelet (MVC, PDO, Migration, Auth, RBAC)
- ✅ **Faz 2** — Public site + Admin/Müşteri paneli + Çerez + i18n + **5 Tema**
- ✅ **Faz 3** — Ürün + Sepet + Kupon + Vergi + PayTR + Fatura
- ✅ **Faz 4** — Domain + Hosting + 16 Site Aracı + Health/QA + Composer + Encrypter
- ✅ **Faz 5** — Site Builder + Mobile Builder + AI 3-bağlam + Marketplace + Destek + BTK
- ✅ **Faz 6** — Mail queue + SMTP + 9 template + Cron scheduler + Bildirimler + Docker + Playwright + e-Fatura iskelet
- ✅ **Faz 6b** — iyzico + Papara + Canlı TCMB Kur + AI Site Generator + Referral Programı + Uyumsoft SOAP
- ✅ **Faz 6c** — Müşteri kayıt akışı + Panel (5 sayfa) + Admin Domain CRUD + Kurulum Sihirbazı
- ✅ **Faz 6d** — Otomatik Provisioning + Şifre Reset + 2FA (google2fa) + Server admin CRUD + Hizmet detay
- ✅ **Faz 6e** — E-posta doğrulama + Admin 2FA setup + Kupon CRUD + Ticket iç not + Usage cron
- ✅ **Faz 6f** — Referral Payout + SMTP test + Usage grafik + Uptime Probe
- ✅ **Faz 6g** — Şifre değiştir + Fatura PDF (Dompdf) + Domain WHOIS refresh
- ✅ **Faz 6h** — Canlı Admin Dashboard + Global search
- ✅ **Faz 6i** — Marketplace onay + Admin Activity Log

---

## 📊 İstatistikler (Faz 6 sonrası)

- **300+ PHP** dosyası
- **86 CSS** dosyası (5 tema × site/admin ayrımı + 19 aile CSS)
- **8 JS** modülü
- **53 migration** — MySQL **52 tablo**
- **13 seeder** (varsayılan yönetici, roller, ürünler, kupon, mail template, kategoriler, vs.)
- **30 modül** (aile disiplini ile organize)
- **5 tema** (Default / Midnight / Emerald / Sunset / Royal)
- **37 PHPUnit test** — **260 assertion** — hepsi ✓
- **12 Playwright E2E** senaryosu — auth + checkout + AI + tema + 404
- **69 HTTP smoke test** — hepsi ✓
- **8 cron görevi** kayıtlı ve çalışır durumda
- **9 mail template** (hoşgeldin, sipariş, fatura, ödeme, hizmet, domain, ticket, şifre sıfırlama, MFA)
- **0 PHP warning/error** — Log tertemiz

---

## 🚀 Kurulum

### Docker ile (tavsiye edilen)

```bash
docker compose up -d --build
```
Tarayıcı → **http://localhost:8080**

### Manuel

```bash
composer install
cp .env.example .env
php console key:generate

# .env'de DB bilgisi
mysql -e "CREATE DATABASE ahost_one CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

php console migrate:fresh --seed
php console cron:install
composer serve
```

### Test hesapları

- **Admin:** `admin@ahost.web.tr` / `AhostOne2026!`
- **Müşteri:** `test@ahost.web.tr` / `Test1234!`
- **Kupon:** `WELCOME10` (%10 indirim)

---

## 🖥️ CLI

```bash
php console migrate                # bekleyen migration'lar
php console migrate:fresh --seed   # tüm tabloları baştan kur + seed
php console rollback --steps=1     # son batch geri al
php console seed                   # sadece seed
php console routes                 # tüm route'ları listele
php console serve                  # dev sunucusu
php console key:generate           # APP_KEY üret
php console cron:install           # 8 varsayılan cron zamanla
php console cron:run               # cron'ları elle tetikle
php console mail:queue             # mail kuyruğunu işle
php console mail:test <email>      # test mail gönder
```

---

## 🧩 30 Modül (aile disipliniyle)

| Aile | Modüller | Paylaşılan CSS |
|---|---|---|
| Core / Layout | Header, Footer, Home, Pages | site/header.css, site/footer.css, site/homepage.css, site/page.css |
| Commerce | Product, Cart, Checkout, Payment, Invoice | site/product.css, site/cart.css, site/checkout.css |
| Content | Blog, Announcements, Knowledge, References, Contact | site/page.css, site/contact.css |
| Domain & Hosting | Registrar, Domain, Hosting | site/tools.css |
| Tools Family | SiteTools (16 araç) | site/tools.css (paylaşılan) |
| **Builder Family** | **Site Builder + Mobile Builder** | **site/builder.css (paylaşılan)** |
| Support Family | Ticket (müşteri + admin) | site/support.css |
| Marketplace | Marketplace | site/marketplace.css |
| AI | Ai (public/customer/admin) | site/ai-widget.css |
| Panel | Admin, Customer | admin/*.css |
| Ops | Health/QA, CookieAnalytics, Btk | admin CSS |
| E-Fatura (opt) | EInvoice (iskelet) | — |
| Meta | Theme (5 skin) | theme-switcher.css |

---

## 🔐 Güvenlik

- CSRF her POST/PUT/DELETE (`CsrfMiddleware`)
- Session regen on login, HTTP-only, SameSite=Lax
- Bcrypt cost 12 şifre hashleme
- **AES-256-GCM Encrypter** (API key, panel şifresi DB'de şifreli)
- Rate limit middleware
- Security headers (X-Frame, X-Content-Type, Referrer, Permissions)
- PDO prepared statements zorunlu
- View auto-escape
- **AI bağlam güvenliği** (admin AI public'e yönlendiremez)
- API log'ta hassas alanlar otomatik maskelenir
- Composer + PHPUnit + PHPMailer + dompdf + Google2FA + BaconQR
- Docker'da: OPcache JIT, expose_php off, güvenlik header'ları

---

## ⏰ Cron Görevleri (8 varsayılan)

| Görev | Sıklık | Ne yapar |
|---|---|---|
| `mail:queue` | Her dakika | Mail kuyruğunu işler |
| `domains:renewal-reminder` | Günlük | 30 gün içinde dolacak domainlere e-posta |
| `services:due-check` | Günlük | Vadesi geçen hizmetleri raporlar |
| `currency:update` | Saatlik | Kur güncelleme (API varsa) |
| `cache:clean` | Günlük | 24 saatten eski cache dosyalarını siler |
| `ratelimit:clean` | Saatlik | 1 saatten eski rate-limit dosyalarını siler |
| `logs:cleanup` | Günlük | Api log 90 gün, cookie 12 ay, mail 30 gün |
| `cron:log-cleanup` | Günlük | 60 gün öncesi cron loglarını siler |

Kurulum:
```bash
* * * * * cd /path/to/ahost-one && php console cron:run > /dev/null 2>&1
```

---

## 📧 9 Mail Template (DB'de düzenlenebilir)

- `customer_welcome` — Hoş geldin
- `order_received` — Sipariş alındı
- `payment_success` — Ödeme onaylandı
- `invoice_created` — Yeni fatura
- `service_active` — Hizmet aktifleşti
- `domain_renewal_reminder` — Domain süresi yaklaşıyor
- `ticket_reply` — Destek talebine yanıt
- `password_reset` — Şifre sıfırlama
- `mfa_code` — 2FA kodu

---

## 🧪 Test

```bash
composer test                      # PHPUnit — 37 test, 260 assertion
npm install                        # Playwright bağımlılıkları
npx playwright install chromium
npm run test:e2e                   # E2E tarayıcı testleri
```

---

## 📄 Lisans

Sahibine özel geliştirme — **Ahost Bilişim**.
