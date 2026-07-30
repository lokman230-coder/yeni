# Kubernetes Deploy — Ahost Bilişim

Production-grade k8s manifests. Auto-scaling (3-20 pod) + cron + ingress + SSL.

## Ön Koşullar

- Kubernetes 1.24+ cluster (managed önerilir: DigitalOcean, Hetzner, Linode)
- `nginx-ingress-controller` yüklü
- `cert-manager` + `letsencrypt-prod` ClusterIssuer
- Bir `StorageClass` (RWO ve RWX destekli — nfs, longhorn, csi vs.)
- Container registry (Docker Hub / GHCR / GitLab Registry)

## Adımlar

### 1) Image build + push
```bash
docker build -t YOUR_REGISTRY/ahost-one:v1.0 .
docker push YOUR_REGISTRY/ahost-one:v1.0

# Manifest'lerde image referansını güncelle
sed -i 's|ahost-one:latest|YOUR_REGISTRY/ahost-one:v1.0|g' k8s/*.yaml
```

### 2) Secret'ları oluştur
```bash
# APP_KEY üret
APP_KEY=$(php -r 'echo bin2hex(random_bytes(32));')

kubectl create namespace ahost
kubectl -n ahost create secret generic ahost-secrets \
  --from-literal=APP_KEY=$APP_KEY \
  --from-literal=DB_PASSWORD=$(openssl rand -base64 24) \
  --from-literal=DB_ROOT_PASSWORD=$(openssl rand -base64 24)
```

### 3) Manifest'leri uygula
```bash
kubectl apply -f k8s/
```

### 4) İlk kurulum durumunu izle
```bash
kubectl -n ahost get pods -w
kubectl -n ahost logs -f deployment/ahost-app
kubectl -n ahost logs job/ahost-app-migrate  # migration'lar
```

### 5) Admin şifresini değiştir
```bash
kubectl -n ahost exec -it deployment/ahost-app -- \
  php console admin:reset-password admin@ahost.web.tr
```

## Auto-Scaling

`HorizontalPodAutoscaler` CPU %70 / RAM %80'e ulaşınca pod ekler.
- **Min:** 3 pod (yüksek erişilebilirlik)
- **Max:** 20 pod (peak trafik)

İzlemek için: `kubectl -n ahost get hpa -w`

## Backup

`storage/backups/` PVC'ye bağlı — `BackupService` günlük tar.gz üretir.  
**Off-site için:** Ayrı bir CronJob yazıp S3'e sync edin (aşağıdaki `50-backup-s3.yaml.example` örneğine bakın).

## Zero-downtime deploy

```bash
# Yeni image
kubectl -n ahost set image deployment/ahost-app app=YOUR_REGISTRY/ahost-one:v1.1

# Rollout durumu
kubectl -n ahost rollout status deployment/ahost-app

# Sorun varsa geri al
kubectl -n ahost rollout undo deployment/ahost-app
```

## Monitoring önerisi

- **Prometheus + Grafana** — kubectl metrics
- **Loki** — merkezi log
- **Sentry** — hata (admin > ayarlar > güvenlik > Sentry DSN)
- **UptimeRobot** — dışarıdan probe
