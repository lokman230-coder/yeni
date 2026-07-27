# 01 · Mimari

## 1. Tasarım İlkeleri (Sözleşme)

Bu ilkeler pazarlıksızdır. Şartnamedeki 2. ve 41. madde bunlardan doğar.

1. **Tek Sorumluluk** — Bir modül tek bir iş yapar (ör. `Cart` modülü sadece sepeti yönetir; vergi hesabı `Tax` servisine gider).
2. **Modül İzolasyonu** — Modüller birbirinin dosyasını `include` etmez; yalnız *servisleri* üzerinden konuşur.
3. **Yamasızlık** — Bir hata varsa kaynağı bulunur, kaynak modülünde çözülür. CSS `!important` ile ezme, JS `document.querySelector` ile başka modülü sabote etme yasaktır.
4. **Yeniden Kullanılabilirlik** — Ortak kod `app/Support/` içinde helper/service olur; kopyala-yapıştır yoktur.
5. **Deterministik Render** — Sayfa ilk boyanmasında final layout gelir. FOUC, layout shift, "önce masaüstü sonra mobil" davranışı bug'dır.
6. **Erişilebilirlik ve Hata Şeffaflığı** — Bir buton çalışmıyorsa neden çalışmadığını kullanıcıya söyler.

---

## 2. Katmanlar

```
┌────────────────────────────────────────────────────────────┐
│  PUBLIC WEB · ADMIN PANEL · CUSTOMER PANEL · JSON API      │  ← Sunum
├────────────────────────────────────────────────────────────┤
│  Controllers  ·  Middleware  ·  View (Templates)           │  ← HTTP
├────────────────────────────────────────────────────────────┤
│  Modules (Product, Cart, Domain, Hosting, Builder, AI...)  │  ← İş Kuralları
├────────────────────────────────────────────────────────────┤
│  Services (Tax, Currency, Mail, SMS, Auth, RBAC, Logger)   │  ← Ortak
├────────────────────────────────────────────────────────────┤
│  Repositories (PDO) · Models · Migrations                  │  ← Veri
├────────────────────────────────────────────────────────────┤
│  Integrations (PayTR, DomainNameAPI, cPanel, DA, Plesk)    │  ← Dış Dünya
└────────────────────────────────────────────────────────────┘
```

**Kural:** Aşağı doğru bağımlılık serbest, yukarı doğru bağımlılık YASAK. Örneğin `Repository`, `Controller`'ı bilmez.

---

## 3. İstek Yaşam Döngüsü

```
Tarayıcı
   │
   ▼
public/index.php           ← tek giriş noktası (front controller)
   │
   ├─ Bootstrap: env, config, error handler, autoload
   ├─ Session start (secure, httponly, samesite=Lax)
   ├─ Router::dispatch()
   │     │
   │     ├─ Route eşleşir → Middleware zinciri
   │     │      ├─ CsrfMiddleware
   │     │      ├─ AuthMiddleware (varsa)
   │     │      ├─ RbacMiddleware (varsa)
   │     │      ├─ RateLimitMiddleware
   │     │      └─ LocaleMiddleware (dil + para birimi)
   │     │
   │     └─ Controller::action($request)
   │            │
   │            ├─ Module servislerini çağırır
   │            │     └─ Service → Repository → PDO
   │            │
   │            └─ View::render('template', $data)
   │
   ▼
Response (HTML / JSON / File)
```

---

## 4. Modül Sözleşmesi (özet)

Detay: [`05-MODULE-CONTRACT.md`](05-MODULE-CONTRACT.md)

Her modül `app/Modules/<Name>/` altında şu yapıya sahiptir:

```
app/Modules/Product/
├── module.php                 ← modül tanımı (isim, versiyon, bağımlılık, route dosyaları)
├── routes/
│   ├── web.php
│   ├── admin.php
│   └── api.php
├── Controllers/
├── Services/
├── Repositories/
├── Models/
├── Migrations/                ← modüle özel tablolar
├── Views/                     ← modüle özel şablonlar
├── assets/
│   ├── css/product.css        ← ÖNEK: .aho-product-*  (isim çakışması yok)
│   └── js/product.js          ← IIFE veya ES module (global yok)
└── lang/
    ├── tr.php
    └── en.php
```

**CSS namespace kuralı:** Her modülün CSS class'ları `.aho-<module>-*` önekiyle başlar. Örn: `.aho-cart-item`, `.aho-product-card`. Header modülü `.aho-hdr-*`, footer `.aho-ftr-*`. Böylece Cart CSS'i Product CSS'i asla ezmez.

**JS namespace kuralı:** Her modül bir `AhostOne.<Module>` nesnesi altında yaşar. Event listener'lar `data-aho-*` attribute'ları üzerinden bağlanır; global `onclick` yok.

---

## 5. Dizin Ağacı (özet)

Tam ağaç: [`02-DIRECTORY-STRUCTURE.md`](02-DIRECTORY-STRUCTURE.md)

```
ahost-one/
├── public/               ← webroot (index.php + statik varlıklar)
├── app/
│   ├── Core/             ← Router, Container, Request, Response, View, Config
│   ├── Support/          ← Helper, Str, Arr, Money, Date, Validator
│   ├── Services/         ← Auth, Rbac, Tax, Currency, Mail, Sms, Logger, Cache
│   ├── Middleware/
│   ├── Modules/          ← Tüm iş modülleri
│   ├── Integrations/     ← PayTR, DomainNameAPI, cPanel, DirectAdmin, Plesk
│   └── Console/          ← Cron komutları
├── themes/
│   └── default/          ← Public tema; header/footer buradan gelir
├── storage/
│   ├── logs/
│   ├── cache/
│   ├── sessions/
│   └── uploads/
├── database/
│   ├── migrations/       ← ana çekirdek tablolar
│   └── seeds/
├── config/               ← app, db, mail, paytr, ai, ...
├── lang/                 ← ortak diller
├── install/              ← ilk kurulum sihirbazı (kurulum sonrası kilitlenir)
├── docs/
├── planning/
├── .env.example
├── composer.json
└── README.md
```

---

## 6. Konfigürasyon

- `.env` — ortama özel sırlar (DB, API key). Repo'ya asla girmez.
- `config/*.php` — `.env` değerlerini okuyan tipli konfigürasyon.
- Admin panelden değiştirilebilen ayarlar `settings` tablosunda tutulur; `Config::get('key')` önce runtime cache → sonra DB → sonra dosya sırasıyla okur.

---

## 7. Hata Yönetimi

- **Development:** tam trace, `whoops` tarzı sayfa.
- **Production:** kullanıcıya sade mesaj, detay `storage/logs/error-YYYY-MM-DD.log`.
- Fatal → 500 sayfası + admin bildirimi.
- 404 → tema 404 şablonu, aramada "acaba şunu mu demek istediniz" önerisi.
- Tüm exception'lar `App\Support\Logger` üzerinden merkezi loglanır.

---

## 8. Güvenlik Katmanları (özet)

Detay: [`06-SECURITY.md`](06-SECURITY.md)

- CSRF: her form otomatik token, middleware doğrular.
- XSS: `View::render` çıktıyı otomatik `htmlspecialchars`; ham HTML için `{!! !!}` bilinçli.
- SQLi: yalnızca PDO prepared statements.
- Session: `secure`, `httponly`, `samesite=Lax`, ID rotation on login.
- RBAC: rol → izin → controller action eşlemesi.
- Rate limit: login (5/dk), API (60/dk).
- 2FA: TOTP (Google Authenticator) — admin için önerilir, opsiyonel.

---

## 9. Çoklu Dil ve Para Birimi

- Dil: `LocaleMiddleware` sırayla `?lang=` → cookie → `Accept-Language` → default.
- Para birimi: aynı sırayla `?cur=` → cookie → default.
- Çeviri anahtarları modüllerin `lang/` klasöründe; `__('product.add_to_cart')`.
- Fiyat gösterimi tek servisten: `Money::display(199.90, 'USD')` → `199.90 $` veya `TL karşılığı` görselleştirmesi.

---

## 10. Deterministik Responsive Render (kritik)

Layout sıçraması olmaması için:

1. HTML `<html lang="tr" data-viewport="loading">` ile başlar.
2. Kritik CSS `<head>` içinde inline — sadece grid, header yüksekliği, font-face.
3. Ana CSS dosyaları `<link rel="stylesheet">` — `media="all"`.
4. Mobil-first: `.aho-hdr` default mobile; masaüstü `@media (min-width: 992px)` ile büyür.
5. JS yalnızca `defer`. İlk boyamada JS'e ihtiyaç YOK.
6. Font: `font-display: swap` + preload.
7. Görseller `width`/`height` attribute'lu, `loading="lazy"` (fold altı).

Kabul kriteri: **CLS (Cumulative Layout Shift) < 0.05**.

---

## 11. Test Stratejisi

- **PHPUnit** — servis ve repository unit testleri (Faz 1'den itibaren zorunlu).
- **Playwright** — public/admin/müşteri E2E senaryoları (Faz 2'den).
- **QA Scan Center** — admin panelinde, tüm route'ları otomatik crawl edip 500/404/warning arar (şartname madde 20).

---

## 12. "Yasak" Listesi (madde 41'in kod karşılığı)

| ❌ Yasak | ✅ Doğru |
|---|---|
| `!important` ile başka modülün stilini ezmek | Kendi modülünde spesifik selector |
| `$(document).on('click', 'button')` gibi geniş global handler | `data-aho-cart-add` gibi hedefli attribute |
| Controller içinde `mysqli_query` | Repository → PDO |
| View içinde `<?php $stmt = $pdo->query(...) ?>` | Controller data hazırlar, View gösterir |
| Aynı hesabı 3 yerde yazmak | `Tax::calculate()` servisi |
| "Şimdilik böyle kalsın" yorumu | Issue aç, düzelt, sonra merge |
| Sunucuya SSH ile elle dosya değiştirme | Migration + deploy |
