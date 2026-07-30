# Ahost One V2 — AI Handoff / Eksik İşler Raporu

## 1. Proje özeti

Ahost One; PHP 8.2+, MySQL/MariaDB, MVC/modüler yapı, public site, admin paneli, müşteri paneli, ürün/sepet/fatura, domain, hosting, builder, AI ve marketplace hedefleyen bir platformdur.

Kaynak repository:

```text
https://github.com/lokman230-coder/yeni
```

Kurulum hedefi:

```text
cPanel public_html kökü
Root install.php
config/installation.php
Fiziksel admin/ klasörü
```

## 2. Bu çalışma kopyasında yapılanlar

- cPanel public_html root yapısı düzenlendi.
- Root index.php ve install.php kullanıldı.
- .htaccess ve .user.ini eklendi.
- Legacy install/index.php kaldırıldı.
- Fresh install migration/seed akışı düzenlendi.
- Admin oluşturma kolon/rol uyumsuzlukları düzeltildi.
- .env zorunluluğu kaldırılıp config/installation.php fallback eklendi.
- Menü Yönetimi temel CRUD eklendi.
- Modül Merkezi temel aktif/pasif eklendi.
- Tema Blokları yönetimi eklendi.
- Sayfalar CRUD eklendi.
- Duyurular CRUD ve public liste eklendi.
- Finans dashboard, tarih filtresi ve CSV export eklendi.
- Çerez Analizi dashboard’u eklendi.
- Public header/footer menüleri ayarlara bağlandı.
- Footer firma telefon/e-posta/adres ayarları dinamik yapıldı.
- Admin Site Araçları ekranı eklendi.
- Admin Site/Mobile Builder proje listeleme, arama, filtre ve detay ekranları eklendi.
- Admin Mobile Build işleri ekranı, retry ve health kontrolleri eklendi.
- StubController ve placeholder admin route yapısı kaldırıldı.
- WHMCS/WISECP/Blesta import altyapısı genişletildi.
- WISECP ek paket/özel alan tablo varyasyonları için esnek okuma eklendi.
- Blesta registrar taraması eklendi.
- Import period/options normalization eklendi.
- OpenAI, Gemini, Claude, DeepSeek, Mistral ve Heuristic provider altyapısı eklendi.
- AI görev bazlı provider seçimi eklendi.
- Admin AI Asistan ekranı eklendi.
- Mobile Build Worker Docker yapısı eklendi.
- Android SDK/Flutter ilk başlangıç indirme scriptleri eklendi.
- APK/AAB build scriptleri eklendi.
- Android signing scripti eklendi.
- Firebase/package name uygulama scripti eklendi.
- GitHub Actions mobile-build workflow’u eklendi.
- GitHub Actions dispatch/status/artifact servis altyapısı eklendi.
- mobile_build_jobs tablosu eklendi.
- Payment sonrası Mobile Build hook eklendi.
- PWA export ve service worker eklendi.
- APK/AAB admin aç/kapat ayarları eklendi.
- Müşteri/admin Mobile Build geçmişi eklendi.
- Build status endpoint’leri ve cron sync eklendi.
- Artifact storage ve 30 günlük cleanup cron’u eklendi.
- QA audit scripti eklendi.
- JavaScript ve Mobile Worker Bash syntax kontrolleri geçti.

## 3. Kalan kritik işler

### A. Mobile Builder

- MobileExportService ve MobileBuildService tamamen tek unified servis altında birleştirilmeli.
- Legacy builder_export_jobs ile mobile_build_jobs ayrımı nihai hale getirilmeli.
- GitHub workflow run ID otomatik olarak doğru mobile job ile eşleştirilmeli.
- GitHub Actions status polling gerçek run ID ile tamamlanmalı.
- Artifact ZIP’i Ahost storage’a alındıktan sonra APK/AAB içinden doğru dosya çıkarılmalı.
- Müşteriye APK/AAB gerçek dosya indirme testi yapılmalı.
- Firebase google-services.json gerçek Flutter/Android build’e bağlanmalı.
- Keystore/signing değerleri Gradle signingConfig’e bağlanmalı.
- Push notification modülü tamamlanmalı.
- Mobile Builder template gallery ve telefon mockup canlı preview tamamlanmalı.
- Mobile AI JSON schema/orchestrator tamamlanmalı.
- Radyo uygulaması, e-ticaret uygulaması, haber, randevu ve eğitim şablonları gerçek bloklara bağlanmalı.
- PWA manifest ikonları, install prompt ve offline cache iyileştirilmeli.
- Build iptal ve detaylı log ekranı eklenmeli.

### B. Site Builder

- AI ile prompttan JSON site planı üretimi.
- JSON schema validation.
- JSON planını BlockRegistry tree’ye dönüştürme.
- Canlı canvas güncellemesi.
- Autosave.
- Undo/redo.
- Revision geçmişi.
- Template import/export.
- Global renk/font/breakpoint sistemi.
- Form cevaplarının admin paneline aktarımı.
- Özel domain yayınlama.

### C. AI

- Admin AI gerçek function calling ve onay mekanizması.
- Public canlı destek widget’ı.
- Public AI → ürün, domain, sepet, ödeme ve ticket yönlendirmesi.
- Müşteri AI → hizmet/fatura/domain/ticket işlemlerinin uçtan uca tamamlanması.
- Provider test bağlantısı.
- Provider fallback.
- Token/maliyet raporu.
- Görsel/logo/splash üretim API’si.
- Mobile Builder özel AI komutları.

### D. Marketplace

- Dijital ürün/script/tema/plugin/source satışı.
- Güvenli download tokenı.
- Ürün sürüm ve changelog.
- Lisans anahtarı.
- Domain/installation limitli aktivasyon.
- Lisans iptali/deaktivasyon.
- Satıcı komisyon ve payout.
- İade/anlaşmazlık.
- Yorum/soru-cevap.
- Ürün güncelleme bildirimi.

### E. Veri aktarımı

- WHMCS gerçek DB testleri.
- WISECP sürüm bazlı gerçek DB testleri.
- Blesta gerçek registrar şema testleri.
- Import preview.
- Import rollback.
- Import validation report.
- Büyük DB performans testi.
- Şifre hash uyumluluğu testleri.

### F. Canlı entegrasyonlar

- PayTR başarılı/başarısız/callback.
- Registrar kayıt/transfer/yenileme/EPP/DNS.
- cPanel/DirectAdmin/Plesk provisioning.
- SMTP/SMS.
- Gemini/Claude/OpenAI/DeepSeek/Mistral gerçek API testleri.
- Uyumsoft/GİB e-Fatura/E-Arşiv.
- S3/Wasabi/Backblaze off-site backup.
- Sentry.
- UptimeRobot.
- reCAPTCHA.

### G. QA ve production

- PHP syntax: cPanel PHP 8.2+ üzerinde çalıştırılmalı.
- PHP warning/deprecated/fatal log taraması.
- Tüm route/404/500 taraması.
- Admin tüm buton/form testi.
- Müşteri tüm buton/form testi.
- CSS responsive testi.
- Browser console testi.
- Dosya upload güvenlik testi.
- CSRF/RBAC bypass testi.
- Fresh install testi.
- Existing update testi.
- Cron/queue/backup restore testi.
- Gerçek ödeme/domain/hosting testleri.
- Final cPanel ZIP.

## 4. Başka bir AI’a verilecek çalışma talimatı

Önce şu dosyayı oku:

```text
planning/AI-HANDOFF-REPORT.md
planning/USER-REQUESTS.md
```

Kurallar:

1. Yarım işi yeniden yazma; önce mevcut controller/service/route/migration’ı incele.
2. Stub veya placeholder bırakma.
3. CSS/JS ile başka modülü ezme.
4. API key, DB şifresi, keystore veya Firebase secret’ı koda yazma.
5. Her yeni migration idempotent olmalı.
6. Her POST işleminde CSRF ve RBAC kontrolü olmalı.
7. Gerçek entegrasyon yoksa mock/fallback açıkça belirtilmeli.
8. Mobile Builder’da APK/AAB kapalıysa public ve müşteri ekranında görünmemeli.
9. VPS yoksa PWA/source fallback çalışmalı.
10. Final ZIP yalnızca PHP, JS, route, migration ve gerçek staging testleri tamamlandıktan sonra oluşturulmalı.

## 5. Önemli gerçek durum

Bu paket development/staging snapshot’tır. Production-ready final paket değildir. PHP runtime, Android SDK, Flutter, gerçek API hesapları ve cPanel staging üzerinde ayrıca doğrulama gerektirir.
