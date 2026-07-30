<?php

declare(strict_types=1);

namespace App\Modules\Builder\Services;

/**
 * Site Builder → ZIP export.
 * JSON tree'yi statik HTML/CSS/JS'e çevirip .zip olarak sunar.
 * Mobile Builder için ayrıca Flutter kaynak kod veya APK build queue kullanılır (Faz 6).
 */
final class ExportService
{
    public static function siteToHtml(array $project, array $pages): string
    {
        $settings = json_decode((string) $project['settings'], true) ?: [];
        $primary  = $settings['colors']['primary'] ?? '#0284c7';
        $accent   = $settings['colors']['accent']  ?? '#06b6d4';
        $font     = $settings['font'] ?? 'Inter';
        $appName  = $project['name'];

        $home = null;
        foreach ($pages as $p) if ((int)$p['is_homepage'] === 1) { $home = $p; break; }
        if (!$home) $home = $pages[0] ?? null;

        $tree = $home ? (json_decode((string)$home['tree_json'], true) ?: []) : [];
        $body = self::renderBlocks($tree['blocks'] ?? []);
        if (($project['kind'] ?? 'site') === 'mobile') {
            $body = '<main class="phone-shell">' . $body . '</main>';
        }

        return <<<HTML
<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$appName}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family={$font}:wght@400;500;600;700&display=swap">
    <style>
        :root {
            --primary: {$primary};
            --accent: {$accent};
            --ink: #0f172a;
            --bg: #ffffff;
            --soft: #f8fafc;
            --border: #e2e8f0;
        }
        *,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: '{$font}', system-ui, sans-serif; color: var(--ink); background: var(--bg); line-height: 1.5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
        .btn { display: inline-block; padding: 12px 24px; background: var(--primary); color: #fff; border-radius: 10px; text-decoration: none; font-weight: 500; }
        .btn:hover { background: var(--accent); }
        .section { padding: 4rem 0; }
        .hero { padding: 6rem 0; text-align: center; background: linear-gradient(135deg, {$primary}15, {$accent}0a); }
        .hero h1 { font-size: 3rem; margin-bottom: 1rem; }
        .hero p { font-size: 1.25rem; color: #64748b; margin-bottom: 2rem; }
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; }
        .card { background: var(--soft); padding: 2rem; border-radius: 12px; border: 1px solid var(--border); }
        .phone-shell { width: min(420px, calc(100% - 32px)); margin: 48px auto; border: 14px solid #111827; border-radius: 44px; background: #fff; min-height: 720px; overflow: hidden; box-shadow: 0 30px 80px rgba(15,23,42,.18); }
        .builder-block { width: min(var(--w, 100%), 100%); min-height: var(--h, auto); margin: 18px auto; padding: 20px; border: 1px solid var(--border); border-radius: 18px; background: #fff; box-shadow: 0 14px 34px rgba(15,23,42,.08); text-align: var(--align, left); }
        .player { display:flex; align-items:center; justify-content:space-between; gap:18px; background:linear-gradient(135deg,var(--primary),var(--accent)); color:#fff; }
        .player small,.player em { display:block; opacity:.86; font-style:normal; margin-top:4px; word-break:break-all; }
        .player button,.inline-form button { border:0; border-radius:999px; padding:10px 16px; font-weight:700; cursor:pointer; }
        .now span { color:#64748b; font-weight:700; text-transform:uppercase; font-size:12px; }
        .now strong,.now small { display:block; }
        .inline-form div { display:flex; gap:8px; margin-top:12px; }
        .inline-form input,.contact-form input,.contact-form textarea { width:100%; border:1px solid var(--border); border-radius:12px; padding:12px; font:inherit; }
        .contact-form { display:grid; gap:10px; }
        .contact-form button,.inline-form button { background:var(--primary); color:#fff; }
        footer { padding: 2rem 0; background: {$primary}; color: #fff; text-align: center; margin-top: 4rem; }
        @media (max-width: 640px) { .phone-shell { border-width: 10px; border-radius: 34px; min-height: 640px; } .inline-form div,.player { flex-direction:column; align-items:stretch; } }
    </style>
</head>
<body>
{$body}
</body>
</html>
HTML;
    }

    private static function renderBlocks(array $blocks): string
    {
        $out = '';
        foreach ($blocks as $b) {
            $type = $b['type'] ?? '';
            $p = $b['props'] ?? [];
            $out .= self::renderBlock($type, $p);
        }
        return $out;
    }

    private static function renderBlock(string $type, array $props): string
    {
        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES);
        if (!in_array($type, ['hero', 'features', 'cta', 'footer'], true)) {
            return self::renderGenericBlock($type, $props);
        }
        return match ($type) {
            'hero' => sprintf(
                '<section class="hero"><div class="container"><h1>%s</h1><p>%s</p><a href="%s" class="btn">%s</a></div></section>',
                $e($props['title'] ?? ''),
                $e($props['subtitle'] ?? ''),
                $e($props['cta_link'] ?? '#'),
                $e($props['cta_text'] ?? 'Devam')
            ),
            'features' => sprintf(
                '<section class="section"><div class="container"><h2 style="text-align:center;margin-bottom:2rem">%s</h2><div class="grid-3">%s</div></div></section>',
                $e($props['title'] ?? 'Özellikler'),
                implode('', array_map(fn($i) => '<div class="card"><h3>' . $e($i) . '</h3></div>', $props['items'] ?? []))
            ),
            'cta' => sprintf(
                '<section class="section" style="text-align:center;background:linear-gradient(135deg,var(--primary),var(--accent));color:#fff"><div class="container"><h2 style="color:#fff;margin-bottom:1rem">%s</h2><a href="#" class="btn" style="background:#fff;color:var(--primary)">%s</a></div></section>',
                $e($props['title'] ?? ''),
                $e($props['button'] ?? 'Başla')
            ),
            'footer' => sprintf(
                '<footer><div class="container"><p>%s</p></div></footer>',
                $e($props['copyright'] ?? '© ' . date('Y'))
            ),
            'text', 'heading', 'about' => sprintf(
                '<section class="section"><div class="container"><h2>%s</h2><p>%s</p></div></section>',
                $e($props['title'] ?? 'Başlık'),
                $e($props['content'] ?? 'İçerik henüz doldurulmadı.')
            ),
            default => sprintf(
                '<section class="section"><div class="container"><div class="card"><strong>%s</strong> bloğu (özelleştirmek için editöre gidin).</div></div></section>',
                $e($type)
            ),
        };
    }

    private static function renderGenericBlock(string $type, array $props): string
    {
        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES);
        $title = $props['title'] ?? $props['label'] ?? self::humanType($type);
        $content = $props['content'] ?? $props['subtitle'] ?? $props['description'] ?? '';
        $button = $props['button'] ?? $props['cta_text'] ?? $props['text'] ?? '';
        $style = self::blockStyle($props);

        if (in_array($type, ['radio_player', 'audio_player', 'player'], true)) {
            return sprintf(
                '<section class="builder-block player" style="%s"><div><strong>%s</strong><small>%s</small><em>%s</em></div><button>▶ %s</button></section>',
                $style,
                $e($title ?: 'Canlı Yayın'),
                $e($props['station'] ?? 'Radyo'),
                $e($props['stream_url'] ?? $props['url'] ?? 'Radyo URL bekleniyor'),
                $e($button ?: 'Dinle')
            );
        }

        if ($type === 'now_playing') {
            return sprintf(
                '<section class="builder-block now" style="%s"><span>%s</span><strong>%s</strong><small>%s</small></section>',
                $style,
                $e($title ?: 'Şu An Çalan'),
                $e($props['track'] ?? 'Parça adı'),
                $e($props['artist'] ?? 'Sanatçı')
            );
        }

        if (str_contains($type, 'form') || str_contains($type, 'request') || str_contains($type, 'search') || str_contains($type, 'newsletter')) {
            return sprintf(
                '<section class="builder-block inline-form" style="%s"><strong>%s</strong><div><input placeholder="%s"><button>%s</button></div></section>',
                $style,
                $e($title),
                $e($props['placeholder'] ?? 'Yazın...'),
                $e($button ?: 'Gönder')
            );
        }

        $items = '';
        if (!empty($props['items']) && is_array($props['items'])) {
            $items = '<div class="grid-3" style="margin-top:16px">' . implode('', array_map(fn($i) => '<div class="card">' . $e($i) . '</div>', $props['items'])) . '</div>';
        }

        $image = '';
        $imageUrl = $props['image'] ?? $props['image_url'] ?? $props['bg_image'] ?? '';
        if ($imageUrl !== '') {
            $image = '<img src="' . $e($imageUrl) . '" alt="" style="width:100%;border-radius:14px;margin-bottom:14px">';
        }

        $btn = $button !== '' ? '<a class="btn" href="' . $e($props['link'] ?? $props['cta_link'] ?? '#') . '">' . $e($button) . '</a>' : '';
        return sprintf(
            '<section class="builder-block" style="%s">%s<h2>%s</h2>%s%s%s</section>',
            $style,
            $image,
            $e($title),
            $content !== '' ? '<p style="margin:10px 0 16px;color:#64748b">' . $e($content) . '</p>' : '',
            $items,
            $btn
        );
    }

    private static function blockStyle(array $props): string
    {
        $style = [];
        if (!empty($props['align'])) $style[] = '--align:' . preg_replace('/[^a-z]/', '', (string)$props['align']);
        if (!empty($props['width'])) $style[] = '--w:' . max(80, (int)$props['width']) . 'px';
        if (!empty($props['height'])) $style[] = '--h:' . max(44, (int)$props['height']) . 'px';
        if (!empty($props['bg_color'])) $style[] = 'background:' . htmlspecialchars((string)$props['bg_color'], ENT_QUOTES);
        if (!empty($props['text_color'])) $style[] = 'color:' . htmlspecialchars((string)$props['text_color'], ENT_QUOTES);
        return implode(';', $style);
    }

    private static function humanType(string $type): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $type));
    }

    /** Projeyi ZIP olarak paketle. */
    public static function toZip(array $project, array $pages, string $outputPath): bool
    {
        if (!class_exists('ZipArchive')) return false;
        $zip = new \ZipArchive();
        if ($zip->open($outputPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) return false;

        $html = self::siteToHtml($project, $pages);
        $zip->addFromString('index.html', $html);
        $zip->addFromString('README.txt',
            "Ahost Bilişim Site Builder Export\n" .
            "Proje: {$project['name']}\n" .
            "Tarih: " . date('Y-m-d H:i:s') . "\n\n" .
            "index.html dosyasını bir web sunucusuna yükleyin.\n"
        );
        $zip->close();
        return true;
    }
}
