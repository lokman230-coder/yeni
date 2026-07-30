<?php
// v26.2.2 SEO sitemap generator: public route + dynamic content, no UI/CSS impact.
if (!function_exists('ao_seo_sitemap_base_url')) {
    function ao_seo_sitemap_base_url(): string {
        $base = trim((string)admin_setting('site_url', admin_setting('base_url', url(''))));
        if ($base === '') $base = url('');
        if (!preg_match('~^https?://~i', $base)) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base = $scheme.'://'.$host.'/'.ltrim($base, '/');
        }
        return rtrim($base, '/');
    }
    function ao_seo_sitemap_loc(string $path = ''): string {
        $path = trim($path, '/');
        return ao_seo_sitemap_base_url().($path === '' ? '/' : '/'.$path);
    }
    function ao_seo_sitemap_date($value = null): string {
        $ts = $value ? strtotime((string)$value) : false;
        return date('Y-m-d', $ts ?: time());
    }
    function ao_seo_sitemap_table_columns(string $table): array {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) return [];
        try { return array_map(fn($r) => $r['Field'] ?? $r[0] ?? '', db()->query('SHOW COLUMNS FROM `'.$table.'`')->fetchAll() ?: []); }
        catch(Throwable $e) { return []; }
    }
    function ao_seo_sitemap_add(array &$items, string $path, string $priority = '0.50', string $freq = 'weekly', $lastmod = null): void {
        $loc = ao_seo_sitemap_loc($path);
        $items[$loc] = [
            'loc' => $loc,
            'lastmod' => ao_seo_sitemap_date($lastmod),
            'changefreq' => $freq,
            'priority' => $priority,
        ];
    }
    function ao_seo_sitemap_dynamic_rows(array &$items): void {
        $jobs = [
            ['product_groups', 'urun-grubu/', '0.72', 'weekly', "SELECT slug, COALESCE(created_at,NOW()) lastmod FROM product_groups WHERE is_active=1 ORDER BY sort_order,id LIMIT 500"],
            ['products', 'urun/', '0.78', 'weekly', "SELECT slug, COALESCE(updated_at,created_at,NOW()) lastmod FROM products WHERE is_active=1 AND (visibility IS NULL OR visibility IN ('visible','public')) ORDER BY sort_order,id LIMIT 1000"],
            ['blog_posts', 'blog/', '0.68', 'weekly', "SELECT slug, COALESCE(updated_at,published_at,created_at,NOW()) lastmod FROM blog_posts WHERE status='published' AND (visibility IS NULL OR visibility='public') ORDER BY COALESCE(published_at,created_at) DESC LIMIT 1000"],
            ['knowledge_articles', 'bilgi-bankasi/', '0.62', 'weekly', "SELECT slug, COALESCE(updated_at,created_at,NOW()) lastmod FROM knowledge_articles WHERE audience='customer' AND status='published' ORDER BY updated_at DESC LIMIT 1000"],
            ['portfolio_references', 'referanslar#', '0.50', 'monthly', "SELECT slug, COALESCE(updated_at,created_at,NOW()) lastmod FROM portfolio_references WHERE is_active=1 ORDER BY is_featured DESC,sort_order,id LIMIT 500"],
            ['announcements', 'duyurular/', '0.45', 'monthly', "SELECT id AS slug, COALESCE(created_at,NOW()) lastmod FROM announcements WHERE is_active=1 AND channel IN ('site','all') AND (starts_at IS NULL OR starts_at<=NOW()) AND (ends_at IS NULL OR ends_at>=NOW()) ORDER BY id DESC LIMIT 500"],
        ];
        foreach ($jobs as [$table, $prefix, $priority, $freq, $sql]) {
            if (!ao_seo_sitemap_table_columns($table)) continue;
            try {
                foreach (db()->query($sql)->fetchAll() ?: [] as $row) {
                    $slug = trim((string)($row['slug'] ?? ''));
                    if ($slug === '') continue;
                    $path = str_ends_with($prefix, '#') ? rtrim($prefix, '#').'#'.rawurlencode($slug) : $prefix.$slug;
                    ao_seo_sitemap_add($items, $path, $priority, $freq, $row['lastmod'] ?? null);
                }
            } catch(Throwable $e) { error_log('[ao sitemap] '.$e->getMessage()); }
        }
    }
    function ao_seo_sitemap_items(array $siteMap): array {
        $items = [];
        $skip = ['cart'=>1,'checkout'=>1,'contact'=>1,'references'=>1,'knowledge-base'=>1,'products'=>1,'domain-checker'=>1,'mobilebuilder/download'=>1];
        foreach ($siteMap as $path => $view) {
            if (isset($skip[$path])) continue;
            ao_seo_sitemap_add($items, (string)$path, $path === '' ? '1.00' : '0.70', $path === '' ? 'daily' : 'weekly');
        }
        ao_seo_sitemap_dynamic_rows($items);
        ksort($items);
        return array_values($items);
    }
}
if (in_array($route, ['sitemap.xml','sitemap'], true)) {
    $items = ao_seo_sitemap_items($siteMap);
    header('Content-Type: application/xml; charset=utf-8');
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    foreach ($items as $item) {
        echo "  <url>\n";
        echo "    <loc>".htmlspecialchars($item['loc'], ENT_XML1 | ENT_COMPAT, 'UTF-8')."</loc>\n";
        echo "    <lastmod>".htmlspecialchars($item['lastmod'], ENT_XML1 | ENT_COMPAT, 'UTF-8')."</lastmod>\n";
        echo "    <changefreq>".htmlspecialchars($item['changefreq'], ENT_XML1 | ENT_COMPAT, 'UTF-8')."</changefreq>\n";
        echo "    <priority>".htmlspecialchars($item['priority'], ENT_XML1 | ENT_COMPAT, 'UTF-8')."</priority>\n";
        echo "  </url>\n";
    }
    echo "</urlset>\n";
    exit;
}
if ($route === 'robots.txt') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\n";
    echo "Allow: /\n";
    echo "Disallow: /admin/\n";
    echo "Disallow: /client/\n";
    echo "Disallow: /cart\n";
    echo "Disallow: /checkout\n";
    echo "Sitemap: ".ao_seo_sitemap_loc('sitemap.xml')."\n";
    exit;
}
