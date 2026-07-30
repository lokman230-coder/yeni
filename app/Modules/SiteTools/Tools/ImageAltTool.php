<?php
declare(strict_types=1);
namespace App\Modules\SiteTools\Tools;

final class ImageAltTool extends AbstractTool {
    public function slug(): string { return 'gorsel-alt'; }
    public function label(): string { return 'Görsel Alt Analizi'; }
    public function description(): string { return 'Sayfadaki görsellerin alt metinlerini kontrol eder.'; }
    public function icon(): string { return '🖼️'; }
    public function inputPlaceholder(): string { return 'https://ornekdomain.com'; }
    public function run(string $input): array {
        $url = self::normalizeUrl($input);
        $res = self::fetch($url, 10);
        if (!$res['success']) return ['success' => false, 'error' => $res['error']];

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($res['body'], LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xp = new \DOMXPath($dom);

        $imgs = $xp->query('//img');
        $list = []; $noAlt = 0;
        foreach ($imgs as $img) {
            $src = $img->getAttribute('src');
            $alt = trim($img->getAttribute('alt'));
            if ($alt === '') $noAlt++;
            $list[] = ['src' => mb_substr($src, 0, 120), 'alt' => $alt, 'has_alt' => $alt !== ''];
        }
        return ['success' => true, 'data' => [
            'url' => $res['url'],
            'total' => count($list),
            'no_alt' => $noAlt,
            'coverage_percent' => count($list) > 0 ? (int) round((count($list) - $noAlt) * 100 / count($list)) : 100,
            'images' => array_slice($list, 0, 50),
        ], 'render' => 'images'];
    }
}
