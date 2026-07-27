#!/bin/bash
set -e

cd /var/www/html

# .env yoksa .env.example'dan oluştur
if [ ! -f .env ]; then
    echo "→ .env oluşturuluyor..."
    cp .env.example .env
    php console key:generate
fi

# DB hazırsa migration çalıştır
if [ -n "$DB_HOST" ]; then
    echo "→ DB bekleniyor: $DB_HOST"
    for i in {1..30}; do
        if mysqladmin ping -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" --silent 2>/dev/null; then
            echo "✓ DB hazır"
            break
        fi
        sleep 1
    done

    echo "→ Migration çalıştırılıyor..."
    php console migrate 2>&1 || true

    if [ "$AUTO_SEED" = "true" ]; then
        echo "→ Seeder çalıştırılıyor..."
        php console seed 2>&1 || true
    fi

    echo "→ Cron zamanlamaları kuruluyor..."
    php console cron:install 2>&1 || true
fi

# storage izinleri
chown -R www-data:www-data storage
chmod -R 775 storage

echo "✓ Ahost Bilişim hazır"
exec "$@"
