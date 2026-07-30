# WHMCS + WISECP + Blesta Parity Karşılaştırması

Amaç: Bu 3 platformdaki HER özelliği bizde de olmasını sağlamak, sonra üstüne fazlasını eklemek.

## 📋 ADMIN Tarafı

### 👥 Müşteri Yönetimi
| Özellik | WHMCS | WISECP | Blesta | Ahost | Not |
|---|:-:|:-:|:-:|:-:|---|
| Müşteri listesi | ✅ | ✅ | ✅ | ✅ | Var |
| Müşteri detay | ✅ | ✅ | ✅ | ✅ | Var |
| Müşteri ekle/düzenle | ✅ | ✅ | ✅ | ⚠️ | **CRUD form eksik** |
| Müşteri adına giriş | ✅ | ✅ | ✅ | ✅ | Impersonate |
| Bakiye ekle/çıkar | ✅ | ✅ | ✅ | ✅ | CreditService |
| Müşteri notları | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Müşteri grupları | ✅ | ✅ | ✅ | ❌ | **YOK** (bulk indirim için) |
| Toplu mail | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Müşteri suspend/unsuspend | ✅ | ✅ | ✅ | ⚠️ | status var, UI yok |
| Müşteri silme | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Müşteri özel alanları | ✅ | ✅ | ✅ | ❌ | **YOK** |
| İletişim (contacts) | ✅ | ✅ | ✅ | ❌ | **YOK** (1 müşteri, çoklu iletişim) |

### 📦 Ürün / Hizmet
| Özellik | WHMCS | WISECP | Blesta | Ahost | Not |
|---|:-:|:-:|:-:|:-:|---|
| Ürün CRUD | ✅ | ✅ | ✅ | ✅ | Var |
| Ürün grupları | ✅ | ✅ | ✅ | ✅ | Var |
| Fiyatlandırma (6 periyot) | ✅ | ✅ | ✅ | ✅ | Var |
| Ek paketler (addons) | ✅ | ✅ | ✅ | ✅ | Var |
| Paket opsiyonları | ✅ | ✅ | ✅ | ✅ | Yeni ekledik |
| Özel alanlar | ✅ | ✅ | ✅ | ✅ | Var |
| Stok yönetimi | ✅ | ✅ | ⚠️ | ❌ | **YOK** |
| Ücretsiz domain kuralları | ✅ | ✅ | ✅ | ⚠️ | products.free_domain var mı? |
| Provisioning modules | ✅ | ✅ | ✅ | ✅ | cPanel/DA/Plesk |
| Server groups (load balance) | ✅ | ⚠️ | ✅ | ❌ | **YOK** |

### 📄 Sipariş / Fatura
| Özellik | WHMCS | WISECP | Blesta | Ahost | Not |
|---|:-:|:-:|:-:|:-:|---|
| Sipariş listesi | ✅ | ✅ | ✅ | ⚠️ | Stub, gerçek liste yok |
| Sipariş detay | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Manuel sipariş oluştur | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Sipariş onayla/iptal | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Fatura listesi (admin) | ✅ | ✅ | ✅ | ⚠️ | /admin/finans stub |
| Fatura detay + PDF | ✅ | ✅ | ✅ | ✅ | Dompdf |
| Manuel fatura oluştur | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Fatura düzenle | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Kısmi ödeme | ✅ | ✅ | ✅ | ⚠️ | partially_paid enum var |
| Havale onayı | ✅ | ✅ | ✅ | ❌ | **YOK admin UI** |
| İade | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Recurring/subscription | ✅ | ✅ | ✅ | ⚠️ | recurring_amount var, cron var |

### 🖥 Sunucu / Registrar
| Özellik | WHMCS | WISECP | Blesta | Ahost | Not |
|---|:-:|:-:|:-:|:-:|---|
| Sunucu ekle | ✅ | ✅ | ✅ | ✅ | Var |
| Sunucu bağlantı testi | ✅ | ✅ | ✅ | ⚠️ | Kontrol edilecek |
| cPanel/DA/Plesk | ✅ | ✅ | ✅ | ✅ | Var |
| Registrar ekle | ✅ | ✅ | ✅ | ✅ | DomainNameApi + Manual |
| Server groups | ✅ | ⚠️ | ✅ | ❌ | **YOK** |
| Server monitoring | ✅ | ✅ | ✅ | ⚠️ | Uptime var, disk/RAM yok |

### 💰 Ödeme / Muhasebe
| Özellik | WHMCS | WISECP | Blesta | Ahost | Not |
|---|:-:|:-:|:-:|:-:|---|
| Ödeme gateway'leri | 10+ | 5+ | 8+ | 4 | PayTR, iyzico, Papara, Shopier |
| Bakiye ile ödeme | ✅ | ✅ | ✅ | ⚠️ | Backend var, UI checkout'ta yok |
| Havale/EFT ödeme | ✅ | ✅ | ✅ | ⚠️ | Var ama admin onay UI yok |
| Ödeme geçmişi (admin) | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Otomatik tahsilat (kart) | ✅ | ✅ | ✅ | ❌ | **YOK** (saklanan kart) |
| Vergi kuralları | ✅ | ✅ | ✅ | ⚠️ | TaxService var, admin UI eksik |
| Muhasebe raporları | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Gelir/gider raporu | ✅ | ✅ | ✅ | ❌ | **YOK** |

### 🎫 Destek
| Özellik | WHMCS | WISECP | Blesta | Ahost | Not |
|---|:-:|:-:|:-:|:-:|---|
| Ticket sistemi | ✅ | ✅ | ✅ | ✅ | Var |
| Departmanlar | ✅ | ✅ | ✅ | ✅ | Var |
| İç notlar | ✅ | ✅ | ✅ | ✅ | Var |
| Dosya ek | ✅ | ✅ | ✅ | ✅ | 6m'de eklendi |
| Ticket priority | ✅ | ✅ | ✅ | ✅ | Var |
| Ticket assign | ✅ | ✅ | ✅ | ❌ | **YOK** (staff'a atama) |
| Canned responses | ✅ | ✅ | ✅ | ❌ | **YOK** (hazır cevap şablon) |
| Bilgi bankası | ✅ | ✅ | ✅ | ✅ | knowledge modülü var |
| Duyurular | ✅ | ✅ | ✅ | ✅ | announcements modülü var |
| Downloads | ✅ | ✅ | ⚠️ | ❌ | **YOK** (dosya kütüphanesi) |

### 🛡 Yönetici / Sistem
| Özellik | WHMCS | WISECP | Blesta | Ahost | Not |
|---|:-:|:-:|:-:|:-:|---|
| Admin rolleri | ✅ | ✅ | ✅ | ⚠️ | admin_roles var, UI yok |
| Yetki yönetimi | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Admin log (aktivite) | ✅ | ✅ | ✅ | ✅ | ActivityLog |
| E-posta şablonları | ✅ | ✅ | ✅ | ⚠️ | Seeder var, UI eksik |
| Otomasyon ayarları | ✅ | ✅ | ✅ | ⚠️ | Cron var, UI eksik |
| Modül ayarları | ✅ | ✅ | ✅ | ✅ | Var |
| Tema/branding | ✅ | ✅ | ✅ | ✅ | 5 tema |

### 🌟 Ekstra Modüller
| Özellik | WHMCS | WISECP | Blesta | Ahost | Not |
|---|:-:|:-:|:-:|:-:|---|
| Affiliate program | ✅ | ✅ | ✅ | ✅ | Referral |
| Kupon kodları | ✅ | ✅ | ✅ | ✅ | Var |
| Toplu ürün fiyat güncelleme | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Multi-currency | ✅ | ✅ | ✅ | ✅ | TCMB + margin |
| Multi-language | ✅ | ✅ | ✅ | ⚠️ | lang/ var, sadece TR |
| API (REST) | ✅ | ✅ | ✅ | ⚠️ | OpenAPI spec var, endpoint az |
| İzin/Lisans yönetimi | ✅ | ⚠️ | ⚠️ | ❌ | **YOK** (software lisans satışı) |

## 👤 MÜŞTERİ Paneli

| Özellik | WHMCS | WISECP | Blesta | Ahost | Not |
|---|:-:|:-:|:-:|:-:|---|
| Dashboard | ✅ | ✅ | ✅ | ✅ | Var |
| Hizmetlerim | ✅ | ✅ | ✅ | ✅ | Var |
| Hizmet detay + şifre | ✅ | ✅ | ✅ | ✅ | Az önce eklendi |
| Hizmet yükseltme/downgrade | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Hizmet iptal talebi | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Domainlerim | ✅ | ✅ | ✅ | ✅ | Var |
| Domain yönet (NS/EPP) | ✅ | ✅ | ✅ | ⚠️ | Route var, controller eksik |
| Domain yenile | ✅ | ✅ | ✅ | ⚠️ | Route var, akış eksik |
| Domain transfer başlat | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Faturalar | ✅ | ✅ | ✅ | ✅ | Var |
| Fatura öde | ✅ | ✅ | ✅ | ⚠️ | Checkout'a yönlendirme lazım |
| Ödeme geçmişi | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Bakiye yükleme | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Destek talepleri | ✅ | ✅ | ✅ | ✅ | Var |
| Bilgi bankası | ✅ | ✅ | ✅ | ✅ | Var |
| Duyurular | ✅ | ✅ | ✅ | ✅ | Var |
| Profil | ✅ | ✅ | ✅ | ✅ | Var |
| İletişim bilgileri (multi) | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Şifre değiştir | ✅ | ✅ | ✅ | ✅ | Var |
| 2FA | ✅ | ✅ | ✅ | ✅ | Var |
| API tokens | ✅ | ⚠️ | ✅ | ❌ | **YOK** |
| E-posta doğrulama | ✅ | ✅ | ✅ | ✅ | Var |
| Kayıtlı kartlar | ✅ | ✅ | ✅ | ❌ | **YOK** |
| Referans/Affiliate | ✅ | ✅ | ✅ | ✅ | Var |
| Downloads | ✅ | ⚠️ | ⚠️ | ❌ | **YOK** |

## 🎯 EKSİK ÖZELLİKLER — Öncelik Sırası

### 🔴 KRITIK (canlı öncesi mutlaka)
1. **Müşteri CRUD** (admin ekle/düzenle/sil/suspend)
2. **Sipariş listesi + detay + onay** (admin)
3. **Fatura CRUD + Havale onay** (admin)
4. **Ödeme geçmişi** (admin + müşteri)
5. **Bakiye yükleme sayfası** (müşteri)
6. **Domain yönet controller metotları** (müşteri — route var, kod yok)
7. **Domain transfer başlatma** (müşteri)
8. **Fatura ödeme akışı** (müşteri → checkout)

### 🟡 ÖNEMLİ (v1.1)
9. Müşteri notları
10. Müşteri grupları + bulk mail
11. Hizmet yükseltme/downgrade
12. Hizmet iptal talebi
13. Kayıtlı kartlar (saklanan kart tokenı)
14. Otomatik tahsilat
15. Server groups (load balance)
16. Vergi kuralları admin UI
17. Muhasebe raporları
18. Canned responses (destek)
19. Ticket assign
20. Downloads modülü
21. API tokens (müşteri)

### 🟢 EKSTRA (Ahost'a özgü avantajlar — WHMCS'de YOK)
- ✨ AI Tool Calling (customer/admin/builder)
- ✨ Site + Mobile Builder
- ✨ AI Site Generator (11 sektör)
- ✨ AI ile içerik üretimi
- ✨ Impersonate audit log
- ✨ SMS OTP login
- ✨ Import (WHMCS/WISECP/Blesta veri çekme)
- ✨ 5 tema
- ✨ 18 site aracı entegre
- ✨ Marketplace
- ✨ TCMB canlı kur
- ✨ K8s + Docker + deploy scripts
- ✨ OpenAPI spec
- ✨ Modern UI (2026)

## Plan
1. Bu turda: **8 KRITIK madde** — hepsi
2. Sonraki tur: 13 ÖNEMLİ madde
3. v1.1: Ekstra ve iyileştirmeler
