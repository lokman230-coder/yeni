<?php

declare(strict_types=1);

namespace App\Modules\Registrar\Drivers;

use App\Modules\Registrar\Contracts\RegistrarInterface;
use App\Services\Logger\ApiLogger;
use App\Services\Logger\Logger;

/**
 * DomainNameAPI (dm.domainnameapi.com) — Yeni REST API driver.
 *
 * Config anahtarları (Admin > Domain Center > Registrar > DomainNameAPI):
 *   - reseller_id     : Reseller ID
 *   - api_key         : API Key
 *   - test_mode       : 1 = sandbox, 0 = live
 *
 * Endpoint'ler (yeni panel https://dm.domainnameapi.com):
 *   - Live:    https://api.domainnameapi.com/v1
 *   - Sandbox: https://api-sandbox.domainnameapi.com/v1
 *
 * Auth: Her istekte X-Reseller-ID + X-API-Key header.
 *
 * SoapClient bağımlılığı YOK — pure cURL.
 * Fallback: API çağrısı fail ederse ManualDriver kullanılır (whois/dns için sistem araçları).
 */
final class DomainNameApiDriver implements RegistrarInterface
{
    private string $baseUrl;
    private ManualDriver $fallback;

    public function __construct(private array $config = [], private bool $testMode = true)
    {
        $this->baseUrl = $testMode
            ? 'https://api-sandbox.domainnameapi.com/v1'
            : 'https://api.domainnameapi.com/v1';

        // Config'te özel URL varsa onu kullan
        if (!empty($config['api_url'])) {
            $this->baseUrl = rtrim((string) $config['api_url'], '/');
        }

        $this->fallback = new ManualDriver($config, $testMode);
    }

    public function id(): string { return 'domainnameapi'; }
    public function label(): string { return 'DomainNameAPI' . ($this->testMode ? ' (Sandbox)' : ''); }

    // ═══════════════════════════════════════════════════════
    //  HTTP CORE — REST API çağrıları
    // ═══════════════════════════════════════════════════════

    private function request(string $method, string $path, array $body = [], array $query = []): array
    {
        $resellerId = trim((string) ($this->config['reseller_id'] ?? ''));
        $apiKey     = trim((string) ($this->config['api_key'] ?? ''));

        if ($resellerId === '' || $apiKey === '') {
            return ['ok' => false, 'error' => 'reseller_id ve api_key eksik. Admin > Domain Center > Registrar > DomainNameAPI\'den girin.'];
        }

        $url = $this->baseUrl . '/' . ltrim($path, '/');
        if ($query) $url .= '?' . http_build_query($query);

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Reseller-ID: ' . $resellerId,
            'X-API-Key: ' . $apiKey,
            'User-Agent: AhostBilisim/1.0',
        ];

        $ch = curl_init($url);
        $opts = [
            CURLOPT_CUSTOMREQUEST   => strtoupper($method),
            CURLOPT_HTTPHEADER      => $headers,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_SSL_VERIFYPEER  => true,
        ];
        if (in_array(strtoupper($method), ['POST','PUT','PATCH','DELETE'], true) && $body) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
        }
        curl_setopt_array($ch, $opts);

        $start = microtime(true);
        $responseBody = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        $duration = (int) ((microtime(true) - $start) * 1000);
        curl_close($ch);

        // API log
        try {
            ApiLogger::log('domainnameapi', $path, $method,
                $body ? json_encode($body) : null,
                is_string($responseBody) ? substr($responseBody, 0, 5000) : null,
                (int) $code, $duration
            );
        } catch (\Throwable) {}

        if ($responseBody === false) {
            return ['ok' => false, 'error' => 'HTTP hatası: ' . $err, 'http_code' => 0];
        }

        $decoded = json_decode((string) $responseBody, true);
        if ($code >= 400) {
            return [
                'ok'        => false,
                'error'     => $decoded['message'] ?? $decoded['error'] ?? ("HTTP $code"),
                'http_code' => $code,
                'response'  => $decoded,
            ];
        }

        return ['ok' => true, 'data' => $decoded ?? [], 'http_code' => $code];
    }

    // ═══════════════════════════════════════════════════════
    //  RegistrarInterface implementation
    // ═══════════════════════════════════════════════════════

    public function check(array $domains): array
    {
        $r = $this->request('POST', 'domain/check', ['domains' => $domains]);
        if (!$r['ok']) {
            Logger::warning('DomainNameAPI check failed, using fallback: ' . ($r['error'] ?? '?'));
            return $this->fallback->check($domains);
        }

        // Yanıt normalize et
        $result = [];
        foreach ($r['data']['domains'] ?? $r['data'] ?? [] as $d) {
            $name = $d['domain'] ?? $d['name'] ?? '';
            if (!$name) continue;
            $result[$name] = [
                'available' => (bool) ($d['available'] ?? false),
                'premium'   => (bool) ($d['premium'] ?? false),
                'price'     => isset($d['price']) ? (float) $d['price'] : null,
                'currency'  => (string) ($d['currency'] ?? 'TRY'),
            ];
        }
        return $result;
    }

    public function whois(string $domain): array
    {
        $r = $this->request('GET', 'domain/whois', [], ['domain' => $domain]);
        if (!$r['ok']) return $this->fallback->whois($domain);

        $data = $r['data'];
        return [
            'domain'        => $domain,
            'registrar'     => $data['registrar'] ?? null,
            'created_at'    => $data['created_date'] ?? $data['created_at'] ?? null,
            'updated_at'    => $data['updated_date'] ?? null,
            'expires_at'    => $data['expires_date'] ?? $data['expiry'] ?? null,
            'status'        => $data['status'] ?? null,
            'nameservers'   => $data['nameservers'] ?? [],
            'dnssec'        => $data['dnssec'] ?? null,
            'whois_privacy' => (bool) ($data['privacy'] ?? false),
            'raw'           => $data,
        ];
    }

    public function dnsRecords(string $domain): array
    {
        $r = $this->request('GET', 'domain/dns', [], ['domain' => $domain]);
        if (!$r['ok']) return $this->fallback->dnsRecords($domain);
        return $r['data']['records'] ?? $r['data'] ?? [];
    }

    public function register(string $domain, int $years, array $contact, array $nameservers = []): array
    {
        $r = $this->request('POST', 'domain/register', [
            'domain'      => $domain,
            'period'      => $years,
            'contact'     => $contact,
            'nameservers' => $nameservers ?: ['ns1.ahost.web.tr', 'ns2.ahost.web.tr'],
        ]);

        if (!$r['ok']) {
            return ['success' => false, 'message' => $r['error'] ?? 'Kayıt başarısız', 'data' => $r['response'] ?? null];
        }

        return [
            'success'          => true,
            'message'          => 'Domain kaydedildi',
            'domain'           => $domain,
            'expires_at'       => $r['data']['expires_date'] ?? null,
            'registrar_domain_id' => $r['data']['domain_id'] ?? null,
        ];
    }

    public function transfer(string $domain, string $eppCode, array $contact): array
    {
        $r = $this->request('POST', 'domain/transfer', [
            'domain'    => $domain,
            'epp_code'  => $eppCode,
            'contact'   => $contact,
        ]);
        return [
            'success' => $r['ok'],
            'message' => $r['ok'] ? 'Transfer başlatıldı' : ($r['error'] ?? 'Transfer başarısız'),
            'data'    => $r['data'] ?? null,
        ];
    }

    public function renew(string $domain, int $years): array
    {
        $r = $this->request('POST', 'domain/renew', [
            'domain' => $domain,
            'period' => $years,
        ]);
        return [
            'success'    => $r['ok'],
            'message'    => $r['ok'] ? 'Yenilendi' : ($r['error'] ?? 'Yenileme başarısız'),
            'expires_at' => $r['data']['expires_date'] ?? null,
        ];
    }

    public function getEppCode(string $domain): string
    {
        $r = $this->request('GET', 'domain/epp', [], ['domain' => $domain]);
        if (!$r['ok']) return '';
        return (string) ($r['data']['epp_code'] ?? '');
    }

    public function setTransferLock(string $domain, bool $locked): bool
    {
        $r = $this->request('POST', 'domain/transfer-lock', [
            'domain' => $domain,
            'locked' => $locked,
        ]);
        return $r['ok'];
    }

    public function updateNameservers(string $domain, array $nameservers): bool
    {
        $r = $this->request('POST', 'domain/nameservers', [
            'domain'      => $domain,
            'nameservers' => $nameservers,
        ]);
        return $r['ok'];
    }

    public function info(string $domain): array
    {
        $r = $this->request('GET', 'domain/info', [], ['domain' => $domain]);
        if (!$r['ok']) return $this->fallback->info($domain);
        return $r['data'] ?? [];
    }

    // ═══════════════════════════════════════════════════════
    //  Ekstra — fiyat çekme (TLD Fiyat listesi otomatik güncelleme)
    // ═══════════════════════════════════════════════════════

    /**
     * TLD fiyat listesini çeker (register/renew/transfer)
     * @return array<string, array{register:float, renew:float, transfer:float, currency:string}>
     */
    public function tldPrices(): array
    {
        $r = $this->request('GET', 'pricing/tlds');
        if (!$r['ok']) return [];

        $result = [];
        foreach ($r['data']['tlds'] ?? $r['data'] ?? [] as $t) {
            $tld = ltrim((string) ($t['tld'] ?? ''), '.');
            if (!$tld) continue;
            $result[$tld] = [
                'register' => (float) ($t['register'] ?? $t['price'] ?? 0),
                'renew'    => (float) ($t['renew'] ?? $t['price'] ?? 0),
                'transfer' => (float) ($t['transfer'] ?? $t['price'] ?? 0),
                'currency' => (string) ($t['currency'] ?? 'TRY'),
            ];
        }
        return $result;
    }

    /** Bakiye sorgusu (reseller hesabı) */
    public function balance(): array
    {
        $r = $this->request('GET', 'account/balance');
        if (!$r['ok']) return ['ok' => false, 'error' => $r['error'] ?? '?'];
        return [
            'ok'       => true,
            'balance'  => (float) ($r['data']['balance'] ?? 0),
            'currency' => (string) ($r['data']['currency'] ?? 'TRY'),
        ];
    }
}
