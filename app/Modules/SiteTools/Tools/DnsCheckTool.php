<?php
declare(strict_types=1);
namespace App\Modules\SiteTools\Tools;

use App\Modules\Registrar\Drivers\ManualDriver;

final class DnsCheckTool extends AbstractTool {
    public function slug(): string { return 'dns-kontrol'; }
    public function label(): string { return 'DNS Kayıt Kontrolü'; }
    public function description(): string { return 'A, AAAA, MX, TXT, NS, CNAME, CAA kayıtlarını listeler.'; }
    public function icon(): string { return '🌐'; }
    public function inputPlaceholder(): string { return 'ornekdomain.com'; }
    public function run(string $input): array {
        $host = self::normalizeHost($input);
        $driver = new ManualDriver();
        return ['success' => true, 'data' => $driver->dnsRecords($host), 'render' => 'dns'];
    }
}
