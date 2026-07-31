<?php
/** @var \App\Core\View $view */
$title = $title ?? 'Admin';
$activeSkin = \App\Services\Theme\ThemeManager::active();
$stylesheets = \App\Services\Theme\ThemeManager::stylesheetsFor(\App\Services\Theme\ThemeManager::CONTEXT_ADMIN);
// Admin panelinde de site tokens'ı gerekli (renkler, spacing vb.)
$siteBase = ['/themes/default/css/site/theme.css', '/themes/default/css/site/blocks.css', '/themes/default/css/site/forms.css', '/themes/default/css/site/cards.css'];
?><!doctype html>
<html lang="<?= e(\App\Core\SessionManager::get('locale', 'tr')) ?>"
      data-theme="light"
      data-theme-skin="<?= e($activeSkin) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <script>window.AHO_BASE_PATH = <?= json_encode(defined('AHO_BASE_PATH') ? AHO_BASE_PATH : '') ?>;</script>
    <title><?= e($title) ?> — Ahost Bilişim Admin</title>
    <script>
        (function () {
            try {
                var t = localStorage.getItem('aho-theme');
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

    <!-- Site tokens (renk, form, kart tabanı) + AI widget CSS -->
    <?php foreach ($siteBase as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="/themes/default/css/site/ai-widget.css">

    <!-- Aktif tema (admin bağlamı) -->
    <?php foreach ($stylesheets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="aho-admin">
    <?= $view->include('admin::layouts.sidebar') ?>
    <div class="aho-admin__main">
        <?= $view->include('admin::layouts.topbar') ?>
        <div class="aho-admin__content">
            <?= $view->yield('content') ?>
        </div>
    </div>

    <?= $view->partial('ai-widget', ['context' => 'admin']) ?>

    <script src="<?= asset('js/theme.js') ?>" defer></script>
    <script src="<?= asset('modules/admin/admin.js') ?>" defer></script>
    <script src="<?= asset('js/ai-widget.js') ?>" defer></script>
</body>
</html>
