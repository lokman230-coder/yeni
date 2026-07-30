# Ahost Mobile Build Worker

İlk container başlangıcında gerekli Android SDK command-line tools, platform, build-tools ve Flutter SDK otomatik indirilir. İndirmeler Docker volume'larında tutulur; sonraki başlatmalarda tekrar indirilmez.

## Kurulum

```bash
cp docker-compose.yml docker-compose.local.yml
# docker-compose.yml içindeki WORKER_API_KEY değerini değiştir
docker compose up -d --build
curl http://127.0.0.1:8090/health
```

İlk açılışta SDK indirmeleri büyük olabilir. Worker'ı public internete doğrudan açmayın; reverse proxy veya private network ve güçlü API key kullanın.

## İmzalama

Gerçek Android imzalama bilgilerini GitHub'a koymayın. Keystore'u worker'ın `storage/` volume'una yükleyin ve compose environment değerlerini değiştirin:

```bash
KEYSTORE_PATH=/opt/ahost-worker/storage/release.keystore
KEY_ALIAS=release
KEYSTORE_PASSWORD=strong-secret
KEY_PASSWORD=strong-secret
./scripts/sign-android.sh /opt/ahost-worker/output/app-release.apk
```

## API

```text
GET /health
POST /build        X-Worker-Key başlığı ile
GET /status?id=...
```

Bu worker'ın ilk sürümü queue kabulü ve ortam sağlık kontrolünü sağlar. Gerçek APK/AAB pipeline'ı, Mobile Builder'ın gönderdiği proje manifestine göre bir sonraki build executor aşamasında çalıştırılır.
