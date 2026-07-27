# Faz 6e — Auth Tamamlama + Admin Genişleme

**Tarih:** 27 Temmuz 2026
**Odak:** E-posta doğrulama, Admin 2FA setup, Kupon admin CRUD, Ticket iç not & durum yönetimi, Server usage cron

---

## Tamamlananlar

### 1. E-posta Doğrulama ✉️
- `EmailVerificationService` — issue/verify/resend + isVerified check
- `AuthService::registerCustomer` sonrası otomatik verification maili
- `/email-dogrula?token=...` handler
- **Panel dashboard'da uyarı bandı** — doğrulanmamışsa turuncu bant + "Tekrar Gönder" butonu
- Token 3 gün geçerli, tek kullanımlık
- 6 unit test

### 2. Admin 2FA Setup 🔐
- `/admin/guvenlik` — QR + secret + kod onayı + kurtarma kodları
- Sidebar'a `🔐 Güvenlik / 2FA` menüsü
- Login akışı zaten Faz 6d'de hazırdı, artık admin panelden setup edilebilir

### 3. Kupon Admin CRUD 🎟️
- `/admin/kuponlar` — liste + filtre (arama, aktif/pasif), 4 metrik kartı (Toplam, Aktif, Kullanım, Süresi Dolmuş)
- Form: kod (regex validation), isim, tip (yüzde/sabit), değer, para birimi, tarih aralığı, kullanım limitleri, min sepet, aktif toggle
- Durum otomatik: `active` / `inactive` / `expired` / `exhausted` (limit doldu)
- Sidebar'a `🎟️ Kuponlar` menüsü

### 4. Ticket Admin Thread View 🎧
- **İç not** özelliği: müşteriye gitmeyen yönetim notu (sarı arka plan + rozet)
- `TicketService::replies(includeInternal:false)` — müşteri paneli iç notları görmez (güvenlik)
- Yan panelde durum + öncelik dropdown (onchange → auto submit)
- Renk kodlu badge'ler (durum ve öncelik)
- Meta detay kartı (ticket no, oluşturma, son yanıt, kapatma, mesaj sayısı)

### 5. Server Usage Cron ⏱️
- `UsageSyncService` — aktif hesap için driver->getUsage() çağrısı
- `hosting_accounts.disk_usage_mb` + `bandwidth_usage_mb` + `usage_updated_at` günceller
- **Panelde suspended görülürse** local status otomatik senkron edilir
- Cron: `hosting:usage-update` — 6 saatte bir (`0 */6 * * *`)
- **Auth token cleanup** ek cron — günde bir (03:00)

### 6. Cron Genişleme
Şimdi 10 default cron:
- `mail:queue` (dakikada bir)
- `domains:renewal-reminder`, `services:due-check`, `cache:clean`, `logs:cleanup`, `cron:log-cleanup` (günlük)
- `currency:update`, `ratelimit:clean` (saatlik)
- **`hosting:usage-update`** (6 saatte bir) ← yeni
- **`auth:token-cleanup`** (günde bir 03:00) ← yeni

---

## Test kapsaması

| Test | Test # | Assertion |
|---|---|---|
| EmailVerificationTest | 6 | 8 |
| UsageSyncServiceTest | 3 | 9 |

**Toplam kümülatif: 128 test, 484 assertion** (önceki 119, 467)

---

## Sistem istatistikleri

| Metrik | Faz 6d | Faz 6e |
|---|---|---|
| **PHPUnit test** | 119 | **128** |
| **Assertion** | 467 | **484** |
| **Cron sayısı** | 8 default | **10 default** |
| **Admin CRUD ekranı** | 7 | **9** (+Kuponlar, +Güvenlik) |
| **Auth güvenlik** | Basic + Reset + 2FA | + **Email Verify** |

---

## Sıradaki (Faz 6f)

1. Kupon listesinin sepet/checkout ile canlı kontrolü (min_order_total gibi kısıtların uygulanması test)
2. Server usage panel'de gerçek disk/bandwidth grafiği (Chart.js)
3. Referral payout istekleri (bakiyeden banka havalesi çekim)
4. Admin > Ayarlar altında SMTP test butonu
5. Health Center: uptime probe (public sitenin dış görünümü)
