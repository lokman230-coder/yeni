<?php
declare(strict_types=1);
namespace App\Modules\SiteTools\Tools;

final class IpLookupTool extends AbstractTool {
    public function slug(): string { return 'ip-lookup'; }
    public function label(): string { return 'IP Lookup'; }
    public function description(): string { return 'IP veya domain’in konumu, ISP, ASN bilgileri.'; }
    public function icon(): string { return '📍'; }
    public function inputPlaceholder(): string { return '8.8.8.8 veya ornekdomain.com'; }
    public function run(string $input): array {
        $input = trim($input);
        $ip = filter_var($input, FILTER_VALIDATE_IP) ? $input : gethostbyname(self::normalizeHost($input));
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['success' => false, 'error' => 'Geçerli IP veya domain girin.'];
        }

        // ip-api.com ücretsiz — HTTP (443 için pro), ama request/dk sınırlı
        $res = self::fetch("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,zip,lat,lon,isp,org,as,query", 8);
        $data = ['ip' => $ip];
        if ($res['success']) {
            $json = json_decode($res['body'], true) ?: [];
            $data = array_merge($data, $json);
        }
        return ['success' => true, 'data' => $data, 'render' => 'ip-lookup'];
    }
}
