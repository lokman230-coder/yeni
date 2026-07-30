<?php
$pageTitle = $pageTitle ?? 'Ahost One';
$aoHeadContext = 'site';
$aoHeadTitleSuffix = 'Ahost One';
require __DIR__ . '/../../shared/layout-head.php';
?>
<body data-app="site" class="site-body <?= function_exists('ao_theme_body_class') ? e(ao_theme_body_class('site')) : '' ?>">
<?php $aoHeaderContext = 'site'; require __DIR__ . '/../../shared/header.php'; ?>
<main class="site-main">
