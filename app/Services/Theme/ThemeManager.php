<?php

declare(strict_types=1);

namespace App\Services\Theme;

use App\Core\Config;
use App\Core\SessionManager;

/**
 * Tema yönetimi.
 *
 * Her tema iki bağlamda çalışır:
 *   - "site"  → public + müşteri paneli
 *   - "admin" → admin paneli
 *
 * Dosya yapısı:
 *   themes/<slug>/
 *     ├── theme.json
 *     └── css/
 *         ├── site/*.css
 *         └── admin/*.css
 */
final class ThemeManager
{
    private static ?array $available = null;
    private static ?string $active = null;

    public const CONTEXT_SITE  = 'site';
    public const CONTEXT_ADMIN = 'admin';

    public static function themesPath(): string
    {
        return AHO_ROOT . '/themes';
    }

    /** Diskteki tüm temaları keşfeder. */
    public static function all(): array
    {
        if (self::$available !== null) {
            return self::$available;
        }

        $themes = [];
        $base = self::themesPath();
        if (!is_dir($base)) {
            return self::$available = [];
        }

        foreach (glob($base . '/*', GLOB_ONLYDIR) as $dir) {
            $slug = basename($dir);
            $jsonPath = $dir . '/theme.json';

            $meta = file_exists($jsonPath)
                ? (json_decode((string) file_get_contents($jsonPath), true) ?: [])
                : ['name' => ucfirst($slug)];

            $themes[$slug] = array_merge([
                'slug'          => $slug,
                'name'          => ucfirst($slug),
                'description'   => '',
                'author'        => 'Ahost Bilişim',
                'version'       => '1.0.0',
                'preview'       => '/themes/' . $slug . '/preview.png',
                'colors'        => ['primary' => '#0284c7', 'accent' => '#06b6d4'],
                'supports_dark' => true,
                'is_premium'    => false,
            ], $meta, ['slug' => $slug, 'path' => $dir]);
        }

        uksort($themes, function ($a, $b) {
            if ($a === 'default') return -1;
            if ($b === 'default') return 1;
            return strcmp($a, $b);
        });

        return self::$available = $themes;
    }

    /** Şu anki aktif tema slug'ı. */
    public static function active(): string
    {
        if (self::$active !== null) {
            return self::$active;
        }

        $slug = $_GET['theme']
            ?? SessionManager::get('theme')
            ?? ($_COOKIE['aho_theme'] ?? null)
            ?? Config::get('app.theme', 'default');

        $slug = (string) $slug;
        if (!self::exists($slug)) {
            $slug = 'default';
        }
        return self::$active = $slug;
    }

    public static function setActive(string $slug): bool
    {
        if (!self::exists($slug)) return false;
        SessionManager::set('theme', $slug);
        setcookie('aho_theme', $slug, time() + 86400 * 365, '/', '', false, false);
        self::$active = $slug;
        return true;
    }

    public static function exists(string $slug): bool
    {
        return is_dir(self::themesPath() . '/' . $slug);
    }

    public static function get(string $slug): ?array
    {
        $all = self::all();
        return $all[$slug] ?? null;
    }

    public static function activeManifest(): array
    {
        return self::get(self::active()) ?? self::get('default') ?? [];
    }

    public static function bodyAttribute(): string
    {
        return self::active();
    }

    /**
     * Verilen bağlam için yüklenmesi gereken CSS dosya URL'lerini döndürür.
     *
     * Sıra:
     *   1) Base default (o bağlam için) — tüm temel CSS'ler
     *   2) Aktif skin (default değilse) — override dosyaları
     */
    public static function stylesheetsFor(string $context = self::CONTEXT_SITE): array
    {
        $urls = [];

        // 1) BASE
        $baseDir = self::themesPath() . '/default/css/' . $context;
        if (is_dir($baseDir)) {
            foreach (self::orderedFiles($baseDir) as $file) {
                $urls[] = '/themes/default/css/' . $context . '/' . $file;
            }
        }

        // 2) SKIN
        $slug = self::active();
        if ($slug !== 'default') {
            $skinDir = self::themesPath() . '/' . $slug . '/css/' . $context;
            if (is_dir($skinDir)) {
                foreach (self::orderedFiles($skinDir) as $file) {
                    $urls[] = '/themes/' . $slug . '/css/' . $context . '/' . $file;
                }
            }
        }

        return $urls;
    }

    /** Geriye uyumluluk: site bağlamı için kısayol. */
    public static function activeStylesheets(): array
    {
        return self::stylesheetsFor(self::CONTEXT_SITE);
    }

    /**
     * Bir dizindeki CSS dosyalarını yükleme sırasına göre döndürür.
     * Deterministik: tokens → bloklar → modül bazlı override.
     */
    private static function orderedFiles(string $dir): array
    {
        $sitePriority = [
            'theme.css'          => 10,
            'blocks.css'         => 20,
            'buttons.css'        => 25,
            'forms.css'          => 30,
            'cards.css'          => 35,
            'header.css'         => 40,
            'footer.css'         => 50,
            'homepage.css'       => 60,
            'page.css'           => 65,
            'product.css'        => 70,
            'cart.css'           => 75,
            'checkout.css'       => 76,
            'contact.css'        => 78,
            'customer-login.css' => 80,
            'customer.css'       => 85,
            'tools.css'          => 88,  // Site araçları + Domain ailesi
            'builder.css'        => 92,  // Site Builder + Mobile Builder ailesi
            'ai-widget.css'      => 95,  // AI Assistant floating widget
            'support.css'        => 96,  // Ticket / Destek ailesi
            'marketplace.css'    => 97,  // Marketplace kart + kategori
        ];

        $adminPriority = [
            'theme.css'       => 10,
            'blocks.css'      => 20,
            'buttons.css'     => 25,
            'forms.css'       => 30,
            'cards.css'       => 35,
            'sidebar.css'     => 40,
            'topbar.css'      => 45,
            'admin-login.css' => 50,
            'dashboard.css'   => 60,
            'tables.css'      => 70,
            'admin.css'       => 90,
        ];

        $priority = array_merge($sitePriority, $adminPriority);

        $found = [];
        foreach (glob($dir . '/*.css') ?: [] as $path) {
            $name = basename($path);
            $found[$name] = $priority[$name] ?? 999;
        }

        uksort($found, fn($a, $b) => ($found[$a] === $found[$b])
            ? strcmp($a, $b)
            : $found[$a] <=> $found[$b]);

        return array_keys($found);
    }
}
