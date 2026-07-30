# FAZ 6Q — AI Tool Calling + Gerçek İşlem Yapan AI
> Tarih: 2026-07-27 · Süre: ~2 saat · Sonuç: **AI ARTIK KONUŞMUYOR, İŞ YAPIYOR**

## 🎯 Yapılanlar

### 1. AI Tool Calling Altyapısı
- **`AiToolRegistry`** — merkezi tool kayıt sistemi
  - `register($context, $tool)` — tool tanımla
  - `forContext($context)` — bağlama ait tool listesi
  - `call($context, $name, $args, $userId, $userType)` — güvenli çağırım
  - `toOpenAiFunctions($context)` — OpenAI function calling formatı
- **Güvenlik katmanları:**
  - Context isolation (customer/admin/builder cross-context yasak)
  - Permission handler (kendi kaydına mı erişiyor kontrolü)
  - Destructive flag (`confirm=true` zorunlu — bakım açma, silme vb.)
  - Audit log (her çağrı `admin_activity_logs`'a düşer)

### 2. Müşteri Tool'ları (7 adet)
- ✅ `create_ticket` — "ticket aç konu: X mesaj: Y" → gerçek ticket + reply
- ✅ `request_password_reset` — hosting hesabı için sıfırlama talebi (ticket)
- ✅ `pay_invoice` — fatura ödeme sayfasına redirect
- ✅ `renew_domain` — domain yenileme sepete ekleme
- ✅ `my_services_summary` — hosting/domain/fatura özeti (canlı DB)
- ✅ `toggle_2fa` — güvenlik sayfası yönlendirme
- ✅ `navigate` — panel içi sayfa geçişi (8 sayfa)

### 3. Admin Tool'ları (8 adet)
- ✅ `create_coupon` — "kupon oluştur XYZ %15 indirim" → DB'de kupon
- ✅ `dashboard_summary` — 30 gün gelir/sipariş/müşteri/ticket
- ✅ `find_customer` — email/ad/telefonla arama
- ✅ `maintenance_mode` — bakım aç/kapat (destructive)
- ✅ `send_payment_reminders` — ödenmemiş faturalara toplu mail (destructive)
- ✅ `clear_cache` — storage/cache temizle
- ✅ `health_check` — console health:check çalıştır
- ✅ `navigate` — 15 admin sayfasına yönlendirme

### 4. Builder Tool'ları (6 adet)
- ✅ `add_block` — hero/features/cta/footer/pricing/... blok ekle
- ✅ `update_block_text` — blok başlık/altbaşlık/description değiştir
- ✅ `change_color_palette` — 6 palet (pastel/dark/ocean/sunset/forest/bold)
- ✅ `delete_block` — blok sil (destructive)
- ✅ `generate_block_content` — AI ile blok içeriği üret
- ✅ `list_blocks` — projedeki blokları listele

### 5. Endpoint'ler
- `POST /ai/customer` — Tool calling ile
- `POST /ai/admin` — Tool calling ile
- `POST /ai/builder` — Yeni (Builder AI özel)
- `POST /ai/public` — Sadece bilgi (tool yok, güvenlik)

### 6. Builder Editörünün İçinde AI Chat Paneli
- Sağ alt köşede sabit **🤖 AI FAB butonu**
- Tıklayınca modal chat açılır (380×500px)
- Mor-pembe gradient tasarım
- Enter ile gönderim, otomatik sayfa yenileme
- Örnekler: "hero blok ekle", "pastel renk", "başlığı değiştir"

### 7. Mobile Builder AI
- `/ai/site-olustur?kind=mobile` — mobile app üret
- Form'da kind toggle (🖥 Web Sitesi / 📱 Mobil Uygulama)
- `SiteGenerator::generate` `kind` parametresi ekledi
- Mobile için template library (starterTree)

### 8. Heuristic Detection
- **16 örüntü** (customer 6 + admin 6 + builder 4)
- Türkçe pattern matching: "ticket aç", "kupon oluştur", "hero blok ekle"
- OpenAI ayarlıysa function calling kullanılır (daha akıllı)

## 📊 Test Sonucu

```
✅ Detection: 16/16 tool doğru tespit edildi
✅ Execution: Gerçek DB'ye insert + update yapıldı
✅ PHPUnit:   186 test / 619 assertion / 0 fail (+9 yeni test)
✅ Audit:     24 ai_logs + 15 admin_activity_logs kaydı
```

## 🎬 Örnek Kullanımlar

### Müşteri: 
> "SSL şifremi sıfırla #5"  
→ AI: Ticket #123 oluşturdu (yüksek öncelikli)

> "domain 3 yıl yenile #7"  
→ AI: Sepete eklendi, ödeme sayfasına yönlendiriyor

### Admin: 
> "kupon oluştur SUMMER25 %25 indirim"  
→ AI: Kupon **SUMMER25** oluşturuldu (30 gün geçerli)

> "ödenmemiş faturalara hatırlatma yolla onay"  
→ AI: 47 hatırlatma maili kuyruğa alındı

### Builder: 
> "hero blok ekle"  
→ AI: 'hero' bloğu eklendi (pozisyon 0)

> "pastel renk paletine geç"  
→ AI: Palet **pastel** olarak değiştirildi, sayfa yenileniyor

## 📁 Yeni Dosyalar

- `app/Modules/Ai/Services/AiToolRegistry.php`
- `app/Modules/Ai/Services/AiCustomerTools.php` (7 tool)
- `app/Modules/Ai/Services/AiAdminTools.php` (8 tool)
- `app/Modules/Ai/Services/AiBuilderTools.php` (6 tool)
- `tests/Unit/AiToolRegistryTest.php` (9 test)

## 🔧 Değişen Dosyalar

- `app/Modules/Ai/Services/AiService.php` (+ `askWithTools()` + `detectTool()`)
- `app/Modules/Ai/Services/ContentGenerator.php` (+ `generate()` jenerik)
- `app/Modules/Ai/Services/SiteGenerator.php` (kind parametresi)
- `app/Modules/Ai/Controllers/AiController.php` (tool calling + `chatBuilder`)
- `app/Modules/Ai/Controllers/SiteGeneratorController.php` (kind desteği)
- `app/Modules/Ai/Views/site-generator/form.php` (kind toggle)
- `app/Modules/Ai/routes/web.php` (+ `/ai/builder`)
- `app/Modules/Builder/Views/editor.php` (+ AI chat paneli)

## ✅ Sonuç

**AI artık sadece bilgi vermiyor — GERÇEK İŞLEM YAPIYOR.**
- Müşteri: sohbetle ticket açar, domain yeniler, ödeme başlatır
- Admin: sohbetle kupon oluşturur, cache temizler, mail yollar
- Builder: sohbetle blok ekler, palet değiştirir, içerik üretir
- Mobile Builder: AI ile sıfırdan mobil app üretebilir

Tüm işlemler audit'lenir, destructive işlemler onay ister.
