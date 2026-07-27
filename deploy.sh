#!/usr/bin/env bash
# ================================================================
#  Ahost Bilişim — Tek Komut Deploy Scripti
#  Kullanım:  bash deploy.sh
#  Ne yapar:  Composer install → izinler → DB kontrol → cron → smoke test
# ================================================================
set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  🚀 Ahost Bilişim — Deploy${NC}"
echo -e "${BLUE}════════════════════════════════════════════════${NC}"

# 1) PHP kontrolü
echo -e "\n${YELLOW}[1/7]${NC} PHP kontrolü..."
PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')
if php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);'; then
    echo -e "${GREEN}  ✓ PHP $PHP_VERSION${NC}"
else
    echo -e "${RED}  ✗ PHP 8.2+ gerekli, mevcut: $PHP_VERSION${NC}"
    exit 1
fi

# Gerekli extensions
REQUIRED_EXT=("pdo_mysql" "mbstring" "curl" "gd" "zip" "openssl")
for ext in "${REQUIRED_EXT[@]}"; do
    if ! php -m | grep -qi "^$ext$"; then
        echo -e "${RED}  ✗ PHP extension eksik: $ext${NC}"
        exit 1
    fi
done
echo -e "${GREEN}  ✓ Tüm PHP extensions yüklü${NC}"

# 2) Composer install
echo -e "\n${YELLOW}[2/7]${NC} Composer bağımlılıkları..."
if [ ! -d "vendor" ]; then
    if command -v composer &>/dev/null; then
        composer install --no-dev --optimize-autoloader --no-interaction
    else
        echo -e "${YELLOW}  ⚠ Composer bulunamadı — vendor/ klasörünü manuel yüklemen gerek${NC}"
        echo -e "${YELLOW}    (paylaşımlı hosting kullanıyorsan zip içindeki vendor.zip'i kullan)${NC}"
    fi
else
    echo -e "${GREEN}  ✓ vendor/ zaten var${NC}"
fi

# 3) Dizin izinleri
echo -e "\n${YELLOW}[3/7]${NC} Dizin izinleri..."
mkdir -p storage/{logs,cache,framework,backups,uploads} public/uploads
chmod -R 775 storage public/uploads 2>/dev/null || true
echo -e "${GREEN}  ✓ storage/ ve public/uploads/ writable${NC}"

# 4) .env kontrolü
echo -e "\n${YELLOW}[4/7]${NC} .env dosyası..."
if [ ! -f ".env" ]; then
    if [ -f ".env.production.example" ]; then
        cp .env.production.example .env
        echo -e "${GREEN}  ✓ .env oluşturuldu (.env.production.example'dan)${NC}"
        echo -e "${YELLOW}  ⚠ ÖNEMLİ: .env içindeki DB_* ve APP_URL değerlerini düzenle!${NC}"
    else
        cat > .env << 'EOF'
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ahost.web.tr
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ahost_one
DB_USERNAME=ahost
DB_PASSWORD=CHANGE_ME
EOF
        echo -e "${GREEN}  ✓ .env template oluşturuldu${NC}"
        echo -e "${RED}  ⚠ .env dosyasını AÇIP DÜZENLE!${NC}"
    fi
else
    echo -e "${GREEN}  ✓ .env var${NC}"
fi

# APP_KEY üret
if ! grep -qE "^APP_KEY=.+" .env; then
    KEY=$(php -r "echo bin2hex(random_bytes(32));")
    if grep -q "^APP_KEY=" .env; then
        sed -i.bak "s|^APP_KEY=.*|APP_KEY=$KEY|" .env
    else
        echo "APP_KEY=$KEY" >> .env
    fi
    echo -e "${GREEN}  ✓ APP_KEY üretildi${NC}"
fi

# 5) DB bağlantısı + migration
echo -e "\n${YELLOW}[5/7]${NC} Veritabanı..."
if php console migrate 2>&1 | tail -5 | grep -qi "hata\|error"; then
    echo -e "${RED}  ✗ Migration başarısız — .env'deki DB bilgilerini kontrol et${NC}"
    exit 1
fi
echo -e "${GREEN}  ✓ Migration tamam${NC}"

# İlk kurulumda seed
if [ ! -f "storage/installed.lock" ]; then
    echo -e "${YELLOW}  → İlk kurulum tespit edildi, seed çalıştırılıyor...${NC}"
    php console seed 2>&1 | tail -3
    touch storage/installed.lock
    echo -e "${GREEN}  ✓ Seed tamam${NC}"
fi

# Cron kayıtları
php console cron:install 2>&1 | tail -2 | head -1

# 6) install.php sil
echo -e "\n${YELLOW}[6/7]${NC} Güvenlik..."
if [ -f "public/install.php" ] && [ -f "storage/installed.lock" ]; then
    rm -f public/install.php
    echo -e "${GREEN}  ✓ public/install.php silindi (kurulum tamamlandı)${NC}"
else
    echo -e "${GREEN}  ✓ install.php mevcut değil${NC}"
fi

# 7) Health check
echo -e "\n${YELLOW}[7/7]${NC} Sağlık kontrolü..."
php console health:check 2>&1 | tail -22

echo ""
echo -e "${GREEN}════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✅ DEPLOY TAMAM${NC}"
echo -e "${GREEN}════════════════════════════════════════════════${NC}"
echo ""
echo -e "  ${BLUE}Yapılacaklar:${NC}"
echo -e "  1. ${YELLOW}Cron job ekle:${NC}"
echo -e "     ${BLUE}* * * * * cd $(pwd) && php console cron:run > /dev/null 2>&1${NC}"
echo ""
echo -e "  2. ${YELLOW}Admin panele gir:${NC}  https://senin-domainin.com/admin/giris"
echo -e "     E-posta: admin@ahost.web.tr"
echo -e "     Şifre:   AhostOne2026!  ${RED}(HEMEN DEĞİŞTİR!)${NC}"
echo ""
echo -e "  3. ${YELLOW}Admin > Ayarlar'dan doldur:${NC}"
echo -e "     • 💳 Ödeme (PayTR / iyzico / Papara / Shopier)"
echo -e "     • ✉ SMTP"
echo -e "     • 📱 SMS (opsiyonel)"
echo -e "     • 🔒 Güvenlik (reCAPTCHA)"
echo -e "     • 🏛 Firma (adres, vergi no)"
echo ""
echo -e "  4. ${YELLOW}Hosting sunucusu ekle:${NC}"
echo -e "     Admin > Hosting & Sunucu > + Yeni Sunucu"
echo ""
echo -e "  5. ${YELLOW}Smoke test:${NC}"
echo -e "     ${BLUE}bash tests/smoke.sh https://senin-domainin.com${NC}"
echo ""
