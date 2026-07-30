<?php

declare(strict_types=1);

namespace App\Modules\Registrar\Drivers;

use App\Modules\Registrar\Contracts\RegistrarInterface;

/**
 * Manuel registrar sürücüsü.
 * API entegrasyonu olmadan; admin işlemleri elle yapar.
 * WHOIS/DNS için PHP çekirdek fonksiyonları kullanılır.
 */
final class ManualDriver implements RegistrarInterface
{
    public function __construct(private array $config = [], private bool $testMode = true) {}

    public function id(): string { return 'manual'; }
    public function label(): string { return 'Manuel Registrar (WHOIS + DNS)'; }

    public function check(array $domains): array
    {
        $out = [];
        foreach ($domains as $d) {
            $d = self::normalize($d);
            $out[$d] = ['available' => self::isLikelyAvailable($d)];
        }
        return $out;
    }

    private static function isLikelyAvailable(string $domain): bool
    {
        // DNS'e bak, kayıtlıysa müsait değil (basit heuristic)
        $records = @dns_get_record($domain, DNS_NS | DNS_A);
        return empty($records);
    }

    private static function normalize(string $domain): string
    {
        $d = strtolower(trim($domain));
        $d = preg_replace('#^https?://#', '', $d) ?? $d;
        $d = preg_replace('#^www\.#', '', $d) ?? $d;
        $d = rtrim($d, '/');
        return $d;
    }

    public function whois(string $domain): array
    {
        $domain = self::normalize($domain);
        // whois CLI varsa
        $out = @shell_exec('command -v whois >/dev/null 2>&1 && whois ' . escapeshellarg($domain) . ' 2>/dev/null');
        if (!$out) {
            return [
                'domain'          => $domain,
                'registrar'       => null,
                'registrant'      => null,
                'created'         => null,
                'updated'         => null,
                'expires'         => null,
                'nameservers'     => [],
                'transfer_lock'   => null,
                'update_lock'     => null,
                'delete_lock'     => null,
                'whois_privacy'   => null,
                'raw'             => null,
                'source'          => 'unavailable',
                'available'       => self::isLikelyAvailable($domain),
            ];
        }

        // Basit parse
        $data = [
            'domain'    => $domain,
            'registrar' => self::pick($out, ['Registrar:', 'Sponsoring Registrar:']),
            'created'   => self::pick($out, ['Creation Date:', 'Created On:', 'Created:']),
            'updated'   => self::pick($out, ['Updated Date:', 'Last Updated:', 'Modified:']),
            'expires'   => self::pick($out, ['Registry Expiry Date:', 'Registrar Registration Expiration Date:', 'Expiration Date:', 'Expiry Date:']),
            'registrant'=> self::pick($out, ['Registrant Organization:', 'Registrant Name:']),
        ];

        // Nameservers
        preg_match_all('/Name Server:\s*(\S+)/i', $out, $m);
        $data['nameservers'] = array_values(array_unique(array_map('strtolower', $m[1] ?? [])));

        // Status flags
        $statusText = strtolower(implode(' ', self::pickAll($out, 'Domain Status:')));
        $data['transfer_lock'] = str_contains($statusText, 'transferprohibited') ? true : (str_contains($statusText, 'ok') ? false : null);
        $data['update_lock']   = str_contains($statusText, 'updateprohibited') ? true : null;
        $data['delete_lock']   = str_contains($statusText, 'deleteprohibited') ? true : null;
        $data['whois_privacy'] = (stripos($out, 'privacy') !== false || stripos($out, 'redacted') !== false) ? true : null;

        $data['raw']       = $out;
        $data['source']    = 'whois-cli';
        $data['available'] = false;

        return $data;
    }

    private static function pick(string $text, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (preg_match('/' . preg_quote($k, '/') . '\s*(.+)/i', $text, $m)) {
                $val = trim($m[1]);
                if ($val !== '') return $val;
            }
        }
        return null;
    }

    private static function pickAll(string $text, string $key): array
    {
        preg_match_all('/' . preg_quote($key, '/') . '\s*(.+)/i', $text, $m);
        return array_map('trim', $m[1] ?? []);
    }

    public function dnsRecords(string $domain): array
    {
        $domain = self::normalize($domain);
        $out = [];
        $typeMap = [
            'A'     => DNS_A,
            'AAAA'  => DNS_AAAA,
            'MX'    => DNS_MX,
            'TXT'   => DNS_TXT,
            'NS'    => DNS_NS,
            'CNAME' => DNS_CNAME,
            'CAA'   => 8192, // DNS_CAA (PHP 7.0.16+)
            'SOA'   => DNS_SOA,
        ];
        foreach ($typeMap as $label => $flag) {
            $records = @dns_get_record($domain, $flag);
            if (!$records) { $out[$label] = []; continue; }
            $out[$label] = array_map(function ($r) use ($label) {
                return match ($label) {
                    'A'     => ['host' => $r['host'], 'ttl' => $r['ttl'] ?? null, 'value' => $r['ip'] ?? null],
                    'AAAA'  => ['host' => $r['host'], 'ttl' => $r['ttl'] ?? null, 'value' => $r['ipv6'] ?? null],
                    'MX'    => ['host' => $r['host'], 'ttl' => $r['ttl'] ?? null, 'priority' => $r['pri'] ?? null, 'value' => $r['target'] ?? null],
                    'TXT'   => ['host' => $r['host'], 'ttl' => $r['ttl'] ?? null, 'value' => $r['txt'] ?? implode(' ', $r['entries'] ?? [])],
                    'NS'    => ['host' => $r['host'], 'ttl' => $r['ttl'] ?? null, 'value' => $r['target'] ?? null],
                    'CNAME' => ['host' => $r['host'], 'ttl' => $r['ttl'] ?? null, 'value' => $r['target'] ?? null],
                    'SOA'   => ['host' => $r['host'], 'value' => ($r['mname'] ?? '') . ' ' . ($r['rname'] ?? '')],
                    default => ['host' => $r['host'] ?? '', 'value' => json_encode($r)],
                };
            }, $records);
        }
        return $out;
    }

    public function register(string $domain, int $years, array $contact, array $nameservers = []): array
    { return ['success' => false, 'message' => 'Manuel registrar: kayıt admin tarafından yapılır.']; }
    public function transfer(string $domain, string $eppCode, array $contact): array
    { return ['success' => false, 'message' => 'Manuel registrar: transfer admin tarafından yapılır.']; }
    public function renew(string $domain, int $years): array
    { return ['success' => false, 'message' => 'Manuel registrar: yenileme admin tarafından yapılır.']; }
    public function getEppCode(string $domain): string { return ''; }
    public function setTransferLock(string $domain, bool $locked): bool { return false; }
    public function updateNameservers(string $domain, array $nameservers): bool { return false; }

    public function info(string $domain): array
    {
        $w = $this->whois($domain);
        return [
            'domain'      => $w['domain'],
            'registrar'   => $w['registrar'] ?? null,
            'created'     => $w['created'] ?? null,
            'expires'     => $w['expires'] ?? null,
            'nameservers' => $w['nameservers'] ?? [],
        ];
    }
}
