# Ahost Bilişim — Deployment Guide (v1.0.0)

## Sunucu Gereksinimleri
- **PHP:** 8.2+ (test edildi: 8.4.23)
- **DB:** MariaDB 10.11+ veya MySQL 8+
- **Web sunucu:** Nginx (önerilir) veya Apache
- **Extensions:** pdo_mysql, mbstring, curl, openssl, gd, zip, json, dom, xml
- **Opsiyonel:** soap (e-fatura için), redis (session), imagick (upload)
- **Composer:** 2.x (kurulum sonrası root'ta çalıştırılır)
- **Cron:** dakikada bir çalışan job

## Hızlı Kurulum (3 Yol)

### A) Otomatik Sihirbaz (Önerilen)
```bash
git clone <repo> /var/www/ahost && cd /var/www/ahost
composer install --no-dev --optimize-autoloader
cp .env.production.example .env
php console key:generate
# storage/installed.lock DOSYASI OLMASIN (yeni kurulum)
chown -R www-data:www-data storage
```
Sonra tarayıcıdan `https://sitem.com` → otomatik `/kurulum`'a yönlendirir → 5 adım sihirbaz:
1. Sistem gereksinimleri kontrolü
2. Veritabanı bağlantısı
3. Migration çalıştırma
4. Süper admin oluşturma
5. Site bilgileri + SMTP

### B) Docker Compose
```bash
docker compose up -d --build
# Otomatik: PHP-FPM, Nginx, MariaDB, cron, migration
```

### C) Manuel
```bash
composer install --no-dev
cp .env.production.example .env && nano .env  # DB bilgilerini gir
php console migrate
php console seed
php console cron:install
touch storage/installed.lock
```

## Cron Kurulumu
```
* * * * * cd /var/www/ahost && /usr/bin/php console cron:run > /dev/null 2>&1
```

10 varsayılan cron çalışır:
- `mail:queue` (dakikada)
- `currency:update` (saatlik — TCMB'den)
- `ratelimit:clean` (saatlik)
- `domains:renewal-reminder` (günlük)
- `services:due-check` (günlük)
- `cache:clean` (günlük)
- `logs:cleanup` (günlük)
- `cron:log-cleanup` (günlük)
- `hosting:usage-update` (6 saatte bir)
- `auth:token-cleanup` (günde bir, 03:00)

## Nginx Örnek Config
```nginx
server {
    listen 443 ssl http2;
    server_name ahost.web.tr;

    root /var/www/ahost/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/ahost.web.tr/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/ahost.web.tr/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ /\.(env|git) { deny all; }
    client_max_body_size 25M;
}

server {
    listen 80;
    server_name ahost.web.tr;
    return 301 https://$server_name$request_uri;
}
```

## .env Ayar Kontrol Listesi (Prod)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ahost.web.tr
APP_KEY=<php console key:generate ile üretilir>

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ahost
DB_USERNAME=ahost
DB_PASSWORD=<güçlü şifre>

MAIL_HOST=smtp.gmail.com    # veya SES/Mailgun
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=noreply@ahost.web.tr
MAIL_FROM_NAME="Ahost Bilişim"
MAIL_ENCRYPTION=tls

# Ödeme sağlayıcıları
PAYTR_MERCHANT_ID=
PAYTR_MERCHANT_KEY=
PAYTR_MERCHANT_SALT=
PAYTR_TEST_MODE=false

IYZICO_API_KEY=
IYZICO_SECRET_KEY=
IYZICO_SANDBOX=false

PAPARA_API_KEY=
PAPARA_SANDBOX=false

# AI (opsiyonel — yoksa HeuristicProvider fallback)
AI_API_KEY=

# Uyumsoft e-Fatura
UYUMSOFT_USERNAME=
UYUMSOFT_PASSWORD=
UYUMSOFT_TEST_MODE=false
```

## Post-deploy Checklist
- [ ] `/kurulum/tamamlandi` → Admin panele git → Ayarlar > SMTP > Test Mail Gönder ✓
- [ ] Kur Yönetimi > TCMB'den Şimdi Çek → gerçek kurlar geliyor ✓
- [ ] Health Center > Uptime Probe → domain 200 dönüyor + SSL geçerli ✓
- [ ] Ödeme sağlayıcı bilgilerini gir (test siparişi ile doğrula)
- [ ] Referans programı ayarlarını gözden geçir (%komisyon, min payout)
- [ ] Admin > Güvenlik > 2FA aktifleştir
- [ ] Cron'un çalıştığını doğrula: Admin > Loglar > Cron
- [ ] En az bir hosting sunucusu ekle (Admin > Hosting & Sunucu)
- [ ] Domain WHOIS test et: Admin > Domain Center > "WHOIS'ten Yenile"

## Yedekleme

```bash
# Günlük DB backup
mysqldump ahost | gzip > /backup/ahost-$(date +%F).sql.gz

# Storage backup (encryptedler dahil)
tar czf /backup/storage-$(date +%F).tgz storage/
```

## Güvenlik Notları
- `storage/`, `.env`, `installed.lock` **asla** public'e maruz kalmasın (Nginx `location /` kural setinde)
- Admin girişi için 2FA zorunlu (Faz 6d)
- Rate limit middleware aktif (login/register endpoint'lerinde)
- CSRF token tüm POST'larda zorunlu
- Encrypter (AES-256-GCM) ile: hosting şifreleri, API keyler, 2FA secretler, recovery kodlar

## Sorun Giderme
- **500 hatası:** `storage/logs/app-YYYY-MM-DD.log` dosyasına bak
- **Kurulum sonrası site açılmıyor:** `storage/installed.lock` var mı kontrol et
- **Cron çalışmıyor:** `php console cron:run --verbose` ile manuel dene
- **Mail gitmiyor:** Admin > Loglar > Mail sekmesinde hata mesajı görün
- **PDF Türkçe karakter bozuk:** DejaVu Sans varsayılan, hâlâ sorun varsa `php-gd` kurulu olduğundan emin ol
