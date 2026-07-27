<?php
declare(strict_types=1);
namespace App\Modules\SiteTools\Tools;

use App\Modules\Registrar\Drivers\ManualDriver;

final class WhoisTool extends AbstractTool {
    public function slug(): string { return 'whois'; }
    public function label(): string { return 'WHOIS Sorgulama'; }
    public function description(): string { return 'Domain sahibi, kayıt tarihi, süresi ve nameserver bilgileri.'; }
    public function icon(): string { return '🔍'; }
    public function inputPlaceholder(): string { return 'ornekdomain.com'; }
    public function run(string $input): array {
        $host = self::normalizeHost($input);
        $driver = new ManualDriver();
        $w = $driver->whois($host);
        return ['success' => true, 'data' => $w, 'render' => 'whois'];
    }
}
