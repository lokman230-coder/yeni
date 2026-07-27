# 00 · Rekabet Avantajı ve Konumlandırma

> Ahost Bilişim'ın hedefi: **WHMCS + WISECP + Blesta'nın toplamından daha ileri** olmak.
> Bu doküman, her modülün rakiplerden hangi noktada üstün olması gerektiğini
> ölçülebilir kriterlerle tanımlar. Kod yazılırken bu kriterler gözetilir.

---

## 1. Rakip Karşılaştırma Matrisi

| Alan | WHMCS | WISECP | Blesta | **Ahost Bilişim (hedef)** |
|---|---|---|---|---|
| **UI/UX** | 2010'lardan kalma tema | Modern ama ağır | Sade ama basit | 2026 seviyesi — glassmorphism, dark/light, motion, mikro-etkileşim |
| **Site Builder** | ❌ yok | ❌ yok | ❌ yok | ✅ **Elementor + Visual Composer + AI** (üçünün toplamı) |
| **Mobile Builder** | ❌ | ❌ | ❌ | ✅ APK/AAB/kaynak kod export |
| **AI Asistan** | 3rd party eklenti | zayıf | ❌ | ✅ 3 bağlam (public/müşteri/admin) + gerçek aksiyon çalıştırma |
| **Marketplace** | ❌ | kısıtlı | ❌ | ✅ Domain + tasarım + script + hizmet |
| **Domain Değerleme** | ❌ | ❌ | ❌ | ✅ Çok faktörlü skorlama motoru |
| **Site Araçları** | ❌ | birkaç | ❌ | ✅ 15+ araç, hepsi gerçek veri |
| **Çerez Analitiği** | ❌ | ❌ | ❌ | ✅ Kendi motoru, KVKK/GDPR uyumlu |
| **BTK/5651 Export** | ❌ | manuel | ❌ | ✅ Tek tıkla CSV |
| **Modülerlik** | ioncube kilitli | kısmen | ✅ açık | ✅ Açık, her modül izole, hot-swap |
| **Türkçe & TR entegrasyonlar** | çeviri | native | çeviri | ✅ Native + PayTR + DomainNameAPI + NetGSM + e-Fatura hazır |
| **Kod kalitesi** | legacy PHP | karmaşık | temiz | ✅ PHP 8.2 strict, PSR-12, test coverage ≥60% |
| **Performans** | ağır | orta | hızlı | ✅ CLS<0.05, TTFB<200ms, LCP<2.5s hedef |
| **Fiyat modeli** | lisanslı pahalı | lisanslı | lisanslı | ✅ Kendi mülkiyetiniz — lisans yok |
| **API-first** | ❌ REST kısmen | kısmen | ✅ | ✅ Her modül REST + JSON, WebHook, GraphQL opsiyonel |
| **Otomasyon** | temel | orta | ✅ | ✅ Workflow builder (if-this-then-that) |
| **Extension/Eklenti** | Marketplace var | var | var | ✅ Modül Merkezi + Composer package + hot install |

---

## 2. Site Builder — "Elementor + Visual Composer + AI"

Bu tek başına platformumuzun **en büyük farkı**. Ayrıntılı ele alıyoruz.

### 2.1 Elementor'dan alacağımız güçler
- **Serbest sürükle-bırak** — herhangi bir bloğu herhangi bir yere.
- **Canlı önizleme** — değişiklik anında ekranda.
- **Cihaz görünümü** — masaüstü / tablet / mobil sekmeleri, her cihaz için ayrı stil.
- **Global renk & tipografi** — tek yerden değiştir, tüm site güncellensin.
- **İkon kütüphanesi** — Lucide gömülü.
- **Hover/animasyon efektleri** — kod yazmadan.
- **Motion editor** — scroll'a bağlı animasyon.
- **Reusable templates** — kaydet, başka sayfada kullan.
- **Revision history** — geri al / ileri.

### 2.2 Visual Composer / WPBakery'den alacağımız güçler
- **Row / Column / Inner-column** yapısal sistem — her satır 12-grid.
- **Backend editor + Frontend editor** — hem şema (ağaç) hem canvas.
- **Shortcode benzeri blok kodu** — export/import kolay.
- **Content Elements Library** — geniş, kategorili blok kütüphanesi.
- **Template roles** — hangi bloğu hangi kullanıcı görebilir/düzenleyebilir.
- **Bulk editing** — birden fazla bloğu birlikte düzenle.
- **Custom CSS per block** — sadece o bloğa özel stil (namespace'li).

### 2.3 AI'dan gelecek üstünlükler (bizim farkımız)
- **"Bana bir hosting firması sitesi yap"** → tam site otomatik üretilir.
- **"Bu bölüme SSS ekle"** → AI konuya uygun SSS'leri yazar ve blok kurar.
- **"Bu buton daha dikkat çekici olsun"** → AI renk/gölge/animasyon önerir, tek tık uygular.
- **"Bu metni SEO uyumlu yeniden yaz"** → içerik AI ile iyileştirilir.
- **"Bu site mobilde bozuk"** → AI sorunu bulur ve düzeltmeyi önerir.
- **Şablon eşleme** — sektör seçilir, AI hem şablonu hem içeriği hem renk paletini birlikte kurar.
- **A/B test önerisi** — AI hangi başlığı test etmenizi önerir.

### 2.4 Mimari kararlar (Site Builder için)
- **Editor stack:** Vanilla JS + Web Components (framework bağımlılığı yok).
- **Storage:** JSON tree — `builder_pages.tree` kolonu.
- **Render:** Server-side PHP renderer (SEO için); frontend editörde aynı renderer HMR ile.
- **Block API:** Her blok bir PHP class + JS component + CSS namespace.
- **Block çeşitleri:** Structural (Row/Col/Section) + Content (Text/Image/Button/Form) + Marketing (Pricing/Testimonial/FAQ/CTA) + Data (Product Grid/Blog Grid) + Advanced (Custom HTML/Embed/Countdown).
- **Ekstensibility:** Third-party bloklar `themes/*/blocks/` altında dropbox-vari eklenir.

---

## 3. Mobile Builder — "Mobil için Site Builder"

- Aynı editor motoru, cihaz olarak telefon mockup.
- **Export:** APK (release/debug), AAB (Play Store), Kaynak kod (Flutter proje zip).
- **Push notification** entegrasyonu (Firebase).
- **Radyo template'i özel:** Icecast/Shoutcast stream URL + metadata.

---

## 4. AI Asistan — 3 Bağlam Detayı

| Bağlam | Konuşabildiği konular | Çalıştırabildiği aksiyonlar |
|---|---|---|
| **Public AI** | ürün karşılaştırma, domain önerisi, fiyat sorusu, teknik bilgi | sepete ürün ekle, domain sorgula, iletişim formu doldur |
| **Müşteri AI** | fatura ödemesi, domain yönetimi, hosting sorunu, ticket açma, builder yardımı | fatura öde, DNS güncelle, ticket aç, hizmet yenile |
| **Admin AI** | müşteri arama, sipariş raporu, gelir analizi, log inceleme, ürün oluşturma yardımı | müşteri ara, sipariş listele, rapor üret, ürün taslağı hazırla |

**Kritik kural:** Admin AI asla public sayfaya yönlendiremez. Müşteri AI admin sayfasına yönlendiremez. Bağlam ihlali = otomatik reddetme.

**Aksiyon güvenliği:** AI'ın çalıştırabileceği her aksiyon `Ai/Actions/*Action.php` içinde tanımlıdır ve RBAC ile korunur. AI serbest SQL çalıştıramaz; sadece tanımlı aksiyonları çağırır.

---

## 5. Ölçülebilir Performans Hedefleri

| Metrik | Rakip ortalaması | Ahost Bilişim hedef |
|---|---|---|
| First Contentful Paint | 2.5s | **< 1.2s** |
| Largest Contentful Paint | 4.0s | **< 2.5s** |
| Cumulative Layout Shift | 0.15 | **< 0.05** |
| Time to Interactive | 5.0s | **< 3.0s** |
| TTFB | 600ms | **< 200ms** |
| Lighthouse Performance | 60 | **≥ 90** |
| Lighthouse Accessibility | 75 | **≥ 95** |
| Lighthouse SEO | 80 | **≥ 95** |
| Sepet → Ödeme adımı süresi | 15s | **< 6s** |
| Admin sayfa geçişi | 1.5s | **< 500ms** |

---

## 6. Modül Ekosistemi (Modül Merkezi)

Rakiplerde eklenti kurmak = FTP yükle, aktive et.
**Ahost Bilişim'da:** Modül Merkezi → ara → kur → aktive et → migration otomatik çalışır.

- Her modül `module.php` içinde metadata (isim, versiyon, bağımlılık, migration, izin) taşır.
- `ModuleLoader` başlangıçta discovery yapar, aktif olanları yükler.
- **Hot-disable:** Bir modül pasifleştirilince route'ları düşer, ama veriler durur.
- **Versiyonlama:** SemVer; migration dosyaları geri alınabilir (up/down).

---

## 7. "Neden bizi seçmeliler?" (Pazarlama argümanı)

1. **Kendi kodunuz** — WHMCS lisansına yıllık ödemeniz yok; kaynak sizin.
2. **Türkiye için tasarlandı** — PayTR, DomainNameAPI, NetGSM, e-Fatura, BTK first-class.
3. **Site + Mobil Builder dahil** — ayrıca Elementor lisansı almanıza gerek yok.
4. **AI dahili** — ChatGPT eklentisi kurmanıza gerek yok.
5. **Modern, hızlı, güzel** — müşterilerinizin panele girmek isteyeceği bir arayüz.
6. **Modüler** — istediğinizi kapatın, istediğinizi ekleyin.
7. **Şeffaf** — kodun açık, denetlenebilir, ioncube kilidi yok.
