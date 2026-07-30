<?php
// Ahost One Prism slider data, upload and frontend render helpers.

if (!function_exists('ao_prism_slider_ensure_schema')) {
    function ao_prism_slider_ensure_schema() {
        try {
            db()->exec("CREATE TABLE IF NOT EXISTS site_sliders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                kicker VARCHAR(160) NULL,
                title VARCHAR(220) NOT NULL,
                description TEXT NULL,
                image_url VARCHAR(255) NULL,
                media_type VARCHAR(20) DEFAULT 'image',
                video_url VARCHAR(255) NULL,
                background_image_url VARCHAR(255) NULL,
                background_video_url VARCHAR(255) NULL,
                background_color VARCHAR(80) NULL,
                text_color VARCHAR(40) NULL,
                accent_color VARCHAR(40) NULL,
                max_width VARCHAR(40) NULL,
                height_value VARCHAR(40) NULL,
                padding_value VARCHAR(80) NULL,
                title_size VARCHAR(40) NULL,
                title_weight VARCHAR(20) NULL,
                body_size VARCHAR(40) NULL,
                radius_value VARCHAR(40) NULL,
                align_value VARCHAR(30) NULL,
                button_text VARCHAR(120) NULL,
                button_url VARCHAR(255) NULL,
                sort_order INT DEFAULT 10,
                is_active TINYINT(1) DEFAULT 1,
                starts_at DATETIME NULL,
                ends_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_active_sort(is_active, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        } catch (Throwable $e) {
            error_log('[ao] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }

        foreach ([
            "ALTER TABLE site_sliders ADD COLUMN IF NOT EXISTS media_type VARCHAR(20) DEFAULT 'image'",
            "ALTER TABLE site_sliders ADD COLUMN IF NOT EXISTS video_url VARCHAR(255) NULL",
            "ALTER TABLE site_sliders ADD COLUMN IF NOT EXISTS background_image_url VARCHAR(255) NULL",
            "ALTER TABLE site_sliders ADD COLUMN IF NOT EXISTS background_video_url VARCHAR(255) NULL",
            "ALTER TABLE site_sliders ADD COLUMN IF NOT EXISTS background_color VARCHAR(80) NULL",
            "ALTER TABLE site_sliders ADD COLUMN IF NOT EXISTS text_color VARCHAR(40) NULL",
            "ALTER TABLE site_sliders ADD COLUMN IF NOT EXISTS accent_color VARCHAR(40) NULL",
            "ALTER TABLE site_sliders ADD COLUMN IF NOT EXISTS max_width VARCHAR(40) NULL",
            "ALTER TABLE site_sliders ADD COLUMN IF NOT EXISTS height_value VARCHAR(40) NULL",
            "ALTER TABLE site_sliders ADD COLUMN IF NOT EXISTS padding_value VARCHAR(80) NULL",
            "ALTER TABLE site_sliders ADD COLUMN IF NOT EXISTS title_size VARCHAR(40) NULL",
            "ALTER TABLE site_sliders ADD COLUMN IF NOT EXISTS title_weight VARCHAR(20) NULL",
            "ALTER TABLE site_sliders ADD COLUMN IF NOT EXISTS body_size VARCHAR(40) NULL",
            "ALTER TABLE site_sliders ADD COLUMN IF NOT EXISTS radius_value VARCHAR(40) NULL",
            "ALTER TABLE site_sliders ADD COLUMN IF NOT EXISTS align_value VARCHAR(30) NULL",
        ] as $sql) {
            try {
                db()->exec($sql);
            } catch (Throwable $e) {
                error_log('[ao] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            }
        }

        try {
            db()->prepare("INSERT INTO settings(setting_key, setting_value) VALUES('site_slider_enabled', '1') ON DUPLICATE KEY UPDATE setting_value = setting_value")->execute();
        } catch (Throwable $e) {
            error_log('[ao] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }

        try {
            $count = (int)db()->query('SELECT COUNT(*) FROM site_sliders')->fetchColumn();
            if ($count === 0) {
                $stmt = db()->prepare('INSERT INTO site_sliders(kicker,title,description,image_url,media_type,video_url,background_image_url,background_video_url,button_text,button_url,sort_order,is_active) VALUES(?,?,?,?,?,?,?,?,?,?,?,1)');
                $stmt->execute(['Ahost One Prism', 'Domain, hosting ve dijital çözümler tek merkezde', 'Hosting, domain, VPS, web tasarım ve müşteri paneli deneyimini modern bir arayüzle yönetin.', '', 'image', '', '', '', 'Hemen Başla', 'hosting', 10]);
                $stmt->execute(['Yeni Nesil Panel', 'Müşterileriniz için daha sade bir yönetim deneyimi', 'Faturalar, hizmetler, alan adları ve destek talepleri tek panelden kolayca takip edilir.', '', 'image', '', '', '', 'Müşteri Paneli', 'client/login', 20]);
            }
        } catch (Throwable $e) {
            error_log('[ao] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }

        try {
            $fixes = [
                ['kicker', 'Domain Kayit / Yenileme / Transfer', 'Domain Kayıt / Yenileme / Transfer'],
                ['title', 'Alan Adinizi Kolayca Yonetin', 'Alan Adınızı Kolayca Yönetin'],
                ['button_text', 'Simdi Tescil / Transfer Et', 'Şimdi Tescil / Transfer Et'],
                ['button_text', 'Paketleri Incele', 'Paketleri İncele'],
            ];
            foreach ($fixes as [$field, $old, $new]) {
                db()->prepare("UPDATE site_sliders SET {$field}=? WHERE {$field}=?")->execute([$new, $old]);
            }
        } catch (Throwable $e) {
            error_log('[ao] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }
    }
}

if (!function_exists('ao_prism_slider_url')) {
    function ao_prism_slider_url($url) {
        $url = trim((string)$url);
        if ($url === '') return '#';
        if (preg_match('#^https?://#i', $url) || str_starts_with($url, '#') || str_starts_with($url, 'data:')) return $url;
        return url($url);
    }
}

if (!function_exists('ao_prism_slider_video_embed_url')) {
    function ao_prism_slider_video_embed_url($url) {
        $url = trim((string)$url);
        if ($url === '' || !preg_match('#^https?://#i', $url)) return '';
        $parts = parse_url($url);
        $host = strtolower((string)($parts['host'] ?? ''));
        $path = (string)($parts['path'] ?? '');
        parse_str((string)($parts['query'] ?? ''), $query);

        if (str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be')) {
            $id = '';
            if (str_contains($host, 'youtu.be')) $id = trim($path, '/');
            elseif (!empty($query['v'])) $id = (string)$query['v'];
            elseif (preg_match('#/(embed|shorts)/([A-Za-z0-9_-]{6,})#', $path, $m)) $id = $m[2];
            if ($id !== '') return 'https://www.youtube-nocookie.com/embed/' . rawurlencode($id) . '?rel=0&modestbranding=1&playsinline=1';
        }

        if (str_contains($host, 'vimeo.com') && preg_match('#/(?:video/)?([0-9]{5,})#', $path, $m)) {
            return 'https://player.vimeo.com/video/' . rawurlencode($m[1]) . '?title=0&byline=0&portrait=0';
        }

        if (str_contains($host, 'dailymotion.com') || str_contains($host, 'dai.ly')) {
            $id = '';
            if (str_contains($host, 'dai.ly')) $id = trim($path, '/');
            elseif (preg_match('#/video/([A-Za-z0-9]+)#', $path, $m)) $id = $m[1];
            if ($id !== '') return 'https://www.dailymotion.com/embed/video/' . rawurlencode($id);
        }

        if (str_contains($host, 'twitch.tv')) {
            $parent = $_SERVER['HTTP_HOST'] ?? 'localhost';
            if (preg_match('#/videos/([0-9]+)#', $path, $m)) return 'https://player.twitch.tv/?video=' . rawurlencode($m[1]) . '&parent=' . rawurlencode($parent);
            $channel = trim($path, '/');
            if ($channel !== '') return 'https://player.twitch.tv/?channel=' . rawurlencode($channel) . '&parent=' . rawurlencode($parent);
        }

        return '';
    }
}

if (!function_exists('ao_prism_slider_datetime')) {
    function ao_prism_slider_datetime($value) {
        $value = trim((string)$value);
        if ($value === '') return null;
        $value = str_replace('T', ' ', $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) $value .= ':00';
        return $value;
    }
}

if (!function_exists('ao_prism_slider_file_upload')) {
    function ao_prism_slider_file_upload($field, $kind = 'image') {
        if (empty($_FILES[$field]) || empty($_FILES[$field]['name']) || !is_uploaded_file($_FILES[$field]['tmp_name'] ?? '')) return '';

        $name = (string)$_FILES[$field]['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed = $kind === 'video'
            ? ['mp4', 'webm', 'mov', 'm4v']
            : ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        if (!in_array($ext, $allowed, true)) throw new Exception(($kind === 'video' ? 'Video' : 'Görsel') . ' dosya türü desteklenmiyor: ' . $ext);

        $max = $kind === 'video' ? 60 * 1024 * 1024 : 12 * 1024 * 1024;
        if ((int)($_FILES[$field]['size'] ?? 0) > $max) throw new Exception(($kind === 'video' ? 'Video' : 'Görsel') . ' dosyası çok büyük.');

        $dir = dirname(__DIR__, 2) . '/public/uploads/sliders';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        $safe = 'slider-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = $dir . '/' . $safe;
        if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) throw new Exception('Dosya yüklenemedi.');
        return 'public/uploads/sliders/' . $safe;
    }
}

if (!function_exists('ao_prism_active_slides')) {
    function ao_prism_active_slides() {
        ao_prism_slider_ensure_schema();
        try {
            $query = db()->query("SELECT * FROM site_sliders WHERE is_active=1 AND (starts_at IS NULL OR starts_at<=NOW()) AND (ends_at IS NULL OR ends_at>=NOW()) ORDER BY sort_order ASC, id DESC LIMIT 8");
            return $query->fetchAll() ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('ao_prism_slider_allowed_by_builder')) {
    function ao_prism_slider_allowed_by_builder() {
        $route = trim((string)($_SERVER['AHOST_ROUTE_RESOLVED'] ?? ''), '/');
        $context = function_exists('ao_builder_context_from_route') ? ao_builder_context_from_route($route) : ['target' => 'site', 'template' => ($route ?: 'home')];
        $template = $context['template'] ?? ($route ?: 'home');
        return !function_exists('ao_builder_pro_has_widget') || ao_builder_pro_has_widget('site', $template ?: 'home', 'slider', true);
    }
}

if (!function_exists('ao_prism_slider_style')) {
    function ao_prism_slider_style(array $slide): string {
        $map = [
            'background_color' => '--ao-slider-bg',
            'text_color' => '--ao-slider-text',
            'accent_color' => '--ao-slider-accent',
            'max_width' => '--ao-slider-max',
            'height_value' => '--ao-slider-height',
            'padding_value' => '--ao-slider-padding',
            'title_size' => '--ao-slider-title-size',
            'title_weight' => '--ao-slider-title-weight',
            'body_size' => '--ao-slider-body-size',
            'radius_value' => '--ao-slider-radius',
            'align_value' => '--ao-slider-align',
        ];
        $style = [];
        foreach ($map as $field => $var) {
            $value = trim((string)($slide[$field] ?? ''));
            if ($value !== '') $style[] = $var . ':' . $value;
        }
        return implode(';', $style);
    }
}

if (!function_exists('ao_prism_render_site_slider')) {
    function ao_prism_render_site_slider() {
        $theme = function_exists('ao_active_theme') ? (ao_active_theme('site') ?: []) : [];
        if (($theme['slug'] ?? '') !== 'ahost-prism') return '';
        if ((string)admin_setting('site_slider_enabled', '1') !== '1') return '';
        if (!ao_prism_slider_allowed_by_builder()) return '';

        $slides = ao_prism_active_slides();
        if (!$slides) return '';
        $sliderShellStyle = ao_prism_slider_style((array)($slides[0] ?? []));

        ob_start();
        ?>
        <section class="ao-prism-site-slider" data-prism-slider data-builder-block="slider"<?= $sliderShellStyle !== '' ? ' style="'.e($sliderShellStyle).'"' : '' ?>>
            <div class="ao-prism-slider-track">
                <?php foreach ($slides as $index => $slide):
                    $mediaType = trim((string)($slide['media_type'] ?? 'image')) ?: 'image';
                    $image = trim((string)($slide['image_url'] ?? ''));
                    $video = trim((string)($slide['video_url'] ?? ''));
                    $videoEmbed = ao_prism_slider_video_embed_url($video);
                    $bgImage = trim((string)($slide['background_image_url'] ?? ''));
                    $bgVideo = trim((string)($slide['background_video_url'] ?? ''));
                    $classes = ['ao-prism-slide', $index === 0 ? 'is-active' : ''];
                    if ($bgImage) $classes[] = 'has-bg-image';
                    if ($bgVideo) $classes[] = 'has-bg-video';
                    if ($mediaType === 'video' && $video) $classes[] = $videoEmbed ? 'has-media-embed' : 'has-media-video';
                    $styleParts = [];
                    if ($bgImage) $styleParts[] = "--ao-prism-slide-bg:url('" . ao_prism_slider_url($bgImage) . "')";
                    $adminStyle = ao_prism_slider_style($slide);
                    if ($adminStyle !== '') $styleParts[] = $adminStyle;
                    $style = $styleParts ? ' style="' . e(implode(';', $styleParts)) . '"' : '';
                ?>
                    <article class="<?= e(trim(implode(' ', $classes))) ?>" data-prism-slide<?= $style ?>>
                        <?php if ($bgVideo): ?><video class="ao-prism-slide-bg-video" autoplay muted loop playsinline preload="metadata" src="<?= e(ao_prism_slider_url($bgVideo)) ?>"></video><?php endif; ?>
                        <div class="ao-prism-slide-copy">
                            <?php if (!empty($slide['kicker'])): ?><span class="ao-prism-slide-kicker"><?= e($slide['kicker']) ?></span><?php endif; ?>
                            <h2><?= e($slide['title']) ?></h2>
                            <?php if (!empty($slide['description'])): ?><p><?= e($slide['description']) ?></p><?php endif; ?>
                            <?php if (!empty($slide['button_text'])): ?><a class="ao-prism-slide-btn" href="<?= e(ao_prism_slider_url($slide['button_url'] ?? '#')) ?>"><?= e($slide['button_text']) ?></a><?php endif; ?>
                        </div>
                        <div class="ao-prism-slide-art">
                            <?php if ($mediaType === 'video' && $videoEmbed): ?>
                                <iframe class="ao-prism-slide-embed" src="<?= e($videoEmbed) ?>" title="<?= e($slide['title']) ?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                            <?php elseif ($mediaType === 'video' && $video): ?>
                                <video autoplay muted loop playsinline preload="metadata" src="<?= e(ao_prism_slider_url($video)) ?>"></video>
                            <?php elseif ($image): ?>
                                <img src="<?= e(ao_prism_slider_url($image)) ?>" alt="<?= e($slide['title']) ?>">
                            <?php else: ?>
                                <div class="ao-prism-slide-abstract"><i></i><i></i><i></i></div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if (count($slides) > 1): ?>
                <button type="button" class="ao-prism-slider-arrow prev" data-prism-slide-prev aria-label="Önceki slider">‹</button>
                <button type="button" class="ao-prism-slider-arrow next" data-prism-slide-next aria-label="Sonraki slider">›</button>
                <div class="ao-prism-slider-dots">
                    <?php foreach ($slides as $index => $slide): ?>
                        <button type="button" data-prism-slide-dot="<?= $index ?>" class="<?= $index === 0 ? 'active' : '' ?>" aria-label="Slider <?= $index + 1 ?>"></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
        return trim(ob_get_clean());
    }
}
