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
        footer { padding: 2rem 0; background: {$primary}; color: #fff; text-align: center; margin-top: 4rem; }
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
