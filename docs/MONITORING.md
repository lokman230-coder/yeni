# 📡 Monitoring Kurulumu (Ücretsiz araçlarla 5 dk)

## 1. Uptime — UptimeRobot (dışarıdan probe)

**Neden:** Sunucun düşerse 1 dk içinde SMS/mail haberin olur.

1. https://uptimerobot.com kayıt ol (50 monitor free)
2. Add New Monitor → **HTTP(s)**
3. URL: `https://ahost.web.tr/health`
4. Monitoring interval: 5 min
5. Alert contacts: e-posta + telefon
6. Save

**Beklenen cevap:** `{"status":"ok","db":"connected"}`

## 2. Sentry — Hata izleme

**Neden:** Prod'da hata olursa stack trace + user context + count.

1. https://sentry.io kayıt ol (5K event/ay free)
2. Yeni proje: Platform = **PHP**
3. DSN'i kopyala (örn: `https://abc123@o12345.ingest.sentry.io/6789`)
4. Ahost admin > Ayarlar > 🔒 Güvenlik > **Sentry DSN** alanına yapıştır
5. Test için: `php console health:check` çalıştır — hata varsa dashboard'a düşer

**Ahost ExternalReporter** SDK gerektirmez, curl POST ile Sentry uyumlu payload yollar.

## 3. Google Search Console + Analytics

1. https://search.google.com/search-console → **Add Property** → Domain
2. DNS TXT kaydı ekle → onaylat
3. Sitemap gönder: `https://ahost.web.tr/sitemap.xml`
4. Analytics: https://analytics.google.com → GA4 property → measurement ID al
5. Admin > Ayarlar > Genel > **Google Analytics ID** alanına yaz (GA4: G-XXXXXXXX)

## 4. Log Aggregation — Grafana Loki (opsiyonel, k8s için)

```yaml
# helm chart ile:
helm repo add grafana https://grafana.github.io/helm-charts
helm install loki grafana/loki-stack -n monitoring --create-namespace
```

Sonra Grafana dashboard'undan tüm pod loglarını arayabilirsin.

## 5. DB Monitoring — Percona Monitoring (opsiyonel)

MariaDB için Percona PMM önerilir:
```bash
docker run -d --name pmm-server -p 443:443 percona/pmm-server:2
```

## 6. Alerting Kanal Önerileri

| Servis | Ücret | Bildirim |
|---|---|---|
| **UptimeRobot** | 0 | 50 monitor, SMS 10 kredi/ay |
| **Sentry** | 0 | 5K event, email |
| **Grafana Cloud** | 0 | 10K log, 3 user |
| **Better Uptime** | $18/ay | SMS sınırsız, page.status.io |
| **PagerDuty** | $21/user | Escalation policy, on-call |

## Test

Kurulumdan sonra sun sunmadığını doğrula:
```bash
# Uptime
curl -s https://ahost.web.tr/health

# Sentry test (hata fırlat)
kubectl -n ahost exec -it deployment/ahost-app -- php -r 'throw new Exception("Sentry test");'

# Analytics — sayfayı ziyaret et, Realtime > 1 aktif user görmelisin
```
