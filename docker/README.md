# Docker Deployment

Ahost Bilişim'i tek komutla Docker ile ayağa kaldırın.

## Hızlı Başlangıç

```bash
# Build & up
docker compose up -d --build

# Loglar
docker compose logs -f app

# Durdur
docker compose down

# DB verisini de silmek için
docker compose down -v
```

Tarayıcı: **http://localhost:8080**

- Admin: `admin@ahost.web.tr` / `AhostOne2026!`
- Test müşteri: `test@ahost.web.tr` / `Test1234!`

## Ne İçerir?

- **app** — PHP 8.2-FPM Alpine + Nginx + Supervisor + Cron (tek container)
- **db** — MariaDB 10.11
- **redis** — Redis 7 (opsiyonel cache)

## Production Deployment

`.env` içindeki secret değerleri **mutlaka değiştirin**:
```yaml
DB_PASSWORD: kendi_güçlü_şifreniz
MARIADB_ROOT_PASSWORD: kendi_root_şifreniz
SESSION_SECURE: "true"    # HTTPS zorunlu
```

SSL için Traefik veya Nginx Proxy Manager ile reverse proxy önerilir.

## Kubernetes (Faz 6b)

`k8s/` klasörüne manifest'ler eklenecektir:
- `deployment.yaml`
- `service.yaml`
- `ingress.yaml` (Traefik / nginx-ingress)
- `secrets.yaml` (encrypted)
- `configmap.yaml`
- `cronjob.yaml` (mail queue + health check)

## Yedekleme

Container içinden:
```bash
docker exec ahost-bilisim-db mysqldump -u ahost -p ahost_one > backup.sql
docker exec ahost-bilisim-app tar -czf storage.tar.gz storage/
```
