<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Modül keşif ve yükleme sistemi.
 */
final class ModuleLoader
{
    private static array $modules = [];

    public static function boot(Container $container, Router $router, string $basePath): void
    {
        $modulesPath = $basePath . '/app/Modules';
        if (!is_dir($modulesPath)) {
            return;
        }

        $activeModules = Config::get('modules.active', []);
        $modulesConfigExists = !empty($activeModules);

        foreach (glob($modulesPath . '/*', GLOB_ONLYDIR) as $dir) {
            $name = basename($dir);
            $manifestFile = $dir . '/module.php';

            if (!file_exists($manifestFile)) {
                continue;
            }

            $manifest = require $manifestFile;
            $slug = $manifest['slug'] ?? strtolower($name);

            // Aktif değilse atla (config yoksa hepsi aktif kabul)
            if ($modulesConfigExists && !in_array($slug, $activeModules, true) && !($manifest['is_core'] ?? false)) {
                continue;
            }

            self::$modules[$slug] = [
                'name'     => $name,
                'manifest' => $manifest,
                'path'     => $dir,
            ];

            // Route dosyalarını yükle
            foreach (['web', 'admin', 'customer', 'api'] as $group) {
                $routeFile = $dir . '/routes/' . $group . '.php';
                if (file_exists($routeFile)) {
                    require $routeFile;
                }
            }
        }
    }

    public static function all(): array
    {
        return self::$modules;
    }

    public static function has(string $slug): bool
    {
        return isset(self::$modules[$slug]);
    }

    public static function get(string $slug): ?array
    {
        return self::$modules[$slug] ?? null;
    }
}
