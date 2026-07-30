# Faz 6g — UX Tamamlama

**Tarih:** 27 Temmuz 2026
**Odak:** Şifre değiştirme, Fatura PDF, Kupon kodu görünürlüğü, Domain WHOIS refresh

## Tamamlananlar

### 1. Şifre Değiştirme 🔑
- `/panel/guvenlik` altına **"Şifre Değiştir"** kartı eklendi
- Mevcut şifre doğrulama zorunlu (PasswordHasher::verify)
- Min 8 karakter + eşleşme kontrolü
- Kayıt sonrası `password_hash` güncellenir, `SessionManager::flash` ile geri bildirim

### 2. Fatura PDF İndirme 📄
- **`InvoicePdfService`** — Dompdf ile Türkçe destekli UBL benzeri fatura üretimi
- **DejaVu Sans** fontu ile Türkçe karakter (Ş, İ, Ç, Ğ, Ü, Ö) sorunsuz
- Ahost Bilişim logosu, VKN, adres bilgileri firma tarafında; TC/VKN + adres müşteri tarafında
- Durum badge (✓ ÖDENDİ / ⚠️ VADESİ GEÇTİ / KISMİ vb) renk kodlu
- Kalem tablosu: Açıklama / Adet / Birim Fiyat / KDV / Tutar
- Sağ altta özet: Ara Toplam, İndirim (varsa), KDV, **TOPLAM** (mavi bantla), Kalan (varsa)
- Alt bilgi: "elektronik olarak oluşturulmuştur"

**Endpoint'ler:**
- `/panel/fatura/{id}/pdf` — müşteri (kendi faturası)
- `/admin/faturalar/{id}/pdf` — admin

### 3. Kupon Kodu Görünürlüğü 🎟️
- Sipariş listesi tablosunda sipariş numarasının altında `🎟️ WELCOME10` etiketi

### 4. Domain WHOIS Refresh 🔍
- Admin domain düzenleme sayfasında **"🔍 WHOIS'ten Yenile"** butonu
- Var olan `WhoisTool`'u çağırır, bitiş tarihi + nameserver bilgilerini otomatik günceller
- Başarılıysa "✓ WHOIS güncellendi — bitiş: 2027-01-01" mesajı

## Test kapsaması

Toplam kümülatif: **137 test, 506 assertion** (Faz 6f'den değişmedi — PDF/UI-only özellikler için ayrı test eklenmedi, mevcut smoke ile doğrulandı)

## Sistem istatistikleri

| Metrik | Faz 6f | Faz 6g |
|---|---|---|
| **PHPUnit test** | 137 | 137 |
| **Assertion** | 506 | 506 |
| **PDF üretim** | Yok | ✓ (Dompdf) |
| **Şifre yönetimi** | Reset (mail) | ✓ + Panel-içi değiştirme |

## Sıradaki (Faz 6h — son polish)

- Panel navbar kullanıcı menüsü (isim + avatar dropdown)
- Admin dashboard metrik kartları (canlı sayaçlar)
- API rate-limit endpoint bilgisi (X-RateLimit headers)
- Hostname otomatik https tespit + non-https uyarısı (setup wizard)
- Global search (admin arama kutusu)
