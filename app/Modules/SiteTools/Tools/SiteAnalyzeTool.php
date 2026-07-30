<?php
declare(strict_types=1);
namespace App\Modules\SiteTools\Tools;

use App\Modules\Domain\Services\SslService;

final class SiteAnalyzeTool extends AbstractTool {
    public function slug(): string { return 'site-analiz'; }
    public function label(): string { return 'Site Analizi'; }
    public function description(): string { return 'SSL, DNS, HTTP status, sunucu yanıt süresi, güvenlik başlıkları.'; }
    public function icon(): string { return '🩺'; }
    public function inputPlaceholder(): string { return 'https://ornekdomain.com'; }
    public function run(string $input): array {
        $url = self::normalizeUrl($input);
        $host = self::normalizeHost($input);
        $res = self::fetch($url, 12);
        $ssl = SslService::check($host);

        $server = $res['headers']['Server'] ?? '—';
        $poweredBy = $res['headers']['X-Powered-By'] ?? '—';
        $status = $res['success'] ? $res['http_code'] : 'ERR';
        $ttfbMs = (int) (($res['total_time_s'] ?? 0) * 1000);

        return ['success' => true, 'data' => [
            'url' => $res['url'] ?? $url,
            'http_code' => $status,
            'ttfb_ms' => $ttfbMs,
            'server' => $server,
            'powered_by' => $poweredBy,
            'ssl_active' => (bool) ($ssl['active'] ?? false),
            'ssl_days_left' => $ssl['days_left'] ?? null,
            'size_kb' => (int) round(($res['size_bytes'] ?? 0) / 1024),
            'content_type' => $res['headers']['Content-Type'] ?? '—',
            'headers_count' => count($res['headers'] ?? []),
        ], 'render' => 'site-analyze'];
    }
}
