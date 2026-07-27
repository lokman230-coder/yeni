<?php
/** @var \App\Core\View $view */
$title = $title ?? 'Ahost Bilişim';
$activeSkin = \App\Services\Theme\ThemeManager::active();
// Blank layout admin login sayfası için kullanılıyor → admin bağlamı yükle
$stylesheets = \App\Services\Theme\ThemeManager::stylesheetsFor(\App\Services\Theme\ThemeManager::CONTEXT_ADMIN);
$siteBase = ['/themes/default/css/site/theme.css', '/themes/default/css/site/blocks.css', '/themes/default/css/site/forms.css', '/themes/default/css/site/cards.css'];
?><!doctype html>
<html lang="<?= e(\App\Core\SessionManager::get('locale', 'tr')) ?>"
      data-theme="light"
      data-theme-skin="<?= e($activeSkin) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title) ?> — Ahost Bilişim</title>
    <script>(function(){try{var t=localStorage.getItem('aho-theme');if(t)document.documentElement.setAttribute('data-theme',t);var s=document.cookie.match(/aho_theme=([^;]+)/);if(s)document.documentElement.setAttribute('data-theme-skin',decodeURIComponent(s[1]));}catch(e){}})();</script>
    <link rel="icon" href="<?= asset('img/logo-icon.png') ?>" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=Playfair+Display:wght@400;600;700;900&display=swap">
    <?php foreach ($siteBase as $href): ?><link rel="stylesheet" href="<?= e($href) ?>"><?php endforeach; ?>
    <?php foreach ($stylesheets as $href): ?><link rel="stylesheet" href="<?= e($href) ?>"><?php endforeach; ?>
</head>
<body>
    <?= $view->yield('content') ?>
    <script src="<?= asset('js/theme.js') ?>" defer></script>
</body>
</html>
