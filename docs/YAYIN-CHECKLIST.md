# 🚀 Ahost Bilişim — Yayın Öncesi Kontrol Listesi

> **v1.0** · Son güncelleme: 2026-07-27
> Bu belge, canlı ahost.web.tr'ye alım öncesi yapılacak KRİTİK kontrolleri sıralar.

---

## 🔴 KRİTİK (Bunlar YAPILMADAN YAYIN YOK)

### 1. Sunucu & Altyapı
- [ ] PHP 8.2+ kurulu (öneri 8.4) + gerekli eklentiler: `mbstring, mysql, curl, xml, gd, zip, intl, opcache`
- [ ] MySQL 8.0 veya MariaDB 10.6+ kurulu
- [ ] Domain **ahost.web.tr** doğru IP'ye A kaydı yapılmış
- [ ] Wildcard SSL sertifikası kuruldu (Let's Encrypt veya ticari)
- [ ] Nginx / Apache document root **/public** klasörüne bakıyor
- [ ] `public/router.php` production'da devre dışı, gerçek nginx/apache rewrite kuralı var

### 2. Uygulama Yapılandırması
- [ ] `install.php` ile temiz kurulum yapıldı → dosya otomatik silindi (`storage/installed.lock` var)
- [ ] `.env` dosyasında sadece DB + APP_KEY + APP_URL bilgileri
- [ ] `APP_ENV=production` ve `APP_DEBUG=false`
- [ ] `APP_KEY` üretildi: `php console key:generate`
- [ ] `storage/`, `public/uploads/` klasörleri writable (`chmod 775`)
- [ ] Cron kuruldu: `* * * * * cd /var/www/ahost && php console cron:run >> /dev/null 2>&1`
- [ ] Cron kayıtları eklendi: `php console cron:install`

### 3. Ödeme Sağlayıcıları (min 1 gerekli)
- [ ] **PayTR** merchant ID + key + salt eklendi (Admin > Ayarlar > Ödeme)
- [ ] **iyzico** API key + secret (opsiyonel)
- [ ] **Papara** merchant + API (opsiyonel)
- [ ] **Shopier** API user + secret (opsiyonel)
- [ ] Test ödeme yapıldı (1 TL) — callback + fatura üretimi doğru
- [ ] Reconciliation cron çalışıyor: `php console payment:reconcile` (15dk'da bir)

### 4. Domain & Hosting Otomasyonu
- [ ] Registrar API key eklendi (DomainNameApi / Namecheap / vs.)
- [ ] En az 1 WHM/cPanel sunucusu tanımlandı (Admin > Hosting & Sunucu)
- [ ] Test domain kaydı + hosting provisioning denendi
- [ ] BTK CSV export tetiklendi ve doğru CSV üretildi

### 5. E-posta (SMTP)
- [ ] SMTP kredensiyalleri Admin > Ayarlar > SMTP'den girildi
- [ ] "Test Maili Gönder" butonu ile ulaştı
- [ ] SPF + DKIM + DMARC DNS kayıtları eklendi
- [ ] Mail queue cron çalışıyor: `php console mail:queue`

### 6. SMS (Opsiyonel ama önerilir)
- [ ] SMS driver seçildi: NetGSM / İletiMerkezi / Twilio (Admin > Ayarlar > SMS)
- [ ] Test SMS gönderildi — `storage/logs/sms.log`'ta görülüyor veya telefona geldi
- [ ] `sms.otp_enabled=1` yapıldıysa SMS ile giriş test edildi

### 7. Güvenlik
- [ ] Admin şifresi değiştirildi (default `AhostOne2026!` KESİNLİKLE kullanılmıyor)
- [ ] Admin 2FA kuruldu (Google Authenticator)
- [ ] reCAPTCHA v2/v3 site+secret key eklendi
- [ ] Rate limit değerleri Admin > Ayarlar > Güvenlik'te set edildi
- [ ] Sentry DSN eklendi (opsiyonel — Ayarlar > Güvenlik)
- [ ] Firewall: sadece 22, 80, 443 açık
- [ ] MySQL sadece localhost'tan erişilebiliyor
- [ ] `install.php` YOKKontrol et: `ls -la public/install.php` → dosya bulunamamalı

### 8. Yedekleme
- [ ] Admin > Yedekleme'den manuel yedek alındı
- [ ] Cron aktif: `php console backup:daily` (günde 1 kez, 03:00)
- [ ] Yedekler S3 / off-site depolama'ya alınıyor (kritik!)
- [ ] Restore testi yapıldı (test DB'ye)

---

## ⚠️ ÖNEMLİ (Yayına Alma İzin Verir Ama Sonra Eklenmeli)

### 9. İçerik
- [ ] Ana sayfa hero + slider metin/görselleri güncel
- [ ] Ürün açıklamaları + fiyatları güncel
- [ ] Legal sayfalar (KVKK, Gizlilik, Mesafeli Satış, İade, Çerez, Kullanım Şartları) firmaya özel düzenlendi
- [ ] Hakkımızda + İletişim sayfaları dolduruldu
- [ ] Firma bilgileri (Admin > Ayarlar > Firma) tam (adres, telefon, vergi no)

### 10. SEO & Analitik
- [ ] Google Search Console DNS/HTML doğrulaması
- [ ] `sitemap.xml` erişilebilir
- [ ] `robots.txt` doğru
- [ ] Google Analytics veya Plausible tracking eklendi
- [ ] OG meta tag'leri ana sayfada + ürünlerde çıkıyor

### 11. Test Turu (Rapor Madde 11)
- [ ] **Ziyaretçi akışı:** Ana sayfa → domain sorgu → sepete ekle → checkout → ödeme
- [ ] **Kayıt + e-posta doğrulama:** Yeni müşteri kaydı, doğrulama maili geliyor
- [ ] **Müşteri girişi:** E-posta + şifre, SMS OTP (aktifse), 2FA (aktifse)
- [ ] **Domain sorgulama:** WHOIS + DNS + öneri doğru dönüyor
- [ ] **18 site aracı:** Hepsi test edildi, gerçek veri veya net "Yok" veriyor
- [ ] **Sepet:** Vergi + kupon + auto-apply + addon + opsiyon (paket opsiyonları) hepsi hesaplanıyor
- [ ] **Fatura:** PDF üretiliyor, mail ekleniyor
- [ ] **Destek talebi:** Aç → cevapla → dosya ekle → çöz
- [ ] **Builder:** Site + Mobile demo → düzenle → ZIP indir
- [ ] **AI Asistan:** Site + admin + müşteri bağlamlarında çalışıyor
- [ ] **Admin panel:** Bütün menüler açılıyor, CRUD işlemleri çalışıyor
- [ ] **Impersonate:** Admin, test müşteri adına giriyor → banner görünüyor → çık çalışıyor

### 12. Performans
- [ ] `opcache` PHP'de açık, `max_accelerated_files >= 20000`
- [ ] Gzip / Brotli aktif
- [ ] Static asset'ler CDN'e verildi (opsiyonel: Cloudflare)
- [ ] MySQL indeksleri kontrol edildi (Admin > Loglar > Yavaş Sorgular)
- [ ] Health check yeşil: `php console health:check`

---

## ✅ v1.0'da Var Olan Özellikler (Karşılaştırma İçin)

- 🛒 **32 modül** aktif
- 🎨 **5 tema** (default, emerald, midnight, royal, sunset)
- 🔧 **18 site aracı** (WHOIS, DNS, SSL, SEO, Site Analiz, Domain Değerleme + 12 diğer)
- 💳 **4 ödeme sağlayıcı** (PayTR, iyzico, Papara, Shopier)
- 🌐 **1 registrar** (DomainNameApi) + Manuel
- 🖥 **3 hosting driver** (cPanel, DirectAdmin, Plesk) + Manuel
- 📱 **4 SMS driver** (NetGSM, İletiMerkezi, Twilio, Log)
- 🤖 **6 AI özelliği** (chat 3-bağlam, site generator, content generator, ticket assistant, AI center, builder AI)
- 🎛 **Paket Opsiyonları** (Lokasyon, Panel, PHP, OS...) — YENİ
- 🔐 **Müşteri Adına Giriş** (Impersonate) — YENİ
- 📱 **SMS/OTP ile Giriş** — YENİ
- 🔌 **Import** (WHMCS, WISECP, Blesta)
- 📊 **13 admin CRUD ekranı**
- 🔒 **10 güvenlik katmanı** (CSRF, RateLimit, 2FA, Encrypter, HMAC, Replay, IP whitelist, PasswordPolicy, Session, Sentry)
- 🗄 **13 cron job**
- 🛠 **14 console komutu**
- 🧪 **164 PHPUnit test / 559 assertion / 0 fail**
- 📦 **73 migration**

---

## 🎯 Yayın Sonrası İlk 24 Saat

- [ ] `storage/logs/`'u izle (error, mail, sms, activity)
- [ ] Cron çalışıyor mu: `php console cron:list`
- [ ] İlk gerçek sipariş yakalandı mı — DB'de `orders` tablosunu kontrol et
- [ ] `admin_activity_logs` doldu mu — admin ekranları gezildi mi
- [ ] Sentry/hata raporlama'da beklenmedik hata var mı
- [ ] Yedek otomatik alındı mı (`storage/backups/`)

---

## 🆘 Acil Geri Alma Planı

```bash
# 1. Bakım modu
cd /var/www/ahost && php console maintenance:on "Kısa süreli bakım"

# 2. Son iyi yedeğe dön
mysql -u ahost ahost_one < /var/backups/ahost-YYYY-MM-DD.sql

# 3. Kod: git ile önceki commit'e dön
git checkout <last-good-commit>

# 4. Bakımı kapat
php console maintenance:off
```
