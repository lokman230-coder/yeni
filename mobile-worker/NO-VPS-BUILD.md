# VPS olmadan APK/AAB build

Ahost Mobile Builder, VPS olmadan GitHub Actions build worker kullanabilir. `.github/workflows/mobile-build.yml` workflow'u Flutter, Java 17, Android SDK ve Gradle ortamını GitHub'ın geçici runner'ında kurar.

## GitHub Secrets

Repository Settings > Secrets and variables > Actions:

- `FIREBASE_ANDROID_JSON`: `google-services.json` dosyasının base64 içeriği
- `ANDROID_KEYSTORE_BASE64`: release keystore base64 içeriği
- `ANDROID_KEY_ALIAS`
- `ANDROID_KEYSTORE_PASSWORD`
- `ANDROID_KEY_PASSWORD`

Şifreler ve keystore dosyaları koda yazılmamalıdır.

## Kullanım

Actions > Ahost Mobile Build > Run workflow seçilir. `apk` veya `aab` seçilir. Çıktı Actions Artifacts bölümünden indirilir.

cPanel uygulaması bu işlemi GitHub Actions API ile tetikleyebilir; API token ve repository bilgileri admin ayarlarında şifreli tutulmalıdır.
