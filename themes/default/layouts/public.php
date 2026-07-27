<?php
/** @var \App\Core\View $view */
$title = $title ?? __('common.app_name');
$description = $description ?? __('common.tagline');
$activeSkin = \App\Services\Theme\ThemeManager::active();
$stylesheets = \App\Services\Theme\ThemeManager::stylesheetsFor(\App\Services\Theme\ThemeManager::CONTEXT_SITE);
?><!doctype html>
<html lang="<?= e(\App\Core\SessionManager::get('locale', 'tr')) ?>"
      data-theme="light"
      data-theme-skin="<?= e($activeSkin) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title) ?> — <?= e(__('common.app_name')) ?></title>
    <meta name="description" content="<?= e($description) ?>">

    <script>
        (function () {
            try {
                var t = localStorage.getItem('aho-theme');
                if (!t && window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches) t = 'dark';
                if (t) document.documentElement.setAttribute('data-theme', t);
                var skin = document.cookie.match(/aho_theme=([^;]+)/);
                if (skin) document.documentElement.setAttribute('data-theme-skin', decodeURIComponent(skin[1]));
            } catch (e) {}
        })();
    </script>

    <link rel="icon" href="<?= asset('img/logo-icon.png') ?>" type="image/png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=Playfair+Display:wght@400;600;700;900&display=swap">

    <!-- Aktif tema (site bağlamı) -->
    <?php foreach ($stylesheets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>

    <!-- Modül-özel CSS (yapısal, tema-agnostik) -->
    <link rel="stylesheet" href="<?= asset('modules/cookie/cookie.css') ?>">
    <link rel="stylesheet" href="<?= asset('modules/theme/theme-switcher.css') ?>">
</head>
<body>
    <a href="#main" class="aho-skip-link">Ana içeriğe atla</a>

    <?php if (\App\Services\Auth\ImpersonationService::isActive()):
        $impState = \App\Services\Auth\ImpersonationService::currentState();
    ?>
        <div class="aho-impersonation-banner" style="background:#f59e0b;color:#1f2937;padding:10px 20px;text-align:center;font-weight:600;position:sticky;top:0;z-index:9999;display:flex;justify-content:center;align-items:center;gap:16px">
            🔐 Admin olarak <?= e($impState['customer_name'] ?? '') ?> müşterisinin paneline giriş yaptın.
            <form method="post" action="/admin/adina-giris/cik" style="display:inline">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <button type="submit" style="background:#1f2937;color:#fff;border:none;padding:6px 14px;border-radius:6px;cursor:pointer;font-weight:600">Admin Paneline Dön ✕</button>
            </form>
        </div>
    <?php endif; ?>

    <?= $view->include('header::topbar') ?>
    <?= $view->include('header::header') ?>

    <main id="main">
        <?= $view->yield('content') ?>
    </main>

    <?= $view->include('footer::footer') ?>

    <?= $view->partial('cookie-banner') ?>
    <?= $view->include('theme::switcher') ?>
    <?= $view->partial('ai-widget', ['context' => \App\Services\Auth\AuthService::isCustomer() ? 'customer' : 'public']) ?>

    <script src="<?= asset('js/theme.js') ?>" defer></script>
    <script src="<?= asset('modules/header/header.js') ?>" defer></script>
    <script src="<?= asset('modules/cookie/cookie.js') ?>" defer></script>
    <script src="<?= asset('modules/theme/theme-switcher.js') ?>" defer></script>
    <script src="<?= asset('js/ai-widget.js') ?>" defer></script>
</body>
</html>
