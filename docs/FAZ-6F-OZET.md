# Faz 6f — Ops & Growth Polish

**Tarih:** 27 Temmuz 2026
**Odak:** Referral payout istekleri, SMTP test, hizmet detay usage grafiği, uptime probe

## Tamamlananlar

### 1. Referral Payout İstekleri 💸
- **Migration 0063:** `payout_requests` tablosu (amount, iban, holder, bank, status, admin notlar)
- **`PayoutService`** — request/approve/markPaid/reject/cancel
- **Bakiye korumalı akış:** İstek anında bakiye rezerve edilir → red/iptal olursa iade
- **TR IBAN validation** (`TR + 24 rakam`), min_payout kontrolü, yetersiz bakiye engeli
- Müşteri panel: `/panel/referanslarim` içinde **çekim formu** + geçmiş çekimler tablosu
- Admin panel: bekleyen payout kuyruğu — 3 buton (Onayla / Ödendi / Reddet)
- Müşteri iptal edebilir (sadece `pending` iken), admin red ederse bakiye iade
- 7 unit test

### 2. SMTP Test Butonu ✉️
- Admin > Ayarlar > SMTP grubunda **"✉️ Test Mail Gönder"** butonu
- AJAX endpoint (`/admin/ayarlar/smtp-test`) — mevcut .env/settings ayarlarını kullanır
- Kullanıcı istediği adrese test mail alabilir (SMTP kurulumunu anında doğrular)

### 3. Hizmet Detay Usage Grafiği 📊
- Progress bar (renk kodlu: %70 sarı, %90 kırmızı)
- Disk kullanımı + trafik kullanımı ayrı grafik
- Quota gösterimi (varsayılan 5GB disk, 50GB trafik — Faz 7'de product tablosundan gelecek)
- Son güncelleme timestamp'i + "her 6 saatte bir otomatik senkron" bilgisi

### 4. Uptime Probe 📡
- **`UptimeProbe` servisi** — curl ile HEAD isteği, HTTP kodu + yanıt süresi + **SSL bilgisi**
- Admin > Health Center'da **📡 Uptime Probe** tablosu (varsayılan 3 URL: `/`, `/domain`, `/hosting`)
- Kolon: HTTP kodu (renk kodlu) / Yanıt (ms — sarı/kırmızı eşikleri) / **SSL geçerli + kalan gün** / UP/DOWN badge
- Bağımsız (Chromium/Playwright gerektirmez), 8 sn timeout
- 2 unit test

## Test kapsaması

| Test | Test # |
|---|---|
| PayoutServiceTest | 7 |
| UptimeProbeTest | 2 |

**Toplam kümülatif: 137 test, 506 assertion** (önceki 128, 484)

## Sistem istatistikleri

| Metrik | Faz 6e | Faz 6f |
|---|---|---|
| **Migration** | 62 | **63** |
| **PHPUnit test** | 128 | **137** |
| **Assertion** | 484 | **506** |

## Sıradaki (Faz 6g)

- Şifre değiştirme ekranı (mevcut şifre + yeni şifre)
- Fatura PDF indirme (Dompdf zaten var)
- Kupon kodu Order Notes'a yansıma
- Domain WHOIS bilgi güncelleme
- İki dilli e-posta template desteği (tr/en)
