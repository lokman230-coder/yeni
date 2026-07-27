<?php
declare(strict_types=1);
namespace App\Modules\SiteTools\Tools;

use App\Modules\Domain\Services\SslService;
use App\Modules\Domain\Services\ValuationService;
use App\Modules\Registrar\Drivers\ManualDriver;

final class DomainValueTool extends AbstractTool {
    public function slug(): string { return 'domain-degerleme'; }
    public function label(): string { return 'Domain Değerleme'; }
    public function description(): string { return 'TLD, uzunluk, marka gücü, yaş, SEO sinyalleri ile piyasa değeri tahmini.'; }
    public function icon(): string { return '💎'; }
    public function inputPlaceholder(): string { return 'ornekdomain.com'; }
    public function run(string $input): array {
        $host = self::normalizeHost($input);
        $registrar = new ManualDriver();
        $whois = $registrar->whois($host);
        $dns = $registrar->dnsRecords($host);
        $ssl = SslService::check($host);
        return ['success' => true, 'data' => ValuationService::evaluate($host, $whois, $dns, $ssl), 'render' => 'valuation'];
    }
}
