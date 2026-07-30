<?php
declare(strict_types=1);
namespace App\Modules\SiteTools\Tools;

final class SeoAnalyzeTool extends AbstractTool {
    public function slug(): string { return 'seo-analiz'; }
    public function label(): string { return 'SEO Analizi'; }
    public function description(): string { return 'Title, meta, H1-H3, alt, schema, canonical, robots.'; }
    public function icon(): string { return '📊'; }
    public function inputPlaceholder(): string { return 'https://ornekdomain.com'; }
    public function run(string $input): array {
        $url = self::normalizeUrl($input);
        $res = self::fetch($url, 15);
        if (!$res['success']) return ['success' => false, 'error' => $res['error']];

        $html = $res['body'];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xp = new \DOMXPath($dom);

        $title = trim(($xp->query('//title')->item(0)?->textContent) ?? '');
        $desc = '';
        foreach ($xp->query('//meta[@name="description"]') as $m) { $desc = trim($m->getAttribute('content')); break; }
        $canonical = '';
        foreach ($xp->query('//link[@rel="canonical"]') as $m) { $canonical = trim($m->getAttribute('href')); break; }
        $robots = '';
        foreach ($xp->query('//meta[@name="robots"]') as $m) { $robots = trim($m->getAttribute('content')); break; }
        $ogCount = $xp->query('//meta[starts-with(@property, "og:")]')->length;
        $twCount = $xp->query('//meta[starts-with(@name, "twitter:")]')->length;
        $schemaCount = $xp->query('//script[@type="application/ld+json"]')->length;

        $h1 = []; foreach ($xp->query('//h1') as $n) $h1[] = trim($n->textContent);
        $h2Count = $xp->query('//h2')->length;
        $h3Count = $xp->query('//h3')->length;

        $imgs = $xp->query('//img');
        $imgTotal = $imgs->length; $imgNoAlt = 0;
        foreach ($imgs as $img) if (trim($img->getAttribute('alt')) === '') $imgNoAlt++;

        $intLinks = 0; $extLinks = 0;
        $host = parse_url($url, PHP_URL_HOST);
        foreach ($xp->query('//a[@href]') as $a) {
            $href = $a->getAttribute('href');
            if ($href === '' || $href[0] === '#') continue;
            $linkHost = parse_url($href, PHP_URL_HOST);
            if (!$linkHost || $linkHost === $host) $intLinks++;
            else $extLinks++;
        }

        $words = str_word_count(strip_tags($html));

        $issues = [];
        if ($title === '') $issues[] = 'Title etiketi yok.';
        elseif (mb_strlen($title) < 30) $issues[] = 'Title çok kısa (30-60 karakter ideal).';
        elseif (mb_strlen($title) > 65) $issues[] = 'Title çok uzun (arama sonucunda kesilebilir).';

        if ($desc === '') $issues[] = 'Meta description yok.';
        elseif (mb_strlen($desc) < 70) $issues[] = 'Meta description çok kısa (120-160 karakter ideal).';
        elseif (mb_strlen($desc) > 170) $issues[] = 'Meta description çok uzun.';

        if (count($h1) === 0) $issues[] = 'H1 etiketi yok.';
        if (count($h1) > 1) $issues[] = 'Birden fazla H1 var (' . count($h1) . ' adet).';

        if ($imgNoAlt > 0) $issues[] = "{$imgNoAlt} adet görselde alt metni eksik.";
        if ($canonical === '') $issues[] = 'Canonical URL yok.';
        if ($schemaCount === 0) $issues[] = 'Schema.org JSON-LD yok.';
        if ($ogCount < 4) $issues[] = 'Open Graph etiketleri eksik veya yok.';

        return ['success' => true, 'data' => [
            'url' => $res['url'],
            'title' => $title, 'title_length' => mb_strlen($title),
            'meta_description' => $desc, 'desc_length' => mb_strlen($desc),
            'canonical' => $canonical,
            'robots' => $robots,
            'h1' => $h1, 'h2_count' => $h2Count, 'h3_count' => $h3Count,
            'images_total' => $imgTotal, 'images_no_alt' => $imgNoAlt,
            'links_internal' => $intLinks, 'links_external' => $extLinks,
            'open_graph_count' => $ogCount, 'twitter_card_count' => $twCount,
            'schema_count' => $schemaCount,
            'word_count' => $words,
            'issues' => $issues,
        ], 'render' => 'seo'];
    }
}
