# Ahost Bilişim — Geliştirme Yol Haritası

Bu doküman, projeyi **teslim edilebilir fazlara** ayırır. Her faz sonunda çalışan,
gösterilebilir, geri alınabilir bir çıktı vardır.

---

## Genel Bakış

| Faz | Ad | Süre (tahmini) | Ana çıktı | Bağımlılık |
|---|---|---|---|---|
| **0** | Planlama & Dokümantasyon | ✅ *(bu tur tamamlandı)* | 10 doküman, ERD, dosya ağacı | — |
| **1** | Çekirdek İskelet | ~4 tur | Router, DI, PDO, Auth, RBAC, Migration, Header/Footer, Tema | Faz 0 |
| **2** | Public + Panel Shell'leri | ~4 tur | 30+ public sayfa, admin & müşteri panel iskeletleri, i18n, kur, çerez | Faz 1 |
| **3** | Ürün + Sepet + Ödeme | ~5 tur | Ürün merkezi, fiyat/periyot, ek paket, özel alan, sepet, PayTR, fatura | Faz 2 |
| **4** | Domain + Hosting + Site Araçları | ~5 tur | DomainNameAPI, WHOIS/DNS/SSL, cPanel/DA/Plesk, 15+ araç | Faz 3 |
| **5** | Builder + AI + Marketplace + Destek + BTK | ~6 tur | Site Builder (Elementor+VC+AI), Mobile Builder, AI 3-bağlam, Marketplace, Ticket, BTK CSV | Faz 4 |
| **6** | Sertleştirme & Yayın | ~2 tur | Test coverage, penetrasyon, dokümantasyon, kurulum sihirbazı | Faz 5 |

**Tur** = benimle yaptığımız bir konuşma turu. Her tur ~10-30 dosya + doküman üretebilir.

---

## Faz 0 — Planlama & Dokümantasyon ✅

**Durum:** Tamamlandı (bu tur)

**Çıktılar:**
- `docs/00-COMPETITIVE-EDGE.md` — rakip analizi
- `docs/01-ARCHITECTURE.md` — mimari kararlar
- `docs/02-DIRECTORY-STRUCTURE.md` — dosya ağacı
- `docs/03-DATABASE-ERD.md` — 48 tablo şema
- `docs/04-CODING-STANDARDS.md` — PHP/CSS/JS standartları
- `docs/05-MODULE-CONTRACT.md` — modül sözleşmesi
- `docs/06-SECURITY.md` — güvenlik katmanları
- `docs/07-API-CONTRACTS.md` — entegrasyon arayüzleri
- `docs/08-UI-DESIGN-SYSTEM.md` — tasarım sistemi
- `planning/ROADMAP.md` — bu doküman
- `planning/ACCEPTANCE-MATRIX.md` — kabul kriterleri

**Kabul:** Yukarıdaki dokümanların tamamı sizin onayınızdan geçer.

---

## Faz 1 — Çekirdek İskelet

**Amaç:** Uygulamanın kalp attığı temel — istek gelir, işlenir, cevap döner. Login çalışır. Migration çalışır.

### Tur 1.1 — Bootstrap & Router
- `public/index.php`, `.htaccess`
- `app/Core/Application.php`, `Container.php`, `Router.php`, `Route.php`, `Request.php`, `Response.php`
- `app/Core/Config.php`, `Env.php`, `ErrorHandler.php`
- `app/Support/helpers.php`
- `config/app.php`, `config/database.php`, `.env.example`
- `composer.json` + PSR-4 autoload
- **Test:** basit "Hello Ahost Bilişim" home route çalışır.

### Tur 1.2 — Veritabanı & Migration
- `app/Core/Database/Connection.php` (PDO)
- `app/Core/Database/Migrator.php`, `Schema.php`, `Blueprint.php`, `QueryBuilder.php`
- `app/Console/console` CLI + `MigrateCommand`, `SeedCommand`, `FreshCommand`, `RollbackCommand`
- İlk 15 migration dosyası (settings, roles, permissions, admins, customers, ...)
- Seeder: `DefaultSettingsSeeder`, `DefaultRolesSeeder`, `DefaultCurrenciesSeeder`
- **Test:** `php console migrate:fresh --seed` → tablolar ve seed veriler oluşur.

### Tur 1.3 — Auth, Session, RBAC, CSRF
- `app/Services/Auth/*` — AuthService, SessionGuard, PasswordHasher
- `app/Services/Rbac/*` — RbacService, PermissionRegistry
- `app/Middleware/*` — Csrf, Auth, AdminAuth, CustomerAuth, Rbac, RateLimit, Locale, Currency, SecurityHeaders
- Admin login sayfası (basit)
- **Test:** Admin login çalışır, izinsiz sayfa 403 döner.

### Tur 1.4 — Tema Sistemi + Header/Footer Modülleri + View Engine
- `app/Core/View.php` — native PHP template, auto-escape, extend/section
- `themes/default/layouts/{public,admin,customer}.php`
- `themes/default/css/theme.css`, `theme.js`, `reset.css`, `components/*`
- `app/Modules/Header/*` — topbar + header modülü
- `app/Modules/Footer/*`
- Lucide icon sprite entegrasyonu
- **Test:** Ana sayfa açılır, header/footer görünür, dark mode çalışır, responsive doğru.

**Faz 1 Kabul:**
- `php console migrate:fresh --seed` sıfırdan sistemi kurar.
- Admin login → boş dashboard'a düşer.
- Public ana sayfada header/footer render olur.
- Konsol/log'da warning/error yok.
- CLS < 0.05.

---

## Faz 2 — Public Site + Admin/Müşteri Panel Shell'leri

**Amaç:** Şartname madde 5'teki 30+ public sayfa route'u + admin/müşteri panel iskeletleri hazır.

### Tur 2.1 — Public Modülleri
- `Home`, `Pages` (about, mission, vision, privacy, cookie-policy, terms, service-policy, refund, contact)
- `Blog` (public listing + detay)
- `Announcements`
- `References`
- `Knowledge` (kategori + makale)
- Contact form (reCAPTCHA)

### Tur 2.2 — i18n, Kur, Çerez
- `app/Middleware/LocaleMiddleware` + `lang/tr,en`
- `app/Services/Currency/CurrencyService` + admin kur merkezi ekranı
- `CookieAnalytics` modülü — banner, tracker (kabul/red), admin dashboard iskeleti
- Topbar'da dil + para birimi switcher çalışır

### Tur 2.3 — Admin Panel Shell
- `Admin` modülü — layout, sidebar, topbar, dashboard
- Menü: 22 admin menüsü (şartname 20) — hepsi route'lu ve boş sayfa açılıyor
- Quick search iskeleti
- Notification dropdown

### Tur 2.4 — Müşteri Panel Shell
- `Customer` modülü — layout, sidebar, dashboard
- Menü: Dashboard, Hizmetlerim, Domainlerim, Faturalarım, Siparişlerim, Destek, Bildirimler, Profil, Güvenlik, Bakiye, Builder, AI (hepsi iskelet)
- Register + Login + Password reset flow'u

**Faz 2 Kabul:**
- 30+ public sayfa 200 döner, layout bozulmaz.
- Dil ve para birimi değişimi tüm public'te çalışır.
- Çerez banner çalışır, red ederse analytics yazmaz.
- Admin & müşteri panellerinde tüm menüler açılır (boş sayfa da olsa).
- Mobile responsive kusursuz.

---

## Faz 3 — Ürün + Sepet + Ödeme + Fatura

**Amaç:** Bir müşterinin ürün seçip ödeme yaparak fatura almasına kadar tam akış.

### Tur 3.1 — Ürün Merkezi (Admin)
- `Product` modülü — CRUD, grup, tip, görsel, SEO
- `PricingService` — periyot bazlı fiyat, kaynak para birimi → hedef kur+marj dönüşümü
- Fiyat ekleme UI: "Fiyatlandırma Ekle" butonu → satır ekle (şartname 10)
- Ek paket UI (şartname 12)
- Özel alan UI (şartname 13)
- Çapraz satış
- SEO alanları

### Tur 3.2 — Ürün Public Sayfaları
- Hosting listeleme + detay
- VPS/Sunucu listeleme
- Domain listeleme (Domain modülüne bağlanır Faz 4'te tam)
- Site Builder demo tanıtım
- Marketplace tanıtım

### Tur 3.3 — Sepet
- `Cart` modülü (session + customer)
- Fatura periyodu seçimi
- Ek paket ekle/çıkar
- Özel alan doldurma
- Kupon uygulama (`Coupon` modülü)
- `TaxService` — anlık vergi (şartname 14)
- Domain seçim akışı (register/transfer/use_own/update_dns) — ince kartlar (şartname 14)

### Tur 3.4 — Checkout + Ödeme
- Checkout flow: sepet → müşteri bilgileri → fatura bilgileri → ödeme yöntemi → ödeme
- `Payment` modülü + `PayTrDriver` + `BankTransferDriver` + `BalanceDriver` + `ManualDriver`
- PayTR iframe entegrasyonu + callback handler + imza doğrulama

### Tur 3.5 — Fatura + Bildirim
- `Invoice` modülü — otomatik oluşturma, PDF export (dompdf)
- Fatura ödeme akışı
- Bildirim şablonları (hoş geldin, sipariş alındı, ödeme başarılı, fatura oluşturuldu, hizmet aktif)
- E-posta kuyruk cron

**Faz 3 Kabul:**
- Test müşterisi ürün seçer → sepete atar → periyot seçer → ek paket ekler → özel alan doldurur → kupon uygular → ödeme yapar (test modu PayTR) → fatura otomatik oluşur → hizmet "pending" olur (otomasyon Faz 4).
- Kur değişince tüm sepet tutarları güncellenir.
- Vergi ve indirim doğru sırayla hesaplanır (şartname 14).

---

## Faz 4 — Domain + Hosting Otomasyon + Site Araçları

### Tur 4.1 — Domain Modülü + DomainNameAPI
- `Registrar` modülü + `DomainNameApiDriver`
- Domain sorgulama (batch check, TLD önerisi)
- WHOIS servisi + premium kart UI
- DNS records
- SSL kontrol
- Domain değerleme motoru (`ValuationService` — TLD ağırlığı, uzunluk, marka gücü heuristik, yaş, SEO sinyalleri)

### Tur 4.2 — Domain Yönetimi (Müşteri + Admin)
- Domain satın alma → registrar API register
- Transfer akışı + EPP kodu
- Yenileme
- Transfer kilit toggle
- Nameserver güncelleme
- WHOIS contact güncelleme
- API log ekranı (admin)

### Tur 4.3 — Hosting Otomasyonu
- `Hosting` modülü + `Server` modülü
- Driver'lar: cPanel, DirectAdmin, Plesk, Manual
- Sipariş ödendiğinde otomatik hesap oluşturma trigger
- Askı, askıdan alma, şifre değiştir, paket değiştir, sil, kullanım çekme

### Tur 4.4 — Site Araçları (15 araç)
- `SiteTools` modülü, her araç ayrı class
- WHOIS, DNS Check, SSL Check, SEO Analyze, Site Analyze, Speed Test, Security Headers, IP Lookup, Ping, HTTP Header, Robots Check, Sitemap Check, Meta Analyze, Link Analyze, Image Alt Analyze
- Premium sonuç kartları — açıklamalı, teknik terim minimum
- SEO analiz ≠ Site analiz farkı net (şartname 19)

### Tur 4.5 — Sertleştirme + QA Scan
- QA Scan Center — tüm route'ları otomatik crawl, 500/404/warning rapor
- Health Center — servis durumu, DB, cache, cron, disk
- Log Center

**Faz 4 Kabul:**
- Gerçek DomainNameAPI (test moduyla) domain sorgu döndürür.
- WHOIS/DNS/SSL kartları premium görünüyor.
- Test cPanel sunucusuna otomatik hesap açıyor.
- Tüm site araçları gerçek veri döndürüyor.

---

## Faz 5 — Builder + AI + Marketplace + Destek + BTK

### Tur 5.1 — Site Builder — Editor Core
- Editor UI (canvas + sidebar + top bar)
- Blok API (Structural + Content + Marketing)
- JSON tree storage
- Server-side renderer
- Cihaz görünüm switch
- Global renk & tipografi
- Undo/redo

### Tur 5.2 — Site Builder — Şablonlar + Bloklar
- 11 sektör şablonu (Hosting, Agency, Landing, Radio, Ecommerce, Restaurant, Clinic, Education, Portfolio, SaaS, Local)
- Sektöre göre uygun blok filtresi (Hosting seçiliyken DJ alanları görünmez — şartname 23)
- Reusable templates
- Export (Zip + Kaynak kod)

### Tur 5.3 — Mobile Builder
- Telefon mockup canvas
- 8 sektör şablonu
- Radyo player, e-ticaret sepet, push notification blokları
- APK/AAB build queue (Faz 5 kapsamında builder JSON'u üretilir; gerçek APK build server-side worker Faz 6'da)

### Tur 5.4 — AI (3 Bağlam)
- `AiProviderInterface` + OpenAI/Anthropic driver
- `ContextBuilder` public/customer/admin
- `ActionRunner` — güvenli aksiyon çalıştırma
- Kayan widget UI (public sol alt, admin/customer uygun konum)
- Site oluştur flow: 2 buton (AI ile paket al / AI olmadan devam)
- Builder içinde "bana şu bölümü ekle" AI komutları

### Tur 5.5 — Marketplace + Destek + Bilgi Bankası
- Marketplace: kategori, ilan CRUD, teklif, admin onay, komisyon, premium kartlar
- Destek: ticket CRUD, departman, öncelik, ek dosya, makro cevap, atama
- Bilgi Bankası admin CRUD

### Tur 5.6 — BTK + Bildirim + Cron Tam
- BTK CSV export (hosting, domain, müşteri, IP, tarih, iletişim)
- Bildirim kanalları tam (mail + SMS + panel + admin)
- Tüm cron komutları yazılı ve schedule dosyası

**Faz 5 Kabul:**
- Site Builder ile bir hosting sitesi 5 dakikada oluşturulabilir.
- Mobile Builder bir radyo uygulaması ayarlanabilir.
- AI müşteri sorusunu cevaplar ve doğru aksiyon çalıştırır (test: "faturam ne kadar?" → cevap).
- Marketplace'te ilan verilebilir, teklif yapılabilir.
- BTK CSV indirilebilir.

---

## Faz 6 — Sertleştirme & Yayın

### Tur 6.1 — Kurulum Sihirbazı + Test Coverage
- `install/` — 6 adımlı sihirbaz (şartname 37)
- PHPUnit coverage %60+
- Playwright E2E kritik flow'lar
- Load test (100 concurrent user)

### Tur 6.2 — Dokümantasyon + Deploy
- Kullanıcı kılavuzu (Türkçe PDF/HTML)
- Admin kılavuzu
- API dokümantasyonu (Swagger)
- Deploy script (composer install, migrate, seed, cache warm)
- Yedekleme + restore prosedürü
- Penetrasyon test checklist'i çalıştırıldı, bulgular kapatıldı

**Faz 6 Kabul (Yayına hazır):**
- Şartname madde 39 & 40'daki tüm maddeler ✅

---

## Toplam Tahmini Efor

| Faz | Tur Sayısı | Kümülatif Tur |
|---|---|---|
| 0 | 1 | 1 |
| 1 | 4 | 5 |
| 2 | 4 | 9 |
| 3 | 5 | 14 |
| 4 | 5 | 19 |
| 5 | 6 | 25 |
| 6 | 2 | **27** |

**Not:** Bir tur çok verimli geçerse birden fazla alt-tur çıktı verebilir. Tersi de mümkün — karmaşık modüller için ek tur gerekebilir. Bu tahmin, her tur ~10-30 dosya varsayımıyladır.

---

## Onay Kapıları

Her faz sonunda:
1. Ben çıktıyı sunuyorum.
2. Siz test ediyorsunuz.
3. **Onaylarsanız** bir sonraki faza geçiyoruz.
4. **Düzeltme isterseniz** aynı faz içinde revizyon turu yapıyoruz.

Kritik: **Faz atlanmaz.** Faz 3'e Faz 2 tamamlanmadan geçmiyoruz. "Yamayı" ancak bu disiplin engeller.
