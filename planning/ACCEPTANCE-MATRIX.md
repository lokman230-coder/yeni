# Kabul Kriterleri Matrisi

Şartname madde 38 ve 40'ın **test edilebilir** hali. Her satır bir kontroldür.
Faz tamamlandığında ilgili satırlar `[x]` olur.

---

## Public Site

| # | Kriter | Nasıl test edilir | Faz |
|---|---|---|---|
| P01 | Tüm public sayfalar 200 döner (30+ route) | Playwright: her route'a GET, status 200 | 2 |
| P02 | Header/footer hiçbir sayfada bozulmaz | Görsel karşılaştırma + CLS<0.05 ölçümü | 1 |
| P03 | Mobil görünüm doğru (ilk render'da) | Chrome DevTools mobil emülatör, layout shift yok | 1 |
| P04 | Domain sorgu gerçek sonuç döner | DomainNameAPI test moduyla `test.com` sorgusu | 4 |
| P05 | 15 site aracının hepsi gerçek veri döndürür | Her araç için `example.com` test | 4 |
| P06 | Çerez kabul çalışır → analytics event kaydeder | Kabul et → sayfa değiştir → DB'de event var | 2 |
| P07 | Çerez red çalışır → analytics event kaydetmez | Reddet → sayfa değiştir → DB'de event yok | 2 |
| P08 | Dil değişimi tüm sayfalarda korunur | TR → EN switch → 5 farklı sayfa gez | 2 |
| P09 | Para birimi değişimi ürün+sepet'te tutarlı | TRY → USD → sepet, ürün detay, admin fiyat tutarlı | 3 |
| P10 | Console error/warning yok | DevTools console — critical error yok | 1-2 |
| P11 | Tüm sayfalarda SEO title + meta description | View-source kontrolü | 2 |
| P12 | 404 sayfası düzgün — fatal değil | Rastgele path → 404 template | 1 |
| P13 | AI kayan widget public'te solda | Görsel test | 5 |
| P14 | Lighthouse Performance ≥ 90 (public home) | Lighthouse CI | 6 |
| P15 | Lighthouse Accessibility ≥ 95 | Lighthouse CI | 6 |

---

## Admin Panel

| # | Kriter | Nasıl test edilir | Faz |
|---|---|---|---|
| A01 | Admin login çalışır | POST /admin/login → dashboard'a yönlenir | 1 |
| A02 | Yanlış şifre generic hata verir | 5 hatalı → "e-posta veya şifre hatalı" | 1 |
| A03 | 22 admin menüsünün hepsi açılır | Her menüye tıkla, 200 döner | 2 |
| A04 | Ürün oluşturma çalışır | Form doldur → DB'de kayıt | 3 |
| A05 | Fiyatlandırma ekleme "Ekle" butonu yeni satır açar | Buton → yeni row DOM'da | 3 |
| A06 | Aylık + yıllık iki periyot kaydedilir | Her ikisi de aktif olarak DB'de | 3 |
| A07 | Boş fiyat kaydedilirse pasif işaretlenir | Boş fiyat → `is_active=0` | 3 |
| A08 | Ek paket "Ek Paket Ekle" ile açılır | Buton → alan görünür | 3 |
| A09 | Özel alan başta görünmez, buton ile açılır | Yeni ürün → alan yok; Ekle → görünür | 3 |
| A10 | Zorunlu özel alan boş kalırsa sipariş verilemez | Sepet checkout → validation hatası | 3 |
| A11 | Sipariş listesi filtrelenebilir | Status filtresi çalışır | 3 |
| A12 | Fatura otomatik oluşur (ödeme sonrası) | PayTR callback → invoice tablo satır | 3 |
| A13 | Çerez analizi sayfası açılır ve veri gösterir | Toplam olay, ziyaretçi, son olaylar var | 2 |
| A14 | Admin AI aksiyonu admin içinde kalır | AI "ürün ara" → admin ürün listesine gider (public'e değil) | 5 |
| A15 | Quick search müşteri/sipariş/domain/ürün bulur | Test her tip için | 2 |
| A16 | Log ekranı filtreleme çalışır | Tarih + tip filtresi | 5 |
| A17 | BTK CSV export indirir | Buton → CSV dosyası, doğru satırlar | 5 |
| A18 | Kur güncelleme (manuel + API) çalışır | Buton → currency_rates tablosu güncel | 2 |
| A19 | Sunucu ekleme + test bağlantı | cPanel test server → başarılı | 4 |
| A20 | Kaydet sonrası sayfa açık kalır (kapanmaz) | Form submit → aynı sayfada success toast | 3 |

---

## Müşteri Panel

| # | Kriter | Nasıl test edilir | Faz |
|---|---|---|---|
| C01 | Kayıt (register) çalışır | Form → email doğrulama linki gelir | 2 |
| C02 | Login çalışır | POST /giris → dashboard | 2 |
| C03 | Şifremi unuttum → email geldi | Token 30dk geçerli | 2 |
| C04 | Sepete ürün ekle çalışır | Buton → sepet güncellenir (session veya DB) | 3 |
| C05 | Periyot seçimi doğru sepette yansır | Aylık seç → sepette "Aylık" yazar | 3 |
| C06 | Ek paket seç → sepete eklenir | Fiyat toplama doğru | 3 |
| C07 | Ek paket sil → sepetten düşer | Fiyat + oluşacak paket içeriğinden düşer | 3 |
| C08 | Özel alan doldurulur ve kaydedilir | Sipariş sonrası admin'de görülür | 3 |
| C09 | Ödeme başarılı → hizmet oluşur | PayTR test → order.status=paid, hosting_account var | 4 |
| C10 | Fatura görüntüleme + PDF indirme | PDF dompdf ile üretilir | 3 |
| C11 | Ticket açma çalışır | Form → tickets kaydı, admin bildirimi | 5 |
| C12 | Domain yenileme çalışır | Buton → registrar API → next_due_date güncel | 4 |
| C13 | Site Builder demo kullanılabilir | Şablon seç → canvas açılır | 5 |
| C14 | Müşteri AI cevap verir | "faturam ne kadar" → doğru cevap | 5 |
| C15 | Hesabımı sil → veriler anonimleştirilir (KVKK) | GDPR endpoint | 6 |

---

## Ödeme

| # | Kriter | Nasıl test edilir | Faz |
|---|---|---|---|
| PY01 | Kupon (yüzde) uygulanır | %10 kupon → 100 → 90 | 3 |
| PY02 | Kupon (sabit) uygulanır | 50 TL → 100 → 50 | 3 |
| PY03 | Kupon süresi geçmişse red edilir | Bitmiş kupon → hata mesajı | 3 |
| PY04 | Vergi hesaplanır (KDV 20%) | 100 → 120 | 3 |
| PY05 | İndirim + vergi sırası doğru | 100 → -10 (kupon) → 90 → +18 (KDV) → 108 | 3 |
| PY06 | PayTR başarılı callback → sipariş paid | Test kart → callback OK → order.status=paid | 3 |
| PY07 | PayTR başarısız callback → sipariş failed | Test kart red → callback FAIL → status=failed | 3 |
| PY08 | Havale seçimi → sipariş pending + banka bilgisi | Havale seç → talimat email gider | 3 |
| PY09 | Bakiye ile ödeme çalışır | Müşteri bakiye ≥ toplam → tek tık öde | 3 |
| PY10 | Ödeme sonrası otomasyon çalışır | Hosting sipariş → cPanel hesabı oluşur | 4 |

---

## Domain

| # | Kriter | Nasıl test edilir | Faz |
|---|---|---|---|
| D01 | Müsait domain → "Sepete Ekle" görünür | test-domain-xyz.com sorgu | 4 |
| D02 | Kayıtlı domain → WHOIS/DNS/Ön sipariş görünür | google.com sorgu | 4 |
| D03 | WHOIS kartı taşmaz, "sinyali yok" gibi metin yok | Görsel test | 4 |
| D04 | Transfer koruması var/yok net gösterilir | google.com → "Var" | 4 |
| D05 | DNS: A, AAAA, MX, TXT, NS, CNAME, CAA gösterilir | google.com DNS card | 4 |
| D06 | SSL: issuer, başlangıç, bitiş, kalan gün, CN | google.com SSL card | 4 |
| D07 | Domain transfer akışı çalışır | EPP kodu ile transfer başlatılır | 4 |
| D08 | Domain değerleme skorları hesaplanır | Tüm faktörler (TLD, uzunluk, marka, yaş, SEO, DNS, SSL, WHOIS) | 4 |
| D09 | "GoDaddy tarzı" gibi marka ifadesi yok | UI review | 4 |
| D10 | Önerilen domainler yan yana düzgün | Grid layout, taşma yok | 4 |

---

## Builder

| # | Kriter | Nasıl test edilir | Faz |
|---|---|---|---|
| B01 | Şablon değişince önizleme anında güncellenir | Şablon dropdown → canvas değişir | 5 |
| B02 | Renk değişince canvas güncellenir | Color picker → tema | 5 |
| B03 | Site adı değişikliği canvas'a yansır | Input → header'da isim | 5 |
| B04 | Menü ekleme çalışır | + menu → nav güncellenir | 5 |
| B05 | Blok ekleme (drag/insert) çalışır | Blok kütüphanesinden ekle | 5 |
| B06 | Blok taşıma çalışır | Yukarı/aşağı taşı butonu | 5 |
| B07 | Blok silme çalışır | Sil butonu → undo mümkün | 5 |
| B08 | Blok kopyalama çalışır | Duplicate → yeni instance | 5 |
| B09 | Hosting seçiliyken DJ/Radio blokları görünmez | Sektör filtresi | 5 |
| B10 | Radyo seçiliyken ödeme/keşif blokları görünmez | Sektör filtresi | 5 |
| B11 | E-ticaret seçiliyken sepet/ödeme modülleri var | Sektör filtresi | 5 |
| B12 | Modül ekleme sepete fiyat düşer | Push modül ekle → sepet +100 TL | 5 |
| B13 | Modül silme sepetten düşer + zip'ten düşer | Modül sil → sepet -100 TL | 5 |
| B14 | Zip export indirilebilir | Buton → zip dosyası | 5 |
| B15 | Kaynak kod export doğru dosyaları içerir | Zip içeriği kontrolü | 5 |

---

## Mobile Builder

| # | Kriter | Nasıl test edilir | Faz |
|---|---|---|---|
| M01 | Telefon mockup içinde canlı önizleme | Editor açılır | 5 |
| M02 | Radyo şablonu player + stream URL alanı | Radyo şablonu seç | 5 |
| M03 | E-ticaret şablonu sepet/ödeme modülleri | E-ticaret şablonu seç | 5 |
| M04 | Splash ekranı özelleştirilebilir | Splash görsel yükle | 5 |
| M05 | Push notification ayarı yapılır | Firebase key input | 5 |
| M06 | Build queue işi kabul eder | Buton → build.status=pending | 5 |

---

## AI

| # | Kriter | Nasıl test edilir | Faz |
|---|---|---|---|
| AI01 | Enter tuşu mesaj gönderir | Klavye event | 5 |
| AI02 | Buton mesaj gönderir | Click event | 5 |
| AI03 | Public AI ürün önerir | "hosting öner" → hosting listesi | 5 |
| AI04 | Public AI login'e zorlamaz | "site oluştur" → 2 buton | 5 |
| AI05 | Müşteri AI faturaları görebilir | "faturalarım" → cevap | 5 |
| AI06 | Admin AI admin sayfaları içinde kalır | "ürün paketleri" → admin/products | 5 |
| AI07 | Admin AI public sayfaya yönlendirmez | Test: AI response içinde `/hosting` URL varsa reddet | 5 |
| AI08 | AI aksiyon RBAC ile korunur | İzinsiz aksiyon → 403 | 5 |
| AI09 | AI log'a yazar (prompt + response + tokens) | ai_logs kaydı | 5 |

---

## Genel

| # | Kriter | Nasıl test edilir | Faz |
|---|---|---|---|
| G01 | PHP warning/notice/deprecated/fatal yok | error_reporting(E_ALL); log dosyası temiz | 1-6 |
| G02 | Tüm butonlar çalışır veya pasifse neden gösterilir | UI review | 1-6 |
| G03 | Layout sıçraması yok (yenilemede) | CLS < 0.05 | 1 |
| G04 | Mobil responsive kusursuz | 320px, 375px, 768px, 1024px, 1440px, 1920px test | 1-2 |
| G05 | Kod modüler ve temiz | PHPStan level 6+, phpcpd temiz | 1-6 |
| G06 | Migration fresh install çalışır | `migrate:fresh --seed` sıfırdan | 1 |
| G07 | Yayın öncesi checklist tamam (madde 39) | ROADMAP Faz 6 kabul | 6 |
| G08 | Kabul kriterleri (madde 40) tamam | Bu doküman tamamı | 6 |

---

## Toplam

- **P** (Public): 15 kriter
- **A** (Admin): 20 kriter
- **C** (Customer): 15 kriter
- **PY** (Ödeme): 10 kriter
- **D** (Domain): 10 kriter
- **B** (Site Builder): 15 kriter
- **M** (Mobile Builder): 6 kriter
- **AI**: 9 kriter
- **G** (Genel): 8 kriter

**Toplam: 108 test edilebilir kabul kriteri.**

Faz sonlarında bu matris güncellenir ve size raporlanır.
