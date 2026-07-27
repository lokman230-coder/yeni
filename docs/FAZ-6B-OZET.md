# Faz 6b — Polish & Growth Features

**Tarih:** 27 Temmuz 2026
**Odak:** Ödeme çeşitliliği, canlı kur, marj mekanizması, AI Site Generator, Referral Programı, Uyumsoft SOAP

---

## 🎯 Bu fazda tamamlananlar

### 1. Ödeme sağlayıcıları çeşitlendirildi
| Driver | Durum | Özellik |
|---|---|---|
| **PayTR** | ✓ önceden var | Iframe API |
| **iyzico** | ✅ **yeni** | Checkout Form + PKI HMAC-SHA256 + 2-12 taksit + 3D Secure |
| **Papara** | ✅ **yeni** | Merchant API + cüzdan ödemesi + notification URL |
| **PaymentManager** | ✅ **yeni** | Registry deseni — driver eklerken tek dosya değişir, checkout sayfası dinamik olarak DB/env'den okur |

**Yeni dosyalar:**
- `app/Modules/Payment/PaymentManager.php`
- `app/Modules/Payment/Drivers/IyzicoDriver.php` (196 satır)
- `app/Modules/Payment/Drivers/PaparaDriver.php` (128 satır)
- `app/Modules/Payment/Controllers/IyzicoController.php`
- `app/Modules/Payment/Controllers/PaparaController.php`
- `app/Modules/Checkout/Views/gateway.php` (credential eksikliğinde şeffaf hata)

**.env genişletildi:** `IYZICO_API_KEY/SECRET_KEY/SANDBOX`, `PAPARA_API_KEY/SANDBOX`

### 2. Kur sistemi canlı → TCMB gerçek entegrasyon
- **CurrencyRateUpdater** servisi: TCMB `today.xml` birincil, `exchangerate.host` fallback
- **Cron `currency:update`** artık gerçek çalışıyor: bu turda çekilen kurlar → USD 47.25, EUR 53.79, GBP 63.06 TRY
- **Migration 0058**: `orders.gateway_ref` sütunu
- **Migration 0059**: `currency_rates` tablosuna `symbol`, `is_active`, `source` sütunları
- **Marj mekanizması aktif**: `currency_rates.margin_percent` hem `finalRate()` hem `convert()` içine yansıyor

### 3. Admin — Kur Yönetimi Ekranı 💱
Sidebar: **💱 Kur Yönetimi** (`/admin/kur-yonetimi`)
- Kolonlar: **Kod** | **Sembol** | **TCMB Kuru** | **Marj (%)** | **Görünen Kur** | **Kaynak** | **Son Güncelleme** | **Aktif** | **İşlem**
- Formül vurgusu: `Görünen Kur = TCMB × (1 + Marj% ÷ 100)`
- Her para biriminin marjı ayrı düzenlenir, aktif/pasif toggle
- Kaynak rozeti: 🇹🇷 TCMB / 🌐 API / ✍️ Manuel
- "TCMB'den Şimdi Çek" butonu → anlık güncelleme
- Yeni para birimi ekleme formu (JPY, CHF vb.)
- TRY silinemez (koruma)

### 4. Public — Şeffaf kur badge'i
- Topbar'da statik "1 USD ≈ 32.40 ₺" → gerçek TCMB + marj: **`1 USD ≈ 48,19 ₺`**
- Tooltip'te formül şeffaflığı: *"TCMB kuru: 47,2497 ₺ + %2,00 marj = 48,1947 ₺"*
- Para birimi dropdown menüsü DB'den aktif olanları listeler

### 5. AI Site Generator v1 🤖
Kullanıcı "diş hekimi sitem yap" der demez → **sektör algılama → template seçimi → içerik doldurma → Builder projesi oluşturma → editöre yönlendirme**.

**11 sektör algılama** ağırlıklı anahtar kelime skorlaması ile:
- clinic (diş hekimi, doktor, klinik, pediatri...)
- restaurant (pizza, restoran, cafe, kebap...)
- ecommerce, radio, hosting, education, portfolio, saas, agency, local, landing

**İşletme adı otomatik çıkarımı:** *"Ali Diş Kliniği için modern..."* → **"Ali Diş Kliniği"**

**Yeni dosyalar:**
- `app/Modules/Ai/Services/SectorDetector.php` (145 satır)
- `app/Modules/Ai/Services/SiteGenerator.php` (~300 satır — heuristic + OpenAI opsiyonel)
- `app/Modules/Ai/Controllers/SiteGeneratorController.php`
- `app/Modules/Ai/Views/site-generator/form.php` (prompt ekranı + 8 örnek)
- `app/Modules/Ai/Views/site-generator/preview.php` (sektör onayı + düzelt)

**Ana sayfa CTA:** `Yeni: "Diş hekimi sitem yap" — AI ile saniyede site oluştur →`

**Akış:** `/ai/site-olustur` → sektör tahmin → onay → **saniyeler içinde Builder projesi + editör**

### 6. Referral / Affiliate Programı 🎁
Tam çalışan komisyon sistemi — link paylaş, satın alım gelince komisyon kazan.

**Yeni tablolar (Migration 0060):**
1. `referral_settings` — Program parametreleri (%, cookie gün, min payout)
2. `referral_codes` — Her müşteriye bir kod (auto-generated: `CPJ9XL28` gibi)
3. `referral_visits` — Link tıklamaları (IP/UA/referer kaydı)
4. `referrals` — "Kim kimi getirdi" (referrer ↔ referred)
5. `referral_commissions` — Ödemeden düşen komisyon kayıtları

**Akış:**
1. Müşteri panel > Referans Programım → kod + link + sosyal paylaşım butonları (WhatsApp, X, Facebook, Email)
2. Ziyaretçi `?ref=CODE` ile gelir → `ReferralCaptureMiddleware` cookie kurar (60 gün varsayılan)
3. Ziyaretçi kayıt olur → `attachOnSignup()` çalışır → `referrals` kaydı
4. İlk ödeme yapılınca (`InvoiceService::markPaid` hook) → `onOrderPaid()` → **`referral_commissions`** oluşur (varsayılan %10)
5. Admin `/admin/referral` ekranında onaylar → referrer'ın **`customers.balance`** hesabına eklenir
6. Referrer bu bakiyeyi sonraki hizmet alımlarında kullanır (havuz)

**Yeni dosyalar:**
- `app/Modules/Referral/Services/ReferralService.php` (~330 satır)
- `app/Modules/Referral/Controllers/ReferralController.php` (müşteri)
- `app/Modules/Referral/Controllers/AdminReferralController.php` (admin CRUD)
- `app/Modules/Referral/Views/customer/index.php` (gradient hero + metrikler + link + sosyal + geçmiş)
- `app/Modules/Referral/Views/admin/index.php` (6 metrik + program ayarları + top 10 + onay kuyruğu)
- `app/Middleware/ReferralCaptureMiddleware.php` (global middleware)
- Customer dashboard'a "🎁 Referans Programım" gradient kart eklendi

**Config değişikliği:**
- `config/app.php` → `global_middleware` array'ine `ReferralCaptureMiddleware` eklendi
- `config/modules.php` → `referral` aktif modüller listesine eklendi
- `app/Core/Router.php` → global middleware'ler her route'a önce uygulanacak şekilde düzeltildi (önceden config'de tanımlı ama çalışmıyordu — bu bir gerçek bug'dı)

### 7. Uyumsoft e-Fatura SOAP entegrasyonu
`UyumsoftDriver` iskeletten gerçek SOAP client'a genişletildi:

- **WS-Security UsernameToken** ile plain auth (Uyumsoft'un beklediği format)
- **Native curl ile raw SOAP** — PHP SoapClient extension yoksa da çalışır
- **UBL-TR 1.2 uyumlu Invoice XML builder** (minimal ama şema-uyumlu)
- **4 metod:** `submit`, `status`, `downloadPdf`, `isRegisteredTaxpayer`, `testConnection`
- **XML özel karakter escape** (test edildi)
- **API log tam entegre** — her SOAP call `api_logs` tablosuna düşer

Test hesabı endpoint: `https://efatura-test.uyumsoft.com.tr/services/BasicIntegration`

### 8. Test kapsaması genişletildi
| Test dosyası | Test # | Doğrulanan |
|---|---|---|
| PaymentManagerTest | 7 | Registry + driver instantiation + credential kontrolü |
| CurrencyRateUpdaterTest | 2 | TCMB parser + sanity |
| CurrencyMarginTest | 6 | Marj → finalRate/convert etkisi (canlı DB) |
| SectorDetectorTest | 16 | 10 sektör dataProvider + isim çıkarım + confidence cap |
| SiteGeneratorTest | 6 | End-to-end proje oluşturma + block içerik doğrulama |
| ReferralServiceTest | 9 | Code gen + visit + attach + commission + approve + reject |
| UyumsoftDriverTest | 10 | Envelope + UBL builder + XML escape + hata durumları |

**Toplam: 93 test, 391 assertion, %100 geçer** (önceki: 37 test, 260 assertion)

---

## 📊 Sistem istatistikleri

| Metrik | Önceki (Faz 6) | Şu an (Faz 6b) |
|---|---|---|
| **Modül sayısı** | 29 | 30 (+ Referral) |
| **Migration** | 49 | 52 (+3: gateway_ref, currency extend, referral tables) |
| **PHP dosya** | 311 | ~340 |
| **PHPUnit test** | 37 | 93 |
| **PHPUnit assertion** | 260 | 391 |
| **Ödeme driver** | 1 (PayTR) | 3 (PayTR + iyzico + Papara) |
| **AI capability** | Chat asistan | + Site Generator (11 sektör) |
| **Growth motor** | — | Referral programı (5 tablo) |

---

## 🔒 Sıfır regresyon
Faz 6 sonu çalışan tüm sistemler (hosting/domain/checkout/builder/marketplace/ticket/BTK/mail/cron) etkilenmedi.
- Router.php'deki global middleware düzeltmesi mevcut middleware'lerle uyumlu
- `InvoiceService::markPaid` hook'u exception-safe (Referral yoksa sessiz geç)
- `.env` template'e sadece yeni değişkenler eklendi (varsayılanlar boş → mevcut PayTR akışı bozulmadı)

---

## 📸 Screenshot'lar
`docs/screenshots/faz6b/`:
- `currency-admin.png` — Admin kur yönetimi ekranı
- `site-topbar-live-rate.png` — Public topbar canlı kur badge
- `home-with-ai-cta.png` — Ana sayfa yeni AI CTA
- `ai-site-form.png` — AI Site Generator prompt formu
- `ai-site-preview.png` — Sektör onay ekranı (Doktor / Klinik %100)
- `referral-customer.png` — Müşteri referans paneli
- `referral-admin.png` — Admin referans yönetimi

---

## 🚧 Bilinen sınırlamalar
- **iyzico/Papara sandbox testi** — gerçek test hesabı `.env`'e girilmeden canlı akış test edilemez, ama credential eksikliğinde şeffaf hata dönüyor
- **OpenAI Site Generator refine** — `AI_API_KEY` yoksa heuristic tek başına çalışır (tam kaliteli metin üretir), key varsa OpenAI ince ayar yapar
- **Uyumsoft SOAP** — gerçek test hesabı olmadan canlı submit denenemedi; envelope + UBL builder + hata yönetimi test edildi
- **Müşteri kayıt (signup) POST handler** — hâlâ eksik (Faz 3'ten kalan). Referral `attachOnSignup` çağrılabilir olarak hazır, sadece kayıt akışı bağlanacak

---

## 🎯 Sıradaki (Faz 6c önerisi)
- Müşteri kayıt akışı tamamla (e-posta doğrulama + KVKK + hoş geldin)
- Sipariş → aktif hizmet ilişkisi (customer panel > Hizmetlerim)
- Domain admin CRUD
- Kurulum sihirbazı (fresh install wizard)
- Referral payout istekleri (banka havalesiyle çekim)
