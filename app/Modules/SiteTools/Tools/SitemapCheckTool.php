<?php
declare(strict_types=1);
namespace App\Modules\SiteTools\Tools;

final class SitemapCheckTool extends AbstractTool {
    public function slug(): string { return 'sitemap'; }
    public function label(): string { return 'Sitemap Kontrolü'; }
    public function description(): string { return 'sitemap.xml varlığı ve içindeki URL sayısı.'; }
    public function icon(): string { return '🗺️'; }
    public function inputPlaceholder(): string { return 'ornekdomain.com'; }
    public function run(string $input): array {
        $host = self::normalizeHost($input);
        $urls = [
            "https://{$host}/sitemap.xml",
            "https://{$host}/sitemap_index.xml",
        ];
        foreach ($urls as $url) {
            $res = self::fetch($url, 8);
            if ($res['success'] && $res['http_code'] === 200 && str_contains(strtolower($res['body']), '<url')) {
                $count = preg_match_all('/<loc>/i', $res['body']);
                $sitemapCount = preg_match_all('/<sitemap>/i', $res['body']);
                return ['success' => true, 'data' => [
                    'url' => $url,
                    'exists' => true,
                    'url_count' => $count,
                    'sub_sitemap_count' => $sitemapCount,
                    'size_kb' => (int) round($res['size_bytes'] / 1024),
                ], 'render' => 'sitemap'];
            }
        }
        return ['success' => true, 'data' => [
            'url' => "https://{$host}/sitemap.xml",
            'exists' => false,
            'url_count' => 0,
        ], 'render' => 'sitemap'];
    }
}
