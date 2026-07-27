# 06 · Güvenlik

Şartname madde 31-32 karşılığı. Her madde uygulamalı, "olsa iyi olur" değil.

---

## 1. Girdi Doğrulama & Sanitizasyon

- Ham `$_GET`, `$_POST`, `$_COOKIE`, `$_FILES` **hiçbir yerde** okunmaz.
- Tek giriş: `Request::input('key', $default)`, `Request::file('key')`.
- Her endpoint için `Validator::make($input, $rules)` çağrılır.
- Rules örneği: `['email' => 'required|email|max:191', 'age' => 'nullable|int|min:0|max:120']`.

---

## 2. CSRF

- Session start ile birlikte token üretilir (`bin2hex(random_bytes(32))`).
- Her HTML formda `<?= csrf() ?>` → gizli input.
- `CsrfMiddleware` state-changing method'larda (POST/PUT/DELETE/PATCH) token karşılaştırır (`hash_equals`).
- API için header: `X-CSRF-TOKEN`.

---

## 3. XSS

- View auto-escape default: `<?= $var ?>` çıktısı `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- Ham HTML: `<?= $view->raw($var) ?>` — sadece güvenilir kaynak (admin girişi olan zengin metin için whitelisted HTML purifier'dan geçmiş).
- `Content-Security-Policy` header:
  ```
  default-src 'self';
  script-src 'self' 'nonce-<random>' https://www.google.com/recaptcha/;
  style-src 'self' 'unsafe-inline';    # kritik CSS için (nonce ile daraltılır)
  img-src 'self' data: https:;
  connect-src 'self' https://www.paytr.com;
  frame-src https://www.google.com/recaptcha/;
  ```

---

## 4. SQL Injection

- Sadece PDO prepared statements.
- Query builder helper:
  ```php
  $db->select('SELECT * FROM users WHERE id = ?', [$id]);
  $db->insert('users', ['email' => $email, 'name' => $name]);
  ```
- **Repository katmanı zorunlu** — controller içinde SQL yok.

---

## 5. Session Güvenliği

`config/session.php`:
```php
return [
    'cookie_name'    => 'aho_session',
    'cookie_domain'  => null,
    'cookie_secure'  => true,      // production
    'cookie_httponly'=> true,
    'cookie_samesite'=> 'Lax',
    'lifetime'       => 7200,      // 2 saat
    'gc_maxlifetime' => 7200,
    'regenerate_on_login' => true,
    'storage'        => 'file',    // file / redis
    'path'           => 'storage/sessions',
];
```

- Login'de `session_regenerate_id(true)` — session fixation koruması.
- Logout'ta `session_destroy()` + cookie sil.
- IP değişimi tespit → tekrar login iste (opsiyonel katı mod).

---

## 6. Şifre

- **Bcrypt** cost 12 (`PASSWORD_BCRYPT`).
- Minimum: 10 karakter, en az 1 harf + 1 rakam. (Admin: 12 karakter + karışık.)
- Şifre değişince tüm oturumlar iptal (`customer_sessions` tablosundan silinir).
- Şifre sıfırlama: 30 dk geçerli, tek kullanımlık token.

---

## 7. 2FA (TOTP)

- Kütüphane: `pragmarx/google2fa` (Composer).
- QR kod: `bacon/bacon-qr-code`.
- Secret AES-256-GCM ile şifreli DB'de.
- Backup kodlar: 8 adet, bcrypt hash'li.
- Admin için önerilir (uyarı gösterilir), müşteri için opsiyonel.

---

## 8. RBAC (Rol Tabanlı Erişim)

- Roller: `super_admin`, `admin`, `support`, `accounting`, `marketplace_seller`, `customer`.
- İzin key formatı: `<modul>.<aksiyon>` — örn. `customers.view`, `products.edit`.
- Middleware: `RbacMiddleware::require('customers.edit')`.
- Controller: `$this->authorize('customers.edit')`.
- View: `@can('customers.edit') ... @endcan`.
- `super_admin` tüm izinlere sahip, override mümkün değil (kaldırılamaz).

---

## 9. Rate Limiting

`RateLimitMiddleware`:
- Login: 5 deneme / 15 dk / IP+email.
- Şifre sıfırlama: 3 istek / saat / email.
- API: 60 istek / dk / API key.
- Public form: 10 gönderim / dk / IP.
- Aşım → 429 + Retry-After header.

Backend: Redis (varsa) veya dosya tabanlı (`storage/cache/rate-limits/`).

---

## 10. Brute-Force Koruması

- Aynı email 5. hatadan sonra CAPTCHA zorunlu.
- 10. hatadan sonra 15 dk lock.
- Şüpheli login (yeni cihaz/IP) → müşteri e-postasına uyarı + isteğe bağlı 2FA challenge.

---

## 11. reCAPTCHA

- Public formlar (kayıt, ticket, iletişim) — reCAPTCHA v3 (score ≥ 0.5).
- Login — v2 (5 hatadan sonra).

---

## 12. Dosya Yükleme

`FileValidator`:
- Whitelist MIME (`image/jpeg`, `image/png`, `image/webp`, `application/pdf`, `application/zip`).
- Max boyut modül bazlı (`config/uploads.php`).
- Yüklenen dosya için `finfo_file` gerçek MIME kontrolü (uzantı yalan söyler).
- Dosya adı `Str::random(16) . '.' . $safeExt`.
- Web-erişilebilir uploads → `public/uploads/`. Özel uploads → `storage/uploads/private/` (sadece controller aracılığıyla stream).
- SVG upload YASAK (varsayılan) — XSS riski.
- Görsel işleme: `intervention/image` ile yeniden encode et (metadata ve exploit temizlenir).

---

## 13. API Key Yönetimi

- `settings` ve `registrar_configs` içinde `is_encrypted=true` alanlar AES-256-GCM ile şifreli.
- Admin UI'da maskeleme: `****...abc4`.
- API log'larda **hiçbir zaman** anahtar loglanmaz (`api_key_masker` middleware).

---

## 14. Güvenlik Başlıkları (SecurityHeadersMiddleware)

```
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
X-XSS-Protection: 0        # modern tarayıcılar CSP kullanır
Content-Security-Policy: (yukarı)
```

`.htaccess` ile de dublelenir.

---

## 15. Audit Log

- Her admin işlemi `audit_logs`'a yazılır: kim, ne yaptı, önce ne vardı, sonra ne var.
- Kritik: müşteri veri değişikliği, silme, ödeme yaklaşımı, izin değişikliği.
- Hassas alanlar (şifre, kart) maskeli loglanır.

---

## 16. Hata Mesajları

- Production: kullanıcıya "İşlem tamamlanamadı" + hata ID (`err_a1b2c3`).
- Log dosyasında tam trace + hata ID.
- Login hatası **spesifik değil**: "E-posta veya şifre hatalı." (username enumeration önlem).

---

## 17. Dependency Güvenliği

- `composer audit` CI'da otomatik.
- Bağımlılık az tut. Her yeni paket = güvenlik yüzeyi.

---

## 18. HTTPS

- Production: HTTP → HTTPS zorunlu 301.
- HSTS preload eligible.
- Session cookie `Secure`.

---

## 19. GDPR / KVKK

- Çerez izni onaysız analytics YOK (şartname 8).
- Müşteri "verilerimi indir" ve "hesabımı sil" hakkına sahip (Faz 5).
- IP ve UA analytics'te hash'lenir.
- Log retention: audit 2 yıl, api 90 gün, cookie analytics 12 ay.

---

## 20. Backup

- Cron: günlük DB dump + `storage/uploads/` snapshot → `storage/backups/`.
- Retention: 7 günlük + 4 haftalık + 6 aylık.
- Şifreli tar.gz. AES-256.
- Restore procedure `docs/OPERATIONS.md`'de.

---

## 21. Penetrasyon Test Checklist'i (Yayın Öncesi)

- [ ] SQL injection (sqlmap ile örnek endpointler)
- [ ] XSS (stored + reflected)
- [ ] CSRF (form + JSON endpoint)
- [ ] Auth bypass (direct URL, role escalation)
- [ ] File upload (php/svg/exe deneme)
- [ ] Rate limit (Burp intruder)
- [ ] Session fixation
- [ ] Password reset token yeniden kullanım
- [ ] Timing attack (login response süresi sabit mi)
- [ ] API key sızıntısı (log/error/response)
