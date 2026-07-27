# 07 · Entegrasyon API Sözleşmeleri

Bu dokümanda her dış servis için **arayüz (interface)** tanımlanır. Sürücüler (driver)
bu arayüze uyar. Uygulama katmanı somut sürücüyü değil arayüzü bilir. Böylece bir
sağlayıcıyı değiştirmek = yeni bir driver eklemek.

---

## 1. Payment Gateway

### 1.1 Interface

```php
namespace App\Modules\Payment\Contracts;

interface PaymentGatewayInterface
{
    public function id(): string;                               // 'paytr'
    public function label(): string;                            // 'PayTR Kredi Kartı'
    public function supportsRefund(): bool;
    public function createCheckout(PaymentRequest $req): PaymentSession;
    public function handleCallback(array $payload): PaymentResult;
    public function verify(string $transactionId): PaymentStatus;
    public function refund(string $transactionId, float $amount): RefundResult;
}
```

### 1.2 PayTR Driver

**Config:** merchant_id, merchant_key, merchant_salt, test_mode (bool)

**Endpoint:** `https://www.paytr.com/odeme/api/get-token`

**Akış:**
1. `createCheckout()` → PayTR'a token isteği, iframe URL üretir.
2. Müşteri PayTR sayfasına yönlendirilir (veya iframe).
3. Ödeme sonrası PayTR arka planda `/payment/paytr/callback` POST eder.
4. Merchant OK / Merchant FAIL response.
5. Front-end `success_url` / `fail_url` ile geri döner.

**Callback güvenliği:** `hash_hmac('sha256', $merchant_oid . $merchant_salt . $status . $total_amount, $merchant_key)` — imzayı doğrula.

**Kritik:** Ödeme başarılı olarak yalnızca callback OK ise işaretlenir; front redirect'e güvenilmez.

### 1.3 Diğer Driver'lar
- `BankTransferDriver` — manuel onay
- `BalanceDriver` — müşteri kredisinden düş
- `ManualDriver` — admin panelinden elle işaretle

---

## 2. Domain Registrar

### 2.1 Interface

```php
namespace App\Modules\Registrar\Contracts;

interface RegistrarInterface
{
    public function id(): string;
    public function check(array $domains): array;           // ['example.com' => ['available' => true, 'price' => ...]]
    public function register(string $domain, int $years, ContactInfo $contact, array $nameservers): RegistrationResult;
    public function transfer(string $domain, string $eppCode, ContactInfo $contact): TransferResult;
    public function renew(string $domain, int $years): RenewalResult;
    public function getEppCode(string $domain): string;
    public function setTransferLock(string $domain, bool $locked): bool;
    public function updateNameservers(string $domain, array $nameservers): bool;
    public function updateContact(string $domain, ContactInfo $contact): bool;
    public function getInfo(string $domain): DomainInfo;
    public function whois(string $domain): WhoisData;
    public function dnsRecords(string $domain): array;      // opsiyonel — desteklemiyorsa []
}
```

### 2.2 DomainNameAPI Driver

- SOAP endpoint (WSDL) veya REST — güncel: SOAP.
- Auth: username + password.
- Test / Live mode.
- Her method call `api_logs`'a request+response yazar (API key maskeli).
- Rate limit: sağlayıcı 60/dk — cache + queue.

### 2.3 Whois Fallback

Registrar API whois döndürmezse `whois` CLI (varsa) veya `iana-whois` HTTP proxy'sine düş — yine yoksa "veri bulunamadı" göster (şartname madde 16 — "sinyali yok" gibi belirsiz metin yok, açık dil kullan).

---

## 3. Hosting Panel

### 3.1 Interface

```php
namespace App\Modules\Hosting\Contracts;

interface HostingPanelInterface
{
    public function id(): string;
    public function createAccount(AccountRequest $req): AccountResult;
    public function suspendAccount(string $username, string $reason = ''): bool;
    public function unsuspendAccount(string $username): bool;
    public function terminateAccount(string $username): bool;
    public function changePassword(string $username, string $newPassword): bool;
    public function changePackage(string $username, string $newPackage): bool;
    public function applyAddon(string $username, string $addonCode, array $params): bool;
    public function getUsage(string $username): UsageStats;
    public function testConnection(): ConnectionResult;
}
```

### 3.2 cPanel Driver
- WHM API v1 (JSON), token auth.
- Endpoint: `https://server:2087/json-api/...`
- SSL sertifika kontrolü zorunlu.

### 3.3 DirectAdmin Driver
- API endpoint: `https://server:2222/CMD_API_...`
- Basic auth (username + password veya login key).

### 3.4 Plesk Driver
- XML-RPC API — `https://server:8443/api/v2/...` (v2 REST tercih).
- API key auth.

### 3.5 ManualDriver
- Admin panelinden elle "hesap açıldı" işaretler.
- Otomasyon çalışmaz ama flow devam eder.

---

## 4. Mail (SMTP)

### 4.1 Interface

```php
interface MailerInterface {
    public function send(Mail $mail): MailResult;
    public function queue(Mail $mail): void;
    public function test(): bool;
}
```

- Kütüphane: `PHPMailer` (composer).
- Kuyruk: DB (`mail_queue` tablosu) + cron `ProcessMailQueueCommand`.
- Template: `email_templates` tablosu — `{{variable}}` placeholder.

---

## 5. SMS

### 5.1 Interface

```php
interface SmsGatewayInterface {
    public function id(): string;
    public function send(string $phone, string $message, ?string $sender = null): SmsResult;
    public function balance(): ?float;
}
```

### 5.2 NetGSM Driver (TR örneği)
- Endpoint: `https://api.netgsm.com.tr/sms/send/xml`
- Auth: username + password
- Sender name (başlık) admin ayarında.

Diğer sağlayıcılar (İletiMerkezi, Verimor) benzeri driver ile eklenir.

---

## 6. AI Provider

### 6.1 Interface

```php
interface AiProviderInterface {
    public function id(): string;
    public function chat(array $messages, array $options = []): AiResponse;
    public function embed(string $text): array;               // opsiyonel
    public function stream(array $messages, callable $onChunk, array $options = []): void;
}
```

### 6.2 Sürücüler
- `OpenAiDriver` — GPT-4o / mini
- `AnthropicDriver` — Claude
- `LocalDriver` — Ollama/local (self-hosted)

### 6.3 Bağlam ve Aksiyon Güvenliği
- `ContextBuilder` — public/customer/admin için sistem promptunu üretir.
- Ai aksiyonları `ai_actions` tablosunda tanımlı — AI serbest kod çalıştıramaz.
- Response içinde JSON `{"action": "cart_add", "params": {...}}` gelirse `ActionRunner` sadece izinli aksiyonları çalıştırır.

---

## 7. Kur (Exchange Rate)

### 7.1 Interface

```php
interface RateProviderInterface {
    public function rates(string $base = 'USD'): array;   // ['TRY' => 32.4, 'EUR' => 0.92, ...]
    public function updatedAt(): DateTimeImmutable;
}
```

### 7.2 Sağlayıcılar
- **TCMB** — resmi Merkez Bankası XML feed.
- **exchangerate.host** — ücretsiz REST.
- **Manual** — admin panelinden elle.

Kur cache: 1 saat. Cron her saat başı çalışır (`UpdateCurrencyRatesCommand`).

---

## 8. reCAPTCHA

```php
interface CaptchaVerifierInterface {
    public function verify(string $token, ?string $action = null, ?string $ip = null): CaptchaResult;
}
```

Google reCAPTCHA v3 (score based) + v2 (fallback).

---

## 9. API Log Formatı (tüm entegrasyonlar için ortak)

```
api_logs
├── integration     : 'paytr' | 'domainnameapi' | 'cpanel:server1' | 'openai'
├── endpoint        : URL veya method adı
├── method          : GET/POST/...
├── request_body    : JSON (secret maskeli)
├── response_body   : JSON (kısaltılmış)
├── http_code
├── duration_ms
├── error           : varsa
├── related_entity  : 'domain:example.com', 'invoice:123'
└── created_at
```

Admin panel API log ekranı: filtre + arama + detay modal + retry butonu.

---

## 10. Ahost Bilişim'ın Kendi REST API'ı (Faz 5)

`/api/v1/*` — token-based auth.

Örnek endpoint'ler:
- `POST /api/v1/domains/check`
- `POST /api/v1/orders`
- `GET  /api/v1/customers/{id}`
- `POST /api/v1/tickets`
- `GET  /api/v1/products`

Response format:
```json
{
  "success": true,
  "data": { ... },
  "meta": { "page": 1, "total": 42 }
}
```

Hata:
```json
{
  "success": false,
  "error": { "code": "VALIDATION_FAILED", "message": "...", "fields": {...} }
}
```

Rate limit + versioning + Swagger dokümanı Faz 5'te.
