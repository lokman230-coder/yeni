# Kullanıcı İstekleri ve Ürün Backlog

Bu dosya, kullanıcı tarafından konuşma sırasında eklenen yeni isteklerin kaybolmaması için tutulur. Mevcut geliştirme yarım bırakılmadan maddeler sırayla tamamlanır.

## Uygulama Sırası

1. Yarım kalan mevcut modüller ve route/controller bağlantıları
2. Mevcut ürün, sepet, ödeme, fatura ve import akışları
3. Admin ve müşteri paneli eksikleri
4. CSS/JS/responsive ve buton-form QA
5. AI ve Builder ileri özellikleri
6. Raporla eklenen entegrasyon ve production özellikleri
7. Final fresh install ve cPanel production QA

## Builder Referansları ve Şablon Üretimi

- [ ] Wix benzeri görsel site şablon kategorileri ve özgün tasarımlar
- [ ] Squarespace benzeri premium kurumsal/portfolyo/landing şablonları
- [ ] Jotform benzeri form, başvuru, rezervasyon, bilet, ödeme ve tablo odaklı site şablonları
- [ ] Andromo benzeri kodsuz mobil uygulama şablonları
- [ ] FlutterFlow benzeri mobil ekran, navigation ve veri modülü şablonları
- [ ] Referans ürünlerin marka/asset/kodunu kopyalamadan özgün tasarım ve blok sistemi
- [ ] Site şablonlarını dil seçenekleriyle sunma
- [ ] Şablon metadata: dil, kategori, sektör, renk, sayfa sayısı, modüller
- [ ] Şablonların Türkçe/İngilizce/Arapça ve seçili dillerde içerik üretmesi
- [ ] Mobile Builder şablonları için telefon mockup ve canlı önizleme
- [ ] Site Builder şablonları için desktop/tablet/mobile canlı önizleme
- [ ] Şablon seçince proje JSON ağacını otomatik oluşturma
- [ ] Şablon değişince mevcut içeriği koru/yenile seçeneği
- [ ] Jotform tarzı form + ödeme + tablo + bildirim modülleri
- [ ] Andromo tarzı radyo, haber, podcast, içerik ve link uygulamaları
- [ ] FlutterFlow tarzı API, veri listesi, login, navigation ve action blokları

## Builder Şablon ve Canlı Önizleme

- [ ] Site Builder şablon galerisinde görsel kart ve kategori filtreleri
- [ ] Mobile Builder şablon galerisinde telefon mockup önizlemesi
- [ ] Şablon seçmeden önce canlı önizleme
- [ ] Şablon seçilince proje içeriğini güvenli şekilde oluşturma
- [ ] Masaüstü/tablet/mobil önizleme geçişi
- [ ] Site Builder değişikliklerinin iframe/canlı canvas'a anında yansıması
- [ ] Mobile Builder değişikliklerinin telefon mockup'a anında yansıması
- [ ] Renk, font, logo, başlık ve metin değişikliklerinde canlı güncelleme
- [ ] Menü, blok, sayfa, slider ve modül değişikliklerinde canlı güncelleme
- [ ] Undo/redo ve revision ile canlı önizleme uyumu
- [ ] Autosave ve preview state ayrımı
- [ ] Önizlemeden geri dönme ve şablon değişikliğinde kullanıcı onayı
- [ ] Site Builder şablonları: hosting, ajans, landing, radyo, e-ticaret, restoran, klinik, eğitim, portfolyo, SaaS, yerel işletme
- [ ] Mobile Builder şablonları: radyo, kurumsal, restoran, e-ticaret, haber, eğitim, spor salonu, randevu

## AI Yönetimi ve Yardımcılar

- [ ] Admin AI Center'da tüm provider API key alanlarını yönet
- [ ] OpenAI API key/model/aktiflik/test bağlantısı
- [ ] Gemini API key/model/aktiflik/test bağlantısı
- [ ] Claude API key/model/aktiflik/test bağlantısı
- [ ] DeepSeek API key/model/aktiflik/test bağlantısı
- [ ] Mistral API key/model/aktiflik/test bağlantısı
- [ ] API key maskeli gösterim ve şifreli saklama
- [ ] Provider bazlı görev ve model yönlendirme
- [ ] Admin AI: doğal dille admin görevleri, onay mekanizması ve audit log
- [ ] Public AI canlı destek widget'ı
- [ ] Public canlı destek: ürün, domain, sepet, ödeme ve bilgi bankası yönlendirmesi
- [ ] Public AI'dan ticket/canlı destek başlangıcı
- [ ] Müşteri AI sohbeti: hizmet, fatura, domain, ticket işlemleri
- [ ] AI hata durumunda provider fallback ve kullanıcıya anlaşılır mesaj
- [ ] AI token/maliyet/kullanım raporları

## AI ve Builder

- [ ] Admin AI yardımcısını production function/tool calling seviyesine çıkar
- [ ] Public AI yardımcısını ürün ve yönlendirme akışlarıyla tamamla
- [ ] Müşteri AI: hizmet, fatura, domain, ticket işlemlerini güvenli onay akışıyla tamamla
- [ ] Site Builder AI: tasarım, blok, renk, metin, sayfa ve revizyon akışlarını tamamla
- [ ] Mobile Builder AI: Gemini tabanlı mobil ekran, navigation, player, push ve e-ticaret modülleri
- [ ] AI provider görev yönlendirme ve otomatik fallback
- [ ] AI kullanım limiti ve maliyet raporu

## Admin ve müşteri panelleri

- [ ] WHMCS/WiseCP tarzı admin panel akışlarını tamamla
- [ ] Müşteri paneli hizmet/domain/fatura/ticket detaylarını tamamla
- [ ] Buton, form, route ve yetki taraması
- [ ] Responsive mobil panel kontrolü

## İçerik ve yönetim

- [x] Menü Yönetimi temel CRUD
- [x] Modül Merkezi temel aktif/pasif
- [x] Tema Blokları temel yönetim
- [x] Sayfalar CRUD
- [x] Duyurular CRUD ve public liste
- [ ] Footer/header ayarlarının tümünü admin ayarlarına bağla

## Veri aktarımı

- [ ] WHMCS aktarımını gerçek örnek veritabanıyla doğrula
- [ ] WISECP ek paket aktarımını sürüm bazlı tamamla
- [ ] WISECP özel alan aktarımını tamamla
- [ ] Blesta registrar aktarımını gerçek şema ile doğrula
- [ ] Import önizleme, rollback ve doğrulama raporu

## Entegrasyonlar

- [ ] PayTR test/callback/live akışı
- [ ] Registrar API testleri
- [ ] cPanel/DirectAdmin/Plesk otomasyonu
- [ ] SMTP/SMS testleri
- [ ] Gemini/Claude/DeepSeek/Mistral gerçek API testleri

## Marketplace — Dijital Ürün ve Lisans Pazarı

- [ ] Script, tema, plugin, SaaS, mobil uygulama ve kaynak kod ürünü satışı
- [ ] Dijital ürün ZIP/file upload ve güvenli teslim
- [ ] Ürün sürüm yönetimi
- [ ] Güncelleme dosyası yayınlama
- [ ] Changelog yönetimi
- [ ] Ürün dokümantasyonu ve kurulum rehberi
- [ ] Demo/preview URL
- [ ] Ürün lisans tipi: kişisel, ticari, sınırsız, abonelik
- [ ] Lisans anahtarı üretimi
- [ ] Domain/installation limitli lisans doğrulama
- [ ] Lisans aktivasyon/deaktivasyon
- [ ] Güncelleme erişim süresi
- [ ] Satıcı paneli ürün yönetimi
- [ ] Satıcı gelir ve komisyon raporu
- [ ] Admin ürün onayı/moderasyonu
- [ ] Dijital ürün iade ve anlaşmazlık akışı
- [ ] Güvenli indirme tokenı ve süreli download linki
- [ ] Ürün puanlama ve yorum
- [ ] Soru-cevap alanı
- [ ] Favoriler ve takip listesi
- [ ] Kategori/etiket/teknoloji filtreleri
- [ ] Arama ve sıralama
- [ ] Ürün karşılaştırma
- [ ] Kupon ve kampanya desteği
- [ ] Satıcı doğrulama
- [ ] Telif/DMCA bildirim süreci
- [ ] Ürün güncelleme bildirimleri
- [ ] Satın alınan ürünlerim ve indirme geçmişi

## Dokümantasyonda olup eksik kalanlar

- [ ] Sentry gerçek hata izleme entegrasyonu
- [ ] S3/Wasabi/Backblaze off-site backup entegrasyonu
- [ ] UptimeRobot/harici monitoring entegrasyonu
- [ ] reCAPTCHA login/register/ticket/domain formlarına bağlama
- [ ] Uyumsoft/GİB e-Fatura canlı akışı
- [ ] E-Arşiv, iptal, yeniden gönderme ve fatura durum senkronizasyonu
- [ ] Mobile Builder gerçek APK/AAB build sistemi
- [ ] Android signing/keystore yönetimi
- [ ] Push notification build/export süreci
- [ ] Play Store paket hazırlama akışı
- [ ] README/QUICKSTART/DEPLOYMENT dokümanlarını gerçek duruma göre güncelleme
- [ ] README’deki test sayılarının gerçek testlerle doğrulanması
- [ ] Docker/cPanel/Kubernetes kurulumlarının ayrı ayrı doğrulanması

## V2 Karşılaştırma Raporundan Eklenenler

- [ ] Ürün ek paketlerini ürün, sepet ve hizmet otomasyonuna uçtan uca bağla
- [ ] Özel sipariş alanlarını hizmete ve hosting otomasyonuna aktar
- [ ] Ek paket silindiğinde sepet ve export içeriğinden kaldır
- [ ] Fiyat/periyot/kur/kar marjı dönüşümünü public-sepet-admin tutarlı hale getir
- [ ] Ücretsiz domain koşullarını sipariş akışına bağla
- [ ] Mobile Builder gerçek APK üretimi
- [ ] Mobile Builder gerçek AAB üretimi
- [ ] Android signing/keystore yönetimi
- [ ] Push notification modülü ve build akışı
- [ ] Firebase ayarlarının Mobile Builder'a aktarılması
- [ ] Radyo mobil player, yayın akışı ve istek hattı modülleri
- [ ] E-ticaret mobil uygulama ürün/sepet/ödeme akışı
- [ ] Site Builder autosave ve revision geçmişi
- [ ] Site Builder undo/redo
- [ ] Site Builder template import/export
- [ ] Site Builder global font/renk ve breakpoint sistemi
- [ ] Site Builder form cevaplarının admin panelinde gösterimi
- [ ] Özel domain ile builder yayınlama
- [ ] AI ile tam site oluşturma akışını tamamla
- [ ] AI ile mobil uygulama oluşturma akışını tamamla
- [ ] AI logo/görsel/splash üretim akışı
- [ ] AI function calling ve onay mekanizması
- [ ] AI provider otomatik fallback ve maliyet/token raporu
- [ ] AI Mobile Builder özel komutları
- [ ] Sentry entegrasyonu
- [ ] S3/Wasabi/Backblaze off-site backup
- [ ] UptimeRobot entegrasyonu
- [ ] reCAPTCHA login/register/ticket/domain doğrulaması
- [ ] Tam Uyumsoft/GİB e-Fatura ve E-Arşiv akışı
- [ ] E-Fatura iptal, yeniden gönderim ve durum senkronizasyonu
- [ ] Gerçek PayTR başarılı/başarısız/callback testi
- [ ] Gerçek registrar kayıt/transfer/yenileme/EPP/DNS testi
- [ ] cPanel/DirectAdmin/Plesk canlı provisioning testi
- [ ] Hosting kullanım bilgisi ve paket değişikliği canlı testi
- [ ] BTK/5651 CSV alanlarının nihai doğrulaması
- [ ] Raporla uyumlu yasal sayfalar ve footer alanları
- [ ] README/QUICKSTART/DEPLOYMENT dokümanlarını gerçek duruma göre güncelle
- [ ] README’deki test ve migration sayılarını gerçek çıktıyla güncelle

## Production QA

- [ ] PHP syntax ve warning taraması
- [x] JavaScript syntax kontrolü
- [ ] CSS/JS console ve event kontrolü
- [ ] Tüm route/404/500 taraması
- [ ] Güvenlik ve yetki testi
- [ ] Cron/queue/backup testi
- [ ] Fresh install testi
- [ ] Güncelleme kurulumu testi
- [ ] Final cPanel production ZIP

## Çalışma kuralı

Yeni kullanıcı soruları veya talepleri bu backlog'a eklenir. Önceki iş yarım bırakılmaz; maddeler sırayla tamamlanır ve tamamlananlar işaretlenir. Ara ZIP hazırlanmaz; final QA tamamlandıktan sonra tek final paket oluşturulur.
