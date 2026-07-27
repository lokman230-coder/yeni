<?php
declare(strict_types=1);
namespace App\Modules\SiteTools\Tools;

final class RobotsCheckTool extends AbstractTool {
    public function slug(): string { return 'robots-txt'; }
    public function label(): string { return 'robots.txt Kontrolü'; }
    public function description(): string { return 'robots.txt varlığı, User-agent kuralları, Sitemap linki.'; }
    public function icon(): string { return '🤖'; }
    public function inputPlaceholder(): string { return 'ornekdomain.com'; }
    public function run(string $input): array {
        $host = self::normalizeHost($input);
        $url = "https://{$host}/robots.txt";
        $res = self::fetch($url, 8);
        if (!$res['success']) return ['success' => false, 'error' => $res['error']];

        $exists = $res['http_code'] === 200;
        $body = $exists ? $res['body'] : '';
        $userAgents = preg_match_all('/^\s*User-agent:\s*(.+)$/mi', $body, $m1) ? array_map('trim', $m1[1]) : [];
        $disallow = preg_match_all('/^\s*Disallow:\s*(.+)$/mi', $body, $m2) ? array_map('trim', $m2[1]) : [];
        $sitemap = preg_match_all('/^\s*Sitemap:\s*(.+)$/mi', $body, $m3) ? array_map('trim', $m3[1]) : [];

        return ['success' => true, 'data' => [
            'url' => $url,
            'exists' => $exists,
            'http_code' => $res['http_code'],
            'user_agents' => array_values(array_unique($userAgents)),
            'disallow_count' => count($disallow),
            'sitemap_urls' => $sitemap,
            'raw' => mb_substr($body, 0, 4000),
        ], 'render' => 'robots'];
    }
}
