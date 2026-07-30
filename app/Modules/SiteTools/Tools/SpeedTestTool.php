<?php
declare(strict_types=1);
namespace App\Modules\SiteTools\Tools;

final class SpeedTestTool extends AbstractTool {
    public function slug(): string { return 'hiz-testi'; }
    public function label(): string { return 'Site Hız Testi'; }
    public function description(): string { return 'TTFB, toplam yükleme, sayfa boyutu ve performans önerileri.'; }
    public function icon(): string { return '⚡'; }
    public function inputPlaceholder(): string { return 'https://ornekdomain.com'; }
    public function run(string $input): array {
        $url = self::normalizeUrl($input);
        $res = self::fetch($url, 20);
        if (!$res['success']) return ['success' => false, 'error' => $res['error']];

        $sizeKb = (int) round($res['size_bytes'] / 1024);
        $totalMs = (int) round(($res['total_time_s'] ?? 0) * 1000);
        $grade = match (true) {
            $totalMs < 500  => 'A',
            $totalMs < 1000 => 'B',
            $totalMs < 2000 => 'C',
            $totalMs < 4000 => 'D',
            default         => 'F',
        };

        $suggestions = [];
        if ($sizeKb > 2000) $suggestions[] = 'Sayfa boyutu 2 MB üstünde — görselleri sıkıştırın (WebP, lazy load).';
        if ($totalMs > 2000) $suggestions[] = 'TTFB yüksek — sunucu hızı, cache, CDN kullanımı gözden geçirin.';
        if (empty($res['headers']['Content-Encoding'] ?? null)) $suggestions[] = 'gzip/brotli sıkıştırma yok — sunucuda etkinleştirin.';
        if (empty($res['headers']['Cache-Control'] ?? null)) $suggestions[] = 'Cache-Control header yok — tarayıcı önbelleği kullanılmıyor.';
        if (!$suggestions) $suggestions[] = 'Harika! Kritik bir performans sorunu tespit edilmedi.';

        return ['success' => true, 'data' => [
            'url' => $res['url'],
            'http_code' => $res['http_code'],
            'total_ms' => $totalMs,
            'size_kb' => $sizeKb,
            'grade' => $grade,
            'suggestions' => $suggestions,
            'gzip' => $res['headers']['Content-Encoding'] ?? '—',
            'cache_control' => $res['headers']['Cache-Control'] ?? '—',
        ], 'render' => 'speed'];
    }
}
