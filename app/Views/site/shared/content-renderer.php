<?php
/**
 * Unified public content renderer.
 * Blog, bilgi bankası, duyuru ve benzer public içerikler aynı temiz HTML iskeletini kullanır.
 */
if (!function_exists('ao_site_content_excerpt')) {
    function ao_site_content_excerpt($value, $limit = 155) {
        $plain = trim(strip_tags((string)$value));
        if ($plain === '') return '';
        if (mb_strlen($plain, 'UTF-8') <= $limit) return $plain;
        return rtrim(mb_substr($plain, 0, $limit, 'UTF-8')) . '...';
    }
}

if (!function_exists('ao_site_content_date')) {
    function ao_site_content_date($row) {
        $date = $row['published_at'] ?? $row['created_at'] ?? null;
        if (!$date) return '';
        try {
            return date('d.m.Y', strtotime($date));
        } catch (Throwable $e) {
            return '';
        }
    }
}

if (!function_exists('ao_site_content_card')) {
    function ao_site_content_card(array $item, array $opts = []) {
        $title = $item[$opts['title_key'] ?? 'title'] ?? '';
        $image = $item[$opts['image_key'] ?? 'featured_image'] ?? '';
        $badge = $item[$opts['badge_key'] ?? 'category_name'] ?? ($opts['badge'] ?? 'Genel');
        $textSource = $item[$opts['excerpt_key'] ?? 'excerpt'] ?? ($item[$opts['content_key'] ?? 'content'] ?? '');
        $href = is_callable($opts['href'] ?? null) ? $opts['href']($item) : ($opts['href'] ?? '#');
        $linkText = $opts['link_text'] ?? 'Devamını Oku';
        $meta = array_filter([$badge, ao_site_content_date($item)]);

        ob_start();
        ?>
        <article class="ao-content-card" data-content-type="<?= e($opts['type'] ?? 'content') ?>">
            <?php if ($image): ?>
                <img src="<?= e($image) ?>" alt="<?= e($title) ?>">
            <?php endif; ?>
            <?php if ($badge): ?>
                <span class="ao-content-badge"><?= e($badge) ?></span>
            <?php endif; ?>
            <h3><?= e($title) ?></h3>
            <?php if ($textSource): ?>
                <p><?= e(ao_site_content_excerpt($textSource, (int)($opts['limit'] ?? 155))) ?></p>
            <?php endif; ?>
            <?php if ($meta): ?>
                <div class="ao-content-meta"><?= e(implode(' · ', $meta)) ?></div>
            <?php endif; ?>
            <div class="ao-content-actions">
                <a class="ao-content-btn secondary" href="<?= e($href) ?>"><?= e($linkText) ?></a>
            </div>
        </article>
        <?php
        return trim(ob_get_clean());
    }
}

if (!function_exists('ao_site_content_grid')) {
    function ao_site_content_grid(array $items, array $opts = []) {
        if (!$items) {
            return '<div class="ao-content-empty"><h3>' . e($opts['empty_title'] ?? 'Henüz içerik yok') . '</h3><p>' . e($opts['empty_text'] ?? 'Yayınlanan içerikler burada ortak tasarım diliyle listelenir.') . '</p></div>';
        }

        $html = '<section class="ao-content-grid ' . e($opts['grid_class'] ?? '') . '">';
        foreach ($items as $item) {
            $html .= ao_site_content_card($item, $opts);
        }
        return $html . '</section>';
    }
}

if (!function_exists('ao_site_content_page')) {
    function ao_site_content_page(array $page) {
        extract($page, EXTR_SKIP);
        require __DIR__ . '/content-page.php';
    }
}
