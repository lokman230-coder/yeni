# 🚀 Ahost Bilişim — Hızlı Başlangıç

Üç farklı deploy senaryosu. **En basitten en profesyonele.**

---

## 🎯 A) Docker Compose (En kolay — 3 dakikada canlı)

**Kime:** VPS/dedicated sunucu sahiplerine.

```bash
# 1) Sunucuya bağlan
ssh root@ahost.web.tr

# 2) Dosyayı çıkar
cd /var/www
unzip ahost-bilisim.zip
cd ahost-one

# 3) .env hazırla
cp .env.docker.example .env
nano .env
# → APP_URL=https://ahost.web.tr yap
# → DB_PASSWORD ve DB_ROOT_PASSWORD güçlü şifreler koy
# → SESSION_SECURE=true yap (https için)

# 4) APP_KEY üret
docker compose run --rm app php console key:generate
# Çıkan APP_KEY'i .env'e yapıştır

# 5) Ayağa kaldır
docker compose up -d

# 6) 30 sn bekle, sonra kontrol
docker compose ps
docker compose logs -f app | head -30

# 7) Tarayıcıdan aç
# http://ahost.web.tr:8080  → giriş: admin@ahost.web.tr / AhostOne2026!
# ŞİFRENİ HEMEN DEĞİŞTİR (Admin > Profil)
```

**SSL için:** Cloudflare arkasına al (ücretsiz Flexible SSL) veya Traefik/Caddy ekle.

---

## 🎯 B) Klasik LAMP / cPanel Paylaşımlı Hosting (5 dakika)

**Kime:** Kendi VPS'i olmayan, paylaşımlı hosting kullananlara.

```bash
# 1) cPanel'de yeni DB oluştur (Databases > MySQL)
#    → ahost_one veritabanı + ahost kullanıcı + güçlü şifre
#    → Tam yetki ver

# 2) File Manager'dan public_html/ altına zip yükle + çıkart
#    → ahost-one klasörünü public_html/ olarak yeniden adlandır
#    → veya public/ klasörünü document root yap (subdomain ile)

# 3) SSH veya cPanel Terminal'den:
cd ~/public_html
bash deploy.sh
# → PHP versiyon kontrolü, izinler, migration, seed hepsi otomatik

# 4) Cron ekle (cPanel > Cron Jobs)
* * * * * cd /home/USER/public_html && /usr/local/bin/php console cron:run > /dev/null 2>&1

# 5) SSL için Let's Encrypt (cPanel > SSL/TLS Status > Run AutoSSL)
```

Composer yoksa `vendor/` klasörünü lokalde `composer install --no-dev` yapıp yükle.

---

## 🎯 C) Kubernetes (Production — auto-scaling, HA)

**Kime:** Yüksek trafik bekleyenlere, kurumsal deploy için.

```bash
# 1) Image build + registry'e push
docker build -t YOUR_REGISTRY/ahost-one:v1.0 .
docker push YOUR_REGISTRY/ahost-one:v1.0

sed -i 's|ahost-one:latest|YOUR_REGISTRY/ahost-one:v1.0|g' k8s/*.yaml

# 2) Namespace + secret
APP_KEY=$(php -r 'echo bin2hex(random_bytes(32));')
kubectl create namespace ahost
kubectl -n ahost create secret generic ahost-secrets \
  --from-literal=APP_KEY=$APP_KEY \
  --from-literal=DB_PASSWORD=$(openssl rand -base64 24) \
  --from-literal=DB_ROOT_PASSWORD=$(openssl rand -base64 24)

# 3) Uygula
kubectl apply -f k8s/

# 4) İzle
kubectl -n ahost get pods -w

# 5) Domain DNS'ini ingress IP'sine yönlendir
kubectl -n ahost get ingress
```

**Detay:** `k8s/README.md`

---

## ✅ Kurulum Sonrası (3 senaryonun da ortağı)

### 1. Kritik ayarları admin panelden gir
```
1. https://ahost.web.tr/admin/giris
2. Şifre değiştir (Profil)
3. Admin > Ayarlar sırayla:
   ✓ 🏢 Genel      → Site adı, logo, favicon
   ✓ 🏛 Firma      → Adres, vergi no, telefon
   ✓ ✉ SMTP        → Mail sunucusu
   ✓ 💳 Ödeme      → PayTR/iyzico credentials
   ✓ 🧾 E-Fatura   → (opsiyonel)
   ✓ 🤖 AI         → OpenAI API key
   ✓ 📱 SMS        → NetGSM/İletiMerkezi
   ✓ 💾 Backup/S3  → Off-site backup
   ✓ 🔒 Güvenlik   → reCAPTCHA + Sentry DSN
```

### 2. Sunucu ekle (hosting için)
```
Admin > 🖥 Hosting & Sunucu > + Yeni Sunucu
→ cPanel/DA/Plesk seç, IP + API key gir
→ "Bağlantı Testi" butonu ile doğrula
```

### 3. Domain registrar bağla
```
Admin > 🌐 Domain Center > Registrar Ayarları
→ DomainNameApi.com / Namecheap / Godaddy
→ API kredensiyalleri
```

### 4. Monitoring aç
`docs/MONITORING.md` — 5 dk'da UptimeRobot + Sentry.

### 5. Smoke test
```bash
bash tests/smoke.sh https://ahost.web.tr
```

### 6. Backup'ı ilk gün al
```bash
# Docker:
docker compose exec app php console backup:daily
# Klasik:
php console backup:daily
# k8s:
kubectl -n ahost exec deployment/ahost-app -- php console backup:daily
```

---

## 🆘 Sorun Giderme

| Sorun | Çözüm |
|---|---|
| 500 hatası | `storage/logs/app-YYYY-MM-DD.log` bak |
| DB bağlanmıyor | `.env` DB_HOST/USER/PASS kontrol et |
| CSS yüklenmiyor | `public/uploads` yazılabilir mi? `chmod 775 storage public/uploads` |
| Cron çalışmıyor | `crontab -l` ve `php console cron:list` |
| SSL geçmiyor | Cloudflare varsa "Full (strict)" seç; yoksa Let's Encrypt kur |
| Bakım modu takıldı | `rm storage/maintenance.lock` |

---

## 📞 Destek

- Kod dokümanı: `docs/`
- API referansı: `docs/openapi.yaml`
- Health check: `php console health:check`
- Route listesi: `php console routes`
