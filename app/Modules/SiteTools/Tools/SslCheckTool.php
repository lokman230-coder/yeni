<?php
declare(strict_types=1);
namespace App\Modules\SiteTools\Tools;

use App\Modules\Domain\Services\SslService;

final class SslCheckTool extends AbstractTool {
    public function slug(): string { return 'ssl-kontrol'; }
    public function label(): string { return 'SSL Sertifika Kontrolü'; }
    public function description(): string { return 'Sertifikanın issuer, geçerlilik, kalan gün ve CN bilgileri.'; }
    public function icon(): string { return '🔒'; }
    public function inputPlaceholder(): string { return 'ornekdomain.com'; }
    public function run(string $input): array {
        $host = self::normalizeHost($input);
        return ['success' => true, 'data' => SslService::check($host), 'render' => 'ssl'];
    }
}
