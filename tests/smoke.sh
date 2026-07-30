#!/usr/bin/env bash
# Ahost Bilişim — Yayın öncesi hızlı smoke testi.
# Kullanım: bash tests/smoke.sh [BASE_URL] [ADMIN_EMAIL] [ADMIN_PASS]

set -e

BASE="${1:-http://127.0.0.1:8092}"
EMAIL="${2:-admin@ahost.web.tr}"
PASS="${3:-AhostOne2026!}"
COOK=$(mktemp)
TMP=$(mktemp)

echo "═══════════════════════════════════════════════"
echo "  🧪 Ahost Bilişim — Smoke Test"
echo "═══════════════════════════════════════════════"
echo "  URL:   $BASE"
echo "  Admin: $EMAIL"
echo "═══════════════════════════════════════════════"

# 1) Login
curl -s -c "$COOK" "$BASE/admin/giris" -o "$TMP" || { echo "❌ Login sayfası açılmadı"; exit 1; }
CSRF=$(grep -oE 'csrf-token" content="[^"]+"' "$TMP" | head -1 | sed 's/.*content="//;s/"$//')
if [ -z "$CSRF" ]; then
    echo "❌ CSRF token bulunamadı"; exit 1
fi
LOGIN_CODE=$(curl -s -b "$COOK" -c "$COOK" -X POST \
  --data-urlencode "_csrf=$CSRF" \
  --data-urlencode "email=$EMAIL" \
  --data-urlencode "password=$PASS" \
  "$BASE/admin/giris" -o /dev/null -w "%{http_code}")
if [ "$LOGIN_CODE" != "302" ]; then
    echo "❌ Login başarısız ($LOGIN_CODE)"; exit 1
fi
echo "✓ Admin login (302)"

# 2) Kritik sayfalar
declare -a URLS=(
    "/"
    "/blog"
    "/site-araclari"
    "/marketplace"
    "/domain"
    "/hosting"
    "/giris"
    "/kayit"
    "/sepet"
    "/admin/"
    "/admin/urun-merkezi"
    "/admin/paket-opsiyonlari"
    "/admin/musteriler"
    "/admin/veri-aktarimi"
    "/admin/ayarlar"
    "/admin/kuponlar"
    "/admin/yedekleme"
    "/admin/ai-center"
    "/admin/domain-center"
    "/admin/hosting-sunucu"
    "/admin/destek-merkezi"
    "/admin/blog"
)

ok=0; fail=0; failed_urls=""
for url in "${URLS[@]}"; do
    code=$(curl -s -o /dev/null -b "$COOK" "$BASE$url" -w "%{http_code}")
    if [ "$code" = "200" ] || [ "$code" = "302" ] || [ "$code" = "301" ]; then
        ok=$((ok+1))
        printf "  ✓ %-40s %s\n" "$url" "$code"
    else
        fail=$((fail+1))
        failed_urls="$failed_urls $url($code)"
        printf "  ✗ %-40s %s\n" "$url" "$code"
    fi
done

# 3) Health check
echo ""
echo "═══════════════════════════════════════════════"
echo "  🏥 Health Check"
echo "═══════════════════════════════════════════════"
php console health:check 2>&1 | tail -25

rm -f "$COOK" "$TMP"

echo ""
echo "═══════════════════════════════════════════════"
echo "  📊 SONUÇ"
echo "═══════════════════════════════════════════════"
echo "  ✓ Başarılı: $ok"
echo "  ✗ Başarısız: $fail"
if [ "$fail" -gt 0 ]; then
    echo "  Başarısız URL'ler:$failed_urls"
    exit 1
fi
echo ""
echo "  🎉 Yayına hazır!"
exit 0
