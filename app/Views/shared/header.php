<?php
/**
 * Public header bridge.
 *
 * The visible site/auth/customer header belongs to the active site theme.
 * This bridge only resolves and includes:
 *   public/themes/{active-theme}/partials/header.php
 *
 * Admin panel screens do not use this public header.
 */
$aoHeaderContext = $aoHeaderContext ?? 'site';

if (!function_exists('ao_theme_partial_path')) {
    function ao_theme_partial_path(string $partial, string $area = 'site'): string
    {
        $partial = trim(str_replace('\\', '/', $partial), '/');
        if ($partial === '' || str_contains($partial, '..')) {
            return '';
        }

        $area = $area === 'customer' || $area === 'auth' ? 'site' : ($area ?: 'site');
        $theme = function_exists('ao_active_theme') ? (ao_active_theme($area) ?: []) : [];
        $slug = function_exists('ao_theme_safe_slug')
            ? ao_theme_safe_slug((string)($theme['slug'] ?? ''))
            : preg_replace('/[^a-z0-9_-]+/i', '', (string)($theme['slug'] ?? ''));

        if ($slug === '') {
            $slug = 'prism';
        }

        $root = dirname(__DIR__, 3);
        $candidates = [];
        $slugs = function_exists('ao_theme_slug_candidates') ? ao_theme_slug_candidates($slug) : [$slug];
        foreach ($slugs as $candidateSlug) {
            $candidateSlug = trim((string)$candidateSlug);
            if ($candidateSlug === '') {
                continue;
            }
            $candidates[] = $root . '/public/themes/' . $candidateSlug . '/partials/' . $partial;
            $candidates[] = $root . '/themes/' . $candidateSlug . '/partials/' . $partial;
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return '';
    }
}

$aoThemeHeader = ao_theme_partial_path('header.php', 'site');
if ($aoThemeHeader !== '') {
    require $aoThemeHeader;
    return;
}

// Minimal fallback for an incomplete uploaded theme package.
?>
<header class="ao-public-header ao-theme-header-fallback">
  <div class="ao-public-header__inner">
    <a class="ao-brand" href="<?= function_exists('url') ? url('') : '/' ?>"><span>Ahost One</span></a>
  </div>
</header>
