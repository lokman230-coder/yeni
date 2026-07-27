# Faz 6c — Customer Journey & Ops

**Tarih:** 27 Temmuz 2026
**Odak:** Müşteri kayıt akışı, kişisel panel (Hizmetler/Faturalar/Domain/Sipariş), Admin Domain Center CRUD, Kurulum Sihirbazı

---

## 🎯 Bu fazda tamamlananlar

### 1. Müşteri Kayıt Akışı 🆕 (**tam çalışan** — Faz 3'ten beri eksikti)
Kayıt akışı sonuna kadar bağlandı:

- **`AuthService::registerCustomer($data)`** — server-side validation + password hash + otomatik login
- **POST `/kayit`** handler + `customer::auth.register` view yenilendi (KVKK checkbox, hata renklendirme, "eski değerleri koru")
- **Referral entegrasyonu** — `?ref=CODE` cookie ile gelen ziyaretçi kayıt olursa `attachOnSignup()` otomatik çalışır → `referrals` tablosuna satır düşer
- **Şeffaf referrer bilgisi** — kayıt formunun üstünde *"🎁 Test Müşteri sizi davet etti — kaydolduktan sonra alışverişleriniz onun komisyon havuzuna katkı sağlayacak. Bu size ek maliyet getirmez."*
- **Hoş geldin e-postası** — `Mailer::send('welcome', ...)` otomatik tetikleniyor (template varsa)
- **KVKK zorunlu** — 3 link (Üyelik Sözleşmesi, KVKK Aydınlatma, Gizlilik Politikası) + checkbox

**Doğrulanmış senaryo:**
1. `?ref=CPJ9XL28` → cookie kuruldu
2. `/kayit` → "sizi davet etti" mesajı görünür
3. Form gönderildi → 302 → `/panel` (otomatik login) ✓
4. `referrals` tablosunda `referrer_customer_id=1, referred_...=75, code=CPJ9XL28, status=pending` ✓

### 2. Müşteri Paneli — 5 sayfa 🖥️
`PanelController` ile 5 ekran:

| URL | İçerik |
|---|---|
| `/panel` | Dashboard — 4 metrik + 3 hızlı erişim + ödenmemiş fatura uyarısı |
| `/panel/hizmetlerim` | Hosting hesapları — durum rozetleri, yenileme sayacı, "Detay" linki |
| `/panel/faturalarim` | Faturalar — durum, kalan, **"Öde" butonu** (ödenmemişse) + PDF ikonu |
| `/panel/domainlerim` | Domainler — bitiş, auto-renew, gün sayacı, transfer/lock durumu |
| `/panel/siparislerim` | Siparişler — ödeme yöntemi ikonu (💳/💠/🟨/🏦/💰), durum, "Öde"/"Detay" |

Ortak: **`_sidebar.php`** partial — 8 menü öğesi + aktif rota vurgusu + çıkış butonu, sticky pozisyon, mobile-uyumlu grid.

Dashboard'daki gerçek metrikler: `PanelController::stats()` DB'den anlık okur (services_active, domains, invoices_unpaid, unpaid_total, balance).

### 3. Admin — Domain Center CRUD 🌐
Sidebar linki artık gerçek CRUD ekrana bağlı: `/admin/domain-center`

- **Liste ekranı:** 5 metrik kartı (Toplam / Aktif / Bekleyen / Süresi Geçen / 30 güne bitecek), arama + durum filtresi, tablo (Domain / Müşteri / Registrar / Bitiş / Auto / Durum / Düzenle)
- **Düzenleme ekranı:** Durum + Registrar + Bitiş/Yenileme tarihleri + Auto Renew/Transfer Lock/WHOIS Privacy toggle + Nameservers textarea + EPP Code + **Sil butonu (confirm dialog)**
- **Yeni domain ekleme:** Manuel giriş (registrar API dışı), validation ile

**Yeni dosyalar:**
- `app/Modules/Domain/Controllers/AdminDomainController.php` (~180 satır)
- `app/Modules/Domain/Views/admin/{index,edit,create}.php`
- Domain routes'a admin endpoint grubu eklendi

### 4. Kurulum Sihirbazı 🚀 (**fresh install wizard**)
Sıfırdan kuruluma tam otomasyon. 5 adım + tamamlandı ekranı.

**Nasıl çalışır:**
- `storage/installed.lock` dosyası **yoksa** → `SetupMiddleware` her isteği `/kurulum`'a redirect eder
- Kurulum tamamlanınca → `InstallGate::markInstalled()` lock dosyasını oluşturur
- Kurulumu tekrar çalıştırmak için: `storage/installed.lock` dosyasını sil

**5 adım:**
1. **Sistem Gereksinimleri** — 11 kontrol (PHP 8.2+, pdo_mysql, mbstring, curl, openssl, gd, zip, json, .env yazılabilir, storage yazılabilir, soap opsiyonel)
2. **Veritabanı** — Host/Port/DB/User/Password → **canlı bağlantı testi** + `.env` otomatik güncellenir
3. **Migration** — `php console migrate` çağrılır, tüm log ekranda gösterilir
4. **Admin Oluştur** — Ad/Soyad/E-posta/Şifre → `admins` tablosuna eklenir (rol seed dahil)
5. **Site Bilgileri** — Site adı + URL + (opsiyonel) SMTP ayarları → `.env` yazılır + `settings.site.name` DB'ye eklenir

**Tasarım:** Full-screen gradient (mavi→mor), progress bar (adım sayacı yeşil checkler), her adım tek sayfa, "Geri/Devam" navigation.

**Yeni dosyalar:**
- `app/Modules/Setup/module.php`
- `app/Modules/Setup/Services/InstallGate.php`
- `app/Modules/Setup/Controllers/SetupController.php` (~180 satır — 5 adım + finalize + .env atomik yazıcı)
- `app/Modules/Setup/Views/wizard.php` (5 adımlı switch tek dosya)
- `app/Modules/Setup/Views/done.php` (tamamlandı ekranı + sıradaki adımlar checklist)
- `app/Modules/Setup/routes/web.php`
- `app/Middleware/SetupMiddleware.php` (global)

### 5. Test kapsaması genişletildi
| Test dosyası | Test # |
|---|---|
| CustomerRegistrationTest | 7 (validation + duplicate + auto-login) |

**Toplam kümülatif: 100 test, 409 assertion, %100 geçer** (önceki: 93, 391)

---

## 📊 Sistem istatistikleri

| Metrik | Faz 6b sonu | Faz 6c sonu |
|---|---|---|
| **Modül sayısı** | 30 | **32** (+ Setup, güncellenmiş Customer/Domain) |
| **Migration** | 52 | 52 |
| **PHPUnit test** | 93 | **100** |
| **Assertion** | 391 | **409** |
| **Global middleware** | 2 | **3** (+ SetupMiddleware) |
| **Müşteri panel sayfaları** | 1 (iskelet) | **5** (dashboard+hizmet+fatura+domain+sipariş) |
| **Admin CRUD ekranı** | Ayarlar/Loglar/Cache/Kur/Referral | + **Domain Center** |

---

## 🔒 Sıfır regresyon
- Mevcut kurulum bozulmasın diye `storage/installed.lock` seed olarak oluşturuldu → SetupMiddleware var olan sistemde şeffaf çalışıyor
- Sadece lock silinince (fresh install senaryosu) wizard'a yönlendirme aktifleşir
- 100/100 test geçiyor

---

## 📸 Screenshot'lar
`docs/screenshots/faz6c/`:
- `register-with-referral.png` — Kayıt formu (referrer davet mesajlı)
- `customer-dashboard.png` — Yeni müşteri paneli (sidebar + 4 metrik + 3 hızlı erişim + uyarı bandı)
- `customer-invoices.png` — Faturalarım (INV-... 961.20₺, "Öde" butonlu)
- `setup-wizard-step1.png` — Kurulum sihirbazı sistem kontrolü (11 check, gradient tasarım)

---

## 🚧 Bilinen boşluklar (Faz 6d+ için)
- Ödeme sonrası **`orders` → `hosting_accounts` provisioning** otomasyonu (şu an manuel — sipariş paid olduğunda cPanel/DA hesap otomatik açılmıyor)
- Hizmet detay sayfası (`/panel/hizmet/{id}`) — link var ama view yok
- Domain admin: **DomainNameAPI SOAP sync** butonu (registrar'dan gerçek bilgi çekme) — driver hazır, buton yok
- E-posta doğrulama akışı (kayıt sonrası verification link) — hoş geldin mail çalışıyor ama verification zorunlu değil
- 2FA (google2fa vendor'da hazır, arayüz yok)
- Şifremi unuttum akışı

---

## 🎯 Sıradaki (Faz 6d önerisi)
- Ödeme → hosting hesap otomatik açılışı (`ProvisionService`)
- Şifremi unuttum + e-posta doğrulama
- 2FA (admin için zorunlu, müşteri için opsiyonel)
- Server (VPS) admin CRUD (`/admin/hosting-sunucu`)
- Ticket admin ekranı (client-side thread akışı)
