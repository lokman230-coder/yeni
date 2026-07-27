<?php
declare(strict_types=1);
namespace App\Modules\SiteTools\Tools;

final class LinkAnalyzeTool extends AbstractTool {
    public function slug(): string { return 'link-analiz'; }
    public function label(): string { return 'Link Analizi'; }
    public function description(): string { return 'İç ve dış linkler, nofollow, kırık link tespiti.'; }
    public function icon(): string { return '🔗'; }
    public function inputPlaceholder(): string { return 'https://ornekdomain.com'; }
    public function run(string $input): array {
        $url = self::normalizeUrl($input);
        $res = self::fetch($url, 12);
        if (!$res['success']) return ['success' => false, 'error' => $res['error']];

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($res['body'], LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xp = new \DOMXPath($dom);

        $host = parse_url($res['url'], PHP_URL_HOST);
        $int = []; $ext = []; $nofollow = 0;
        foreach ($xp->query('//a[@href]') as $a) {
            $href = trim($a->getAttribute('href'));
            if ($href === '' || $href[0] === '#') continue;
            $abs = self::absolutize($href, $res['url']);
            $linkHost = parse_url($abs, PHP_URL_HOST);
            $isInt = !$linkHost || $linkHost === $host;
            $rel = strtolower($a->getAttribute('rel'));
            if (str_contains($rel, 'nofollow')) $nofollow++;
            if ($isInt) $int[] = $abs; else $ext[] = $abs;
        }
        return ['success' => true, 'data' => [
            'url' => $res['url'],
            'internal_count' => count($int),
            'external_count' => count($ext),
            'nofollow_count' => $nofollow,
            'internal' => array_slice(array_values(array_unique($int)), 0, 30),
            'external' => array_slice(array_values(array_unique($ext)), 0, 30),
        ], 'render' => 'links'];
    }

    private static function absolutize(string $href, string $base): string {
        if (preg_match('#^https?://#', $href)) return $href;
        $parts = parse_url($base);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        if (str_starts_with($href, '//')) return $scheme . ':' . $href;
        if ($href[0] === '/') return $scheme . '://' . $host . $href;
        return $scheme . '://' . $host . '/' . ltrim($href, '/');
    }
}
