# Faz 6d — Provisioning & Auth Güvenlik

**Tarih:** 27 Temmuz 2026
**Odak:** Otomatik hosting provisioning, şifremi unuttum, 2FA (google2fa), Server admin CRUD, Hizmet detay sayfası

---

## 🎯 Bu fazda tamamlananlar

### 1. Otomatik Provisioning ⚙️
**Sipariş ödendiğinde hosting hesabı otomatik açılıyor.**

Akış: `InvoiceService::markPaid` → **`ProvisionService::provisionOrder`** →
- Her `order_item` için ürün tipine göre işlem
  - `hosting/email_hosting/radio_hosting` → sunucu seç + driver ile hesap oluştur
  - `vps/dedicated` → manuel kuyruğa al (admin bildirim)
  - `domain` → Domain modülü zaten hallediyor
- **Sunucu seçimi:** En az yüklü aktif sunucu (`hosting_servers` içinde `is_active=1`, `current_accounts < max_accounts`, panel != 'manual')
- **Kimlik + şifre:** Otomatik üretim (`orne1234` username, 16-char karışık password)
- **Encrypter** (AES-256-GCM) ile password saklama
- **Hoş geldin maili:** `hosting_activated` template ile domain + username + password + panel URL gönderimi
- **Manuel kuyruk:** Sunucu bulunamazsa `notifications` tablosuna admin bildirimi

**Kritik korumalar:**
- Aynı `order_item_id` için ikinci provisioning → skipped (duplicate açma yok)
- Panel hatası → hesap `pending` status'ta kalır, `notes`'a hata düşer
- Try-catch her adımda → provision hatası siparişi bozmaz

**Yeni dosya:** `app/Modules/Hosting/Services/ProvisionService.php` (~230 satır)

### 2. Şifremi Unuttum 🔑
Tam çalışan reset akışı — **enumeration önleme** dahil.

- **`AuthTokenService`** — password_reset / email_verify / magic_link (3 amaç, tek tablo)
- **`PasswordResetService`** — request → validate → reset akışı
- **Migration `0061`:** `auth_tokens` tablosu (token, expires_at, used_at, purpose)

**Güvenlik:**
- Kullanıcı olsun olmasın **aynı response** (email enumeration önleme)
- Token 60 dk geçerli, tek kullanımlık (`used_at` set edilir)
- Yeni request → önceki tokenlar geçersiz olur
- Süresi geçmiş tokenlar 7 gün sonra cleanup edilir

**Yeni URL'ler:**
- `/sifremi-unuttum` (GET+POST)
- `/sifre-sifirla?token=...` (GET+POST)

**Test:** Full akış doğrulandı — POST → 302 → token used_at set edildi → login yeni şifreyle çalıştı.

### 3. 2FA — Google Authenticator 🔐
`pragmarx/google2fa` + `bacon/bacon-qr-code` (zaten `vendor/`'daydı).

**Migration `0062`:** `admins` ve `customers` tablolarına eklendi:
- `two_factor_secret_encrypted` (AES-256-GCM)
- `two_factor_confirmed_at`
- `two_factor_recovery_codes` (JSON encrypted)

**`TwoFactorService`:**
- `generateSecret()` — 32 char Base32
- `qrCodeSvg($issuer, $email, $secret)` — SVG QR (Google Auth/Authy/1Password uyumlu)
- `saveSecret / confirm / verify / disable / isEnabled`
- **10 recovery code** setup sırasında üretilir (`XXXX-XXXX` formatında)
- Recovery code kullanılınca **tek kullanımlık** çıkarılır

**Login akışı** (hem admin hem customer):
1. `attemptAdmin/Customer` → 'ok' | '2fa' | 'fail' döndürür
2. `2fa` dönerse → `pending_2fa_customer_id` session'a → 2FA sayfasına redirect
3. Kod doğruysa `completeTwoFactorAdmin/Customer` → gerçek login state

**Yeni sayfalar:**
- `/panel/guvenlik` (customer 2FA setup — QR + secret + onay)
- `/admin/2fa` + `/giris/2fa` (login sırasında kod ekranı)

**Test:** 6 unit test → OTP verify + recovery verify + aynı recovery ikinci kez çalışmıyor + disable sonrası tüm state temizleniyor.

### 4. Server (VPS) Admin CRUD 🖥️
`/admin/hosting-sunucu` gerçek CRUD ekranı.

- **Liste:** Ad / Hostname / Panel rozeti (cPanel turuncu, DA cyan, Plesk mavi, Manuel gri) / Port + SSL ikonu / Hesap sayacı (X/Y) / **Yük progress bar** (renk kodlu: %70 sarı, %90 kırmızı) / Durum / Düzenle
- **Form:** Genel (ad, hostname, IP, server_group) + Panel & Bağlantı (panel seçimi, port otomatik: cPanel=2087, DA=2222, Plesk=8443) + Kullanıcı/Şifre/API Key (encrypted) + SSL/Aktif toggle + Max hesap
- **"Bağlantıyı Test Et" butonu** — AJAX ile driver'ı çağırıp sonucu alert olarak gösterir
- Silme — bağlı hesap varsa engelleniyor (uyarı)

**Yeni dosyalar:**
- `app/Modules/Hosting/Controllers/AdminServerController.php`
- `app/Modules/Hosting/Views/admin/{index,form}.php`
- `app/Modules/Hosting/routes/web.php`

Admin stub'lardan `hosting-sunucu` + `domain-center` çıkarıldı → gerçek modüller devraldı.

### 5. Hizmet Detay Sayfası (`/panel/hizmet/{id}`)
Faz 6c'de link vardı, view yoktu. Şimdi tam:

- Durum rozeti + Kontrol Paneli butonu (cPanel/DA/Plesk URL otomatik oluşur)
- **4 bilgi kartı:** Domain / Kullanıcı Adı / Sunucu / Sonraki Yenileme (gün sayacı)
- **Kaynak Kullanımı** kartı (disk MB + trafik MB)
- **Durum uyarıları:**
  - pending → sarı bant "Kurulum devam ediyor"
  - suspended → kırmızı bant "Askıya alındı → Destek talebi oluştur"
- **İlgili sipariş** kartı — order_number + tutar + tarih

### 6. Router bug fix — global middleware sırası
Faz 6c'de eklenen `SetupMiddleware` global stack'te en başa alındı (kurulum yoksa her istek `/kurulum`'a yönlensin). `ReferralCaptureMiddleware` de global stack'te (zaten Faz 6b'de eklendi).

---

## 📊 Test kapsaması

| Test dosyası | Test | Assertion |
|---|---|---|
| ProvisionServiceTest | 5 | 15 |
| PasswordResetTest | 8 | 20 |
| TwoFactorServiceTest | 6 | 25 |

**Toplam kümülatif: 119 test, 467 assertion, %100 geçer** (önceki: 100, 409)

---

## 📊 Sistem istatistikleri

| Metrik | Faz 6c | Faz 6d |
|---|---|---|
| **Modül** | 32 | 32 (Hosting genişletildi) |
| **Migration** | 52 | **54** (+auth_tokens, +2fa columns) |
| **Global middleware** | 3 | 3 |
| **PHPUnit test** | 100 | **119** |
| **Assertion** | 409 | **467** |
| **Admin CRUD ekranı** | 6 | **7** (+Hosting Server) |
| **Auth güvenlik özellikleri** | 1 (basic) | **4** (basic + reset + 2FA + recovery) |
| **Provisioning otomasyonu** | Yok | **Var** (sipariş → hesap açılışı) |

---

## 🔒 Sıfır regresyon
- `InvoiceService::markPaid` hook'u zincirleme çalışıyor: Referral commission → Provisioning
- Auth token migration idempotent (mevcut kolon varsa skip)
- Auth service `attempt*` metodları backward-incompatible olacak şekilde return type değişti (`bool` → `string`) → **login handler'lar aynı turda güncellendi**, başka çağrı yok

---

## 📸 Screenshot'lar
`docs/screenshots/faz6d/`:
- `forgot-password.png` — Şifremi unuttum formu
- `2fa-setup.png` — 2FA kurulum (QR + secret + onay)
- `admin-servers-list.png` — Sunucu listesi (boş state)
- `admin-server-form.png` — Sunucu ekleme formu (panel seçimi, SSL toggle, kapasite)

---

## 🚧 Bilinen sınırlamalar
- **cPanel/DA/Plesk driver'ların gerçek API çağrıları** — driver iskeletleri hazır ama gerçek WHM/DA/Plesk XML-API test edilmedi (gerçek test hesabı yok)
- **VPS/Dedicated provisioning** — Proxmox/SolusVM entegrasyonu Faz 6e/6f
- **E-posta doğrulama** — signup sonrası zorunlu değil (auth_tokens tablosu hazır, akış eklenmedi)
- **Admin 2FA setup ekranı** yok — sadece login side var. Admin panel Ayarlar > Güvenlik'te eklenmeli
- **Şifre karmaşıklık kuralları** — sadece 8 char min. NIST-uyumlu (zxcvbn) eklenebilir

---

## 🎯 Sıradaki (Faz 6e önerisi)
- E-posta doğrulama (signup sonrası zorunlu link)
- Admin 2FA setup ekranı
- Ticket admin thread view (client-side reply)
- Kupon admin CRUD
- Server usage cron (`hosting:usage-update` — cPanel'den disk/bandwidth çek, `hosting_accounts.disk_usage_mb` güncelle)
- VPS provisioning (Proxmox VE API)
