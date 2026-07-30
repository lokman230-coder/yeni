<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Basit native PHP template engine.
 * - Auto-escape default
 * - extend + section + yield
 * - modül namespace: 'cart::index' → app/Modules/Cart/Views/index.php
 * - tema view: 'layouts.public' → themes/default/layouts/public.php
 */
final class View
{
    private string $themePath;
    private string $modulesPath;
    private array $sections = [];
    private array $sectionStack = [];
    private ?string $extends = null;
    private array $sharedData = [];

    public function __construct()
    {
        $this->themePath   = AHO_ROOT . '/themes/' . Config::get('app.theme', 'default');
        $this->modulesPath = AHO_ROOT . '/app/Modules';
    }

    public function share(string $key, mixed $value): void
    {
        $this->sharedData[$key] = $value;
    }

    public function render(string $template, array $data = []): string
    {
        $data = array_merge($this->sharedData, $data);
        $file = $this->resolve($template);

        $content = $this->renderFile($file, $data);

        if ($this->extends !== null) {
            $parent = $this->extends;
            $this->extends = null;
            // Sections zaten dolduruldu, parent'ı render et
            $parentFile = $this->resolve($parent);
            $content = $this->renderFile($parentFile, $data);
        }

        return $content;
    }

    private function renderFile(string $_file, array $_data): string
    {
        if (!file_exists($_file)) {
            throw new RuntimeException("View bulunamadı: {$_file}");
        }

        // İç değişkenler _ prefix'li — çakışmayı önlemek için
        extract($_data, EXTR_OVERWRITE);
        $view = $this;

        ob_start();
        try {
            include $_file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }

    public function resolve(string $template): string
    {
        // Modül namespace: 'cart::index' → app/Modules/Cart/Views/index.php
        // Ancak modül klasör adı slug'la birebir olmayabilir (ör. 'sitetools' → 'SiteTools').
        // Bu durumda ModuleLoader üzerinden gerçek path bulunur.
        if (str_contains($template, '::')) {
            [$slug, $path] = explode('::', $template, 2);
            $relPath = 'Views/' . str_replace('.', '/', $path) . '.php';

            // 1) ModuleLoader kaydından gerçek klasör
            $info = \App\Core\ModuleLoader::get(strtolower($slug));
            if ($info) {
                $file = $info['path'] . '/' . $relPath;
                if (file_exists($file)) return $file;
            }

            // 2) Doğrudan ucfirst
            $file = $this->modulesPath . '/' . ucfirst($slug) . '/' . $relPath;
            if (file_exists($file)) return $file;

            // 3) Tüm modüllerde tara (slug eşleşen ilk klasör)
            foreach (glob($this->modulesPath . '/*', GLOB_ONLYDIR) as $dir) {
                if (strtolower(basename($dir)) === strtolower($slug)) {
                    $file = $dir . '/' . $relPath;
                    if (file_exists($file)) return $file;
                }
            }

            // 4) Bulunamadıysa hatayı üretecek path'i döndür (mevcut davranış)
            return $this->modulesPath . '/' . ucfirst($slug) . '/' . $relPath;
        }

        // Tema: 'layouts.public'
        return $this->themePath . '/' . str_replace('.', '/', $template) . '.php';
    }

    // ---- Template helpers (view içinden çağrılır) ----

    public function extend(string $layout): void
    {
        $this->extends = $layout;
    }

    public function section(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function endSection(): void
    {
        $name = array_pop($this->sectionStack);
        if ($name === null) {
            throw new RuntimeException('endSection() çağrıldı ama açık section yok.');
        }
        $this->sections[$name] = ob_get_clean();
    }

    public function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function include(string $template, array $data = []): string
    {
        return $this->renderFile($this->resolve($template), array_merge($this->sharedData, $data));
    }

    public function partial(string $template, array $data = []): string
    {
        // tema partials/ dizini
        $file = $this->themePath . '/partials/' . str_replace('.', '/', $template) . '.php';
        if (!file_exists($file)) {
            return '';
        }
        return $this->renderFile($file, array_merge($this->sharedData, $data));
    }

    /** Ham HTML — güvendiğin veri için */
    public function raw(mixed $value): string
    {
        return (string) $value;
    }

    /** Auto-escape */
    public function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
