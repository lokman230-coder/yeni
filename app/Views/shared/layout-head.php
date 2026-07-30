<?php
/**
 * Ahost One RC25 Layout Head
 * Core CSS uses the split core/component files. Admin sidebar CSS is loaded after admin overrides.
 */
$pageTitle = $pageTitle ?? 'Ahost One';
$aoHeadContext = $aoHeadContext ?? 'site';
$aoHeadTitleSuffix = $aoHeadTitleSuffix ?? 'Ahost One';
$aoHeadLang = function_exists('ao_current_language') ? ao_current_language() : 'tr';
$aoHeadScripts = $aoHeadScripts ?? [];
$aoHeadInlineScripts = $aoHeadInlineScripts ?? [];
$aoHeadExtraCss = $aoHeadExtraCss ?? [];
if (!is_array($aoHeadScripts)) { $aoHeadScripts = [$aoHeadScripts]; }
if (!is_array($aoHeadInlineScripts)) { $aoHeadInlineScripts = [$aoHeadInlineScripts]; }
if (!is_array($aoHeadExtraCss)) { $aoHeadExtraCss = [$aoHeadExtraCss]; }
$aoResolvedRoute = trim((string)($_SERVER['AHOST_ROUTE_RESOLVED'] ?? ''), '/');
$aoAssetRoot = dirname(__DIR__, 3) . '/public/assets/';
$aoCssExists = static function (string $path) use ($aoAssetRoot): bool {
    return is_file($aoAssetRoot . ltrim($path, '/\\'));
};
$aoRouteStarts = static function (string $route, array $prefixes): bool {
    foreach ($prefixes as $prefix) {
        $prefix = trim((string)$prefix, '/');
        if ($route === $prefix || str_starts_with($route, $prefix . '/')) {
            return true;
        }
    }
    return false;
};
$aoCoreCss = [
    'css/core/tokens.css',
    'css/core/reset.css',
    'css/core/typography.css',
    'css/components/buttons.css',
    'css/components/forms.css',
    'css/components/cards.css',
    'css/components/tabs.css',
    'css/components/alerts.css',
    'css/components/mobile-nav.css',
    'css/components/domain-popup.css',
];
$aoPostThemeCss = [];
if (($aoHeadContext ?? 'site') === 'site') {
    $aoPostThemeCss[] = 'css/site/hero.css';
    $aoPostThemeCss[] = 'css/site/page.css';
    $aoPostThemeCss[] = 'css/areas/builder/builder.css';
}
if (($aoHeadContext ?? 'site') === 'auth') {
    $aoCoreCss[] = 'css/components/auth.css';
}
$aoAreaCss = [];
if (($aoHeadContext ?? 'site') === 'site') {
    if ($aoResolvedRoute === '') {
        $aoAreaCss[] = 'css/site/home.css';
    }
    if ($aoRouteStarts($aoResolvedRoute, ['domain', 'domain-transfer', 'domain-fiyatlari', 'dusunce-bildir'])) {
        $aoAreaCss[] = 'css/site/domain.css';
    }
    if ($aoRouteStarts($aoResolvedRoute, ['cart', 'sepet', 'checkout', 'siparis'])) {
        $aoAreaCss[] = 'css/site/cart.css';
    }
    if ($aoRouteStarts($aoResolvedRoute, ['site-araclari', 'whois', 'dns', 'ssl', 'seo-analiz', 'site-hiz-testi', 'domain-degerleme'])) {
        $aoAreaCss[] = 'css/areas/tools/tools.css';
    }
    if ($aoRouteStarts($aoResolvedRoute, ['bilgi-bankasi', 'knowledge-base'])) {
        $aoAreaCss[] = 'css/site/knowledge.css';
    }
    if ($aoRouteStarts($aoResolvedRoute, ['urunler', 'urun', 'urun-grubu', 'hosting', 'backorder'])) {
        $aoAreaCss[] = 'css/site/products.css';
    }
    if ($aoRouteStarts($aoResolvedRoute, ['sitebuilder', 'mobilebuilder', 'web-tasarim', 'mobil-uygulama', 'android-uygulama'])) {
        $aoAreaCss[] = 'css/site/builder.css';
    }
    if ($aoRouteStarts($aoResolvedRoute, ['referanslar', 'references'])) {
        $aoAreaCss[] = 'css/site/references.css';
    }
    if ($aoRouteStarts($aoResolvedRoute, ['teklif', 'quotation'])) {
        $aoAreaCss[] = 'css/site/quotation.css';
    }
    if ($aoRouteStarts($aoResolvedRoute, ['iletisim', 'contact', 'hakkimizda', 'about'])) {
        $aoAreaCss[] = 'css/site/contact.css';
    }
}
if (($aoHeadContext ?? 'site') === 'auth') {
    $aoAreaCss[] = 'css/auth/login.css';
}
if (($aoHeadContext ?? 'site') === 'admin') {
    if ($aoRouteStarts($aoResolvedRoute, ['admin/customers'])) {
        $aoAreaCss[] = 'css/admin/areas/customers.css';
    }
    if ($aoRouteStarts($aoResolvedRoute, ['admin/accounting'])) {
        $aoAreaCss[] = 'css/admin/areas/accounting.css';
    }
    if ($aoRouteStarts($aoResolvedRoute, ['admin/product-center'])) {
        $aoAreaCss[] = 'css/admin/areas/products.css';
    }
    if ($aoRouteStarts($aoResolvedRoute, ['admin/builder-pro', 'admin/site-builder', 'admin/mobile-builder'])) {
        $aoAreaCss[] = 'css/admin/areas/builder.css';
    }
    if ($aoRouteStarts($aoResolvedRoute, ['admin/site-heroes'])) {
        $aoAreaCss[] = 'css/admin/areas/site-heroes.css';
    }
}
$aoAreaCss = array_values(array_filter($aoAreaCss, $aoCssExists));
if (($aoHeadContext ?? 'site') !== 'admin') {
    $aoPostThemeCss = array_values(array_filter(array_unique(array_merge($aoPostThemeCss, $aoAreaCss)), $aoCssExists));
} else {
    $aoPostThemeCss = array_values(array_filter(array_unique($aoPostThemeCss), $aoCssExists));
}
if (($aoHeadContext ?? 'site') === 'customer') {
    $aoCoreCss[] = 'css/customer/support.css';
    $aoCoreCss[] = 'css/customer/profile.css';
}
// Load base CSS first, then page/context extras.
// Context-specific admin/customer layers are loaded after the shared shell.
$aoCoreCss = array_values(array_filter(array_unique(array_merge($aoCoreCss, $aoHeadExtraCss))));

$aoCoreJs = ['js/ao-ui.js','js/front/site-currency-language.js'];
if (($aoHeadContext ?? 'site') === 'admin') {
    $aoCoreJs[] = 'js/admin.js';
}
if (in_array(($aoHeadContext ?? 'site'), ['site','customer'], true)) {
    $aoCoreJs[] = 'js/domain-popup.js';
    $aoCoreJs[] = 'js/builder-package-guard.js';
}
$aoHeadScripts = array_values(array_filter(array_unique(array_merge($aoCoreJs, $aoHeadScripts))));
$aoSeoPublic = function_exists('admin_setting') && in_array(($aoHeadContext ?? 'site'), ['site','auth'], true);
$aoPageMetaTitle = trim((string)($metaTitle ?? $seoTitle ?? ''));
$aoSeoTitle = $aoSeoPublic ? ($aoPageMetaTitle ?: trim((string)admin_setting('seo_title', ''))) : '';
$aoSeoDescription = $aoSeoPublic ? trim((string)($metaDescription ?? $seoDescription ?? admin_setting('seo_description', ''))) : '';
$aoSeoKeywords = $aoSeoPublic ? trim((string)($metaKeywords ?? admin_setting('seo_keywords', ''))) : '';
$aoSeoRobots = $aoSeoPublic ? trim((string)($metaRobots ?? admin_setting('seo_robots', 'index,follow'))) : '';
$aoSeoGaId = $aoSeoPublic ? trim((string)admin_setting('seo_google_analytics_id', admin_setting('google_analytics_id', ''))) : '';
$aoSeoGtmId = $aoSeoPublic ? trim((string)admin_setting('seo_google_tag_manager_id', admin_setting('google_tag_manager_id', ''))) : '';
$aoSeoGoogleVerify = $aoSeoPublic ? trim((string)admin_setting('seo_google_site_verification', '')) : '';
$aoSeoBingVerify = $aoSeoPublic ? trim((string)admin_setting('seo_bing_site_verification', '')) : '';
$aoSeoYandexVerify = $aoSeoPublic ? trim((string)admin_setting('seo_yandex_verification', '')) : '';
$aoSeoSitemap = $aoSeoPublic ? trim((string)($sitemapUrl ?? admin_setting('seo_sitemap_url', ''))) : '';
if ($aoSeoPublic && $aoSeoSitemap === '' && function_exists('url')) {
    $aoSeoSitemap = url('sitemap.xml');
}
if ($aoSeoPublic && $aoSeoSitemap !== '' && function_exists('url')) {
    $aoCurrentBasePath = trim((string)parse_url(url(''), PHP_URL_PATH), '/');
    $aoSitemapPath = trim((string)parse_url($aoSeoSitemap, PHP_URL_PATH), '/');
    if ($aoCurrentBasePath !== '' && $aoSitemapPath !== '' && !str_starts_with($aoSitemapPath, $aoCurrentBasePath)) {
        $aoSeoSitemap = url('sitemap.xml');
    }
}
$aoSeoOgTitle = $aoSeoPublic ? trim((string)($ogTitle ?? admin_setting('seo_og_title', ''))) : '';
$aoSeoOgDescription = $aoSeoPublic ? trim((string)($ogDescription ?? $aoSeoDescription)) : '';
$aoSeoOgImage = $aoSeoPublic ? trim((string)($ogImage ?? admin_setting('seo_og_image', ''))) : '';
$aoSeoTwitterCard = $aoSeoPublic ? trim((string)($twitterCard ?? admin_setting('seo_twitter_card', 'summary_large_image'))) : '';
$aoSeoTwitterSite = $aoSeoPublic ? trim((string)($twitterSite ?? admin_setting('seo_twitter_site', ''))) : '';
$aoSeoSiteName = $aoSeoPublic ? trim((string)admin_setting('site_name', admin_setting('company_name', 'Ahost One'))) : '';
$aoSeoCanonical = '';
if ($aoSeoPublic && (string)admin_setting('seo_canonical_enabled', '1') === '1') {
    $aoSeoCanonical = trim((string)($canonicalUrl ?? admin_setting('seo_canonical_url', '')));
    if ($aoSeoCanonical === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $pathOnly = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $aoSeoCanonical = $scheme.'://'.($_SERVER['HTTP_HOST'] ?? 'localhost').$pathOnly;
    }
}
$aoSeoLanguageAlternates = [];
if ($aoSeoPublic && $aoSeoCanonical !== '' && function_exists('ao_language_options')) {
    foreach ((array)ao_language_options() as $aoLanguageCode => $aoLanguageLabel) {
        $aoLanguageCode = strtolower(trim((string)$aoLanguageCode));
        if ($aoLanguageCode === '') continue;
        $aoSeoLanguageAlternates[$aoLanguageCode] = $aoSeoCanonical . (str_contains($aoSeoCanonical, '?') ? '&' : '?') . 'lang=' . rawurlencode($aoLanguageCode);
    }
}
$aoSeoSchemaJson = '';
if ($aoSeoPublic) {
    $customSchema = trim((string)($schemaJsonLd ?? admin_setting('seo_custom_schema_jsonld', '')));
    if ($customSchema !== '' && json_decode($customSchema, true) !== null) {
        $aoSeoSchemaJson = $customSchema;
    } else {
        $sameAs = array_values(array_filter(array_map('trim', preg_split('/\R+/', (string)admin_setting('seo_schema_same_as', '')) ?: [])));
        $aoSchemaUrl = trim((string)admin_setting('site_url', url('')));
        if (function_exists('url')) {
            $aoCurrentBasePath = trim((string)parse_url(url(''), PHP_URL_PATH), '/');
            $aoSchemaPath = trim((string)parse_url($aoSchemaUrl, PHP_URL_PATH), '/');
            if ($aoCurrentBasePath !== '' && $aoSchemaPath !== '' && !str_starts_with($aoSchemaPath, $aoCurrentBasePath)) {
                $aoSchemaUrl = url('');
            }
        }
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => trim((string)admin_setting('seo_schema_type', 'Organization')) ?: 'Organization',
            'name' => trim((string)admin_setting('seo_schema_name', admin_setting('company_name', 'Ahost One'))),
            'url' => $aoSchemaUrl,
        ];
        $logo = trim((string)admin_setting('seo_schema_logo', admin_setting('logo_url', '')));
        $phone = trim((string)admin_setting('seo_schema_phone', admin_setting('company_phone', '')));
        $address = trim((string)admin_setting('seo_schema_address', admin_setting('company_address', '')));
        if ($logo !== '') $schema['logo'] = $logo;
        if ($phone !== '') $schema['telephone'] = $phone;
        if ($address !== '') $schema['address'] = $address;
        if ($sameAs) $schema['sameAs'] = $sameAs;
        $aoSeoSchemaJson = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
$aoSeoExtraHead = $aoSeoPublic ? trim((string)admin_setting('seo_head_extra', '')) : '';
$aoSeoRecaptchaKey = ($aoSeoPublic && (string)admin_setting('recaptcha_public_script_enabled', '0') === '1') ? trim((string)admin_setting('recaptcha_site_key', '')) : '';
$aoSeoRecaptchaVersion = $aoSeoPublic ? trim((string)admin_setting('recaptcha_version', 'v2')) : 'v2';
$aoBrowserTitle = $aoSeoTitle !== '' ? $aoSeoTitle : ($pageTitle.($aoHeadTitleSuffix ? ' - '.$aoHeadTitleSuffix : ''));
?>
<!doctype html>
<html lang="<?= e($aoHeadLang ?: 'tr') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <title><?= e($aoBrowserTitle) ?></title>
  <?php if ($aoSeoDescription !== ''): ?><meta name="description" content="<?= e($aoSeoDescription) ?>"><?php endif; ?>
  <?php if ($aoSeoKeywords !== ''): ?><meta name="keywords" content="<?= e($aoSeoKeywords) ?>"><?php endif; ?>
  <?php if ($aoSeoRobots !== ''): ?><meta name="robots" content="<?= e($aoSeoRobots) ?>"><?php endif; ?>
  <?php if ($aoSeoCanonical !== ''): ?><link rel="canonical" href="<?= e($aoSeoCanonical) ?>"><?php endif; ?>
  <?php foreach ($aoSeoLanguageAlternates as $aoLanguageCode => $aoLanguageUrl): ?><link rel="alternate" hreflang="<?= e($aoLanguageCode) ?>" href="<?= e($aoLanguageUrl) ?>"><?php endforeach; ?>
  <?php if ($aoSeoCanonical !== '' && $aoSeoLanguageAlternates): ?><link rel="alternate" hreflang="x-default" href="<?= e($aoSeoCanonical) ?>"><?php endif; ?>
  <?php if ($aoSeoSitemap !== ''): ?><link rel="sitemap" type="application/xml" title="Sitemap" href="<?= e($aoSeoSitemap) ?>"><?php endif; ?>
  <?php if ($aoSeoGoogleVerify !== ''): ?><meta name="google-site-verification" content="<?= e($aoSeoGoogleVerify) ?>"><?php endif; ?>
  <?php if ($aoSeoBingVerify !== ''): ?><meta name="msvalidate.01" content="<?= e($aoSeoBingVerify) ?>"><?php endif; ?>
  <?php if ($aoSeoYandexVerify !== ''): ?><meta name="yandex-verification" content="<?= e($aoSeoYandexVerify) ?>"><?php endif; ?>
  <?php if ($aoSeoSiteName !== ''): ?><meta property="og:site_name" content="<?= e($aoSeoSiteName) ?>"><?php endif; ?>
  <meta property="og:type" content="website">
  <meta property="og:locale" content="<?= e(($aoHeadLang ?: 'tr') === 'tr' ? 'tr_TR' : $aoHeadLang) ?>">
  <?php if ($aoSeoTitle !== '' || $aoSeoOgTitle !== ''): ?><meta property="og:title" content="<?= e($aoSeoOgTitle ?: $aoSeoTitle) ?>"><?php endif; ?>
  <?php if ($aoSeoOgDescription !== ''): ?><meta property="og:description" content="<?= e($aoSeoOgDescription) ?>"><?php endif; ?>
  <?php if ($aoSeoCanonical !== ''): ?><meta property="og:url" content="<?= e($aoSeoCanonical) ?>"><?php endif; ?>
  <?php if ($aoSeoOgImage !== ''): ?><meta property="og:image" content="<?= e($aoSeoOgImage) ?>"><?php endif; ?>
  <?php if ($aoSeoTwitterCard !== ''): ?><meta name="twitter:card" content="<?= e($aoSeoTwitterCard) ?>"><?php endif; ?>
  <?php if ($aoSeoTwitterSite !== ''): ?><meta name="twitter:site" content="<?= e($aoSeoTwitterSite) ?>"><?php endif; ?>
  <?php if ($aoSeoTitle !== '' || $aoSeoOgTitle !== ''): ?><meta name="twitter:title" content="<?= e($aoSeoOgTitle ?: $aoSeoTitle) ?>"><?php endif; ?>
  <?php if ($aoSeoOgDescription !== ''): ?><meta name="twitter:description" content="<?= e($aoSeoOgDescription) ?>"><?php endif; ?>
  <?php if ($aoSeoOgImage !== ''): ?><meta name="twitter:image" content="<?= e($aoSeoOgImage) ?>"><?php endif; ?>
  <?php if ($aoSeoGaId !== ''): ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e(rawurlencode($aoSeoGaId)) ?>"></script>
  <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',<?= json_encode($aoSeoGaId) ?>);</script>
  <?php endif; ?>
  <?php if ($aoSeoGtmId !== ''): ?>
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+encodeURIComponent(i)+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',<?= json_encode($aoSeoGtmId) ?>);</script>
  <?php endif; ?>
  <?php if ($aoSeoRecaptchaKey !== ''): ?>
  <script defer src="https://www.google.com/recaptcha/api.js<?= $aoSeoRecaptchaVersion === 'v3' ? '?render='.e(rawurlencode($aoSeoRecaptchaKey)) : '' ?>"></script>
  <?php endif; ?>
  <?php if ($aoSeoSchemaJson !== ''): ?><script type="application/ld+json"><?= $aoSeoSchemaJson ?></script><?php endif; ?>
  <?php if ($aoSeoExtraHead !== ''): ?><?= $aoSeoExtraHead ?><?php endif; ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">


  <?php foreach ($aoCoreCss as $cssFile): ?>
  <link rel="stylesheet" href="<?= assetv($cssFile) ?>">
  <?php endforeach; ?>
  <?php $aoThemeCssFiles = (($aoHeadContext ?? 'site') === 'admin') ? [] : (function_exists('ao_theme_asset_urls') ? ao_theme_asset_urls($aoHeadContext, 'css') : (function_exists('ao_theme_asset_url') ? array_filter([ao_theme_asset_url($aoHeadContext, 'assets/css/theme.css')]) : [])); ?>
  <?php
    if (($aoHeadContext ?? 'site') === 'customer' && function_exists('ao_theme_asset_url')) {
        foreach (['assets/css/tokens.css', 'assets/css/header.css'] as $aoSiteHeaderCssFile) {
            $aoSiteHeaderCssUrl = ao_theme_asset_url('site', $aoSiteHeaderCssFile);
            if ($aoSiteHeaderCssUrl !== '') {
                $aoThemeCssFiles[$aoSiteHeaderCssUrl] = $aoSiteHeaderCssUrl;
            }
        }
        $aoThemeCssFiles = array_values(array_unique($aoThemeCssFiles));
    }
  ?>
  <?php foreach ($aoThemeCssFiles as $aoThemeCss): ?><link rel="stylesheet" href="<?= e($aoThemeCss) ?>"><?php endforeach; ?>
  <?php foreach ($aoPostThemeCss as $cssFile): ?>
  <link rel="stylesheet" href="<?= assetv($cssFile) ?>">
  <?php endforeach; ?>
  <?php if (($aoHeadContext ?? 'site') === 'admin'): ?>
    <?php foreach (['css/admin/base.css','css/admin/modules.css','css/admin/builder.css','css/admin/customer.css','css/admin/components.css','css/admin/sidebar.css','css/admin/header.css','css/admin/dashboard.css'] as $aoAdminCss): ?>
      <link rel="stylesheet" href="<?= assetv($aoAdminCss) ?>">
    <?php endforeach; ?>
    <?php foreach ($aoAreaCss as $cssFile): ?>
      <link rel="stylesheet" href="<?= assetv($cssFile) ?>">
    <?php endforeach; ?>
  <?php endif; ?>
  <meta name="ahost-base-url" content="<?= e(rtrim(url(''), '/')) ?>">
    <meta name="csrf-token" content="<?= e(function_exists('csrf_token')?csrf_token(): '') ?>">
  <?php foreach ($aoHeadScripts as $jsFile): if(!$jsFile) continue; ?>
  <script defer src="<?= assetv($jsFile) ?>"></script>
  <?php endforeach; ?>
  <?php $aoThemeHeaderJs = (($aoHeadContext ?? 'site') === 'admin' || !function_exists('ao_theme_asset_url')) ? '' : ao_theme_asset_url($aoHeadContext, 'assets/js/header.js'); ?>
  <?php if ($aoThemeHeaderJs): ?><script defer src="<?= e($aoThemeHeaderJs) ?>"></script><?php endif; ?>
  <?php $aoThemeJs = function_exists('ao_theme_asset_url') ? ao_theme_asset_url($aoHeadContext, 'assets/js/theme.js') : ''; ?>
  <?php if ($aoThemeJs): ?><script defer src="<?= e($aoThemeJs) ?>"></script><?php endif; ?>
  <?php foreach ($aoHeadInlineScripts as $script): if(!$script) continue; ?>
  <script><?= $script ?></script>
  <?php endforeach; ?>
</head>


