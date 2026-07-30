<?php
declare(strict_types=1);
namespace App\Modules\SiteTools\Tools;

final class MetaAnalyzeTool extends AbstractTool {
    public function slug(): string { return 'meta-tag'; }
    public function label(): string { return 'Meta Tag Analizi'; }
    public function description(): string { return 'Open Graph, Twitter Card, favicon, viewport ve diğer meta etiketler.'; }
    public function icon(): string { return '🏷️'; }
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

        $og = []; foreach ($xp->query('//meta[starts-with(@property, "og:")]') as $m) $og[$m->getAttribute('property')] = $m->getAttribute('content');
        $tw = []; foreach ($xp->query('//meta[starts-with(@name, "twitter:")]') as $m) $tw[$m->getAttribute('name')] = $m->getAttribute('content');

        $viewport = ''; foreach ($xp->query('//meta[@name="viewport"]') as $m) { $viewport = $m->getAttribute('content'); break; }
        $charset = ''; foreach ($xp->query('//meta[@charset]') as $m) { $charset = $m->getAttribute('charset'); break; }
        $favicon = '';
        foreach ($xp->query('//link[contains(@rel, "icon")]') as $l) { $favicon = $l->getAttribute('href'); break; }

        return ['success' => true, 'data' => [
            'url' => $res['url'],
            'open_graph' => $og,
            'twitter_card' => $tw,
            'viewport' => $viewport,
            'charset' => $charset,
            'favicon' => $favicon,
        ], 'render' => 'meta'];
    }
}
