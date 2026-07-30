<?php
// WHMCS-style guest cart flow: product, domain, configurable options and add-ons before payment.

if (!function_exists('ao_cart_product_needs_domain')) {
    function ao_cart_product_needs_domain(array $product): bool
    {
        $haystack = strtolower(trim(
            ($product['group_slug'] ?? '') . ' ' .
            ($product['group_name'] ?? '') . ' ' .
            ($product['slug'] ?? '') . ' ' .
            ($product['name'] ?? '')
        ));
        foreach (['hosting', 'sunucu', 'cpanel', 'wordpress', 'reseller', 'vps'] as $needle) {
            if (str_contains($haystack, $needle)) return true;
        }
        return false;
    }
}

if (!function_exists('ao_cart_default_domain_meta')) {
    function ao_cart_default_domain_meta(array $extra = []): array
    {
        return array_merge([
            'type' => 'domain',
            'registration_years' => 1,
            'hosting_choice' => 'add_hosting',
            'nameservers' => [],
        ], $extra);
    }
}

if (!function_exists('ao_cart_builder_package_labels')) {
    function ao_cart_builder_package_labels(): array
    {
        return [
            'builder_type' => 'Builder Tipi',
            'template' => 'Şablon',
            'sitename' => 'Site Adı',
            'appname' => 'Uygulama Adı',
            'color' => 'Renk',
            'custom_color' => 'Özel Renk',
            'menu' => 'Menüler',
            'pages' => 'Sayfalar',
            'block_order' => 'Blok Sıralaması',
            'title' => 'Ön Sayfa Başlığı',
            'description' => 'Ön Sayfa Açıklaması',
            'subtitle' => 'Ön Sayfa Açıklaması',
            'cta' => 'Buton',
            'cta_secondary' => 'İkinci Buton',
            'slider' => 'Slider / Öne Çıkanlar',
            'cards' => 'Kartlar',
            'live_person' => 'DJ / Ekip / İçerik Sahibi',
            'live_time' => 'Yayın / Servis Saati',
            'player_title' => 'Player / Aksiyon Başlığı',
            'campaign' => 'Duyuru / Kampanya',
            'social' => 'Sosyal / İletişim',
            'flow' => 'Süreç / Panel Akışı',
            'payments' => 'Ödeme Yöntemleri',
            'output_format' => 'Çıktı',
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'builder/package-checkout') {
    verify_csrf();
    $builderType = preg_replace('~[^a-z0-9_-]+~i', '', (string)($_POST['builder_type'] ?? 'sitebuilder'));
    $outputFormat = preg_replace('~[^a-z0-9_-]+~i', '', (string)($_POST['output_format'] ?? 'source'));
    $slug = $builderType === 'mobilebuilder'
        ? ($outputFormat === 'apk' ? 'mobilebuilder-apk-output' : ($outputFormat === 'aab' ? 'mobilebuilder-aab-output' : 'mobilebuilder-source-code'))
        : 'sitebuilder-output-package';

    $product = null;
    try {
        $q = db()->prepare('SELECT p.*, g.name AS group_name FROM products p LEFT JOIN product_groups g ON g.id=p.group_id WHERE p.slug=? AND p.is_active=1 LIMIT 1');
        $q->execute([$slug]);
        $product = $q->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        error_log('[ao] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    }

    if (!$product) {
        flash('error', 'Builder çıktı paketi bulunamadı veya pasif.');
        redirect_to($builderType === 'mobilebuilder' ? 'mobilebuilder/create-demo' : 'sitebuilder/create-demo');
    }

    $price = (float)($product['price'] ?? 0);
    $currency = (string)($product['currency'] ?? 'TRY');
    $billing = (string)($product['billing_cycle'] ?? 'one_time');
    try {
        $pq = db()->prepare('SELECT cycle,price,price_try,price_usd,currency FROM product_pricing WHERE product_id=? AND is_active=1 AND price>=0 ORDER BY FIELD(cycle,"one_time","onetime","monthly","annually"), id LIMIT 1');
        $pq->execute([(int)$product['id']]);
        $pr = $pq->fetch(PDO::FETCH_ASSOC);
        if ($pr) {
            $priceTry = (float)($pr['price_try'] ?? 0);
            if ($priceTry <= 0 && (float)($pr['price_usd'] ?? 0) > 0 && function_exists('ao_v23_price_try')) $priceTry = (float)ao_v23_price_try((float)$pr['price_usd'], 'USD');
            if ($priceTry <= 0 && (float)($pr['price'] ?? 0) > 0) $priceTry = strtoupper((string)($pr['currency'] ?? 'TRY')) === 'TRY' || !function_exists('ao_v23_price_try') ? (float)$pr['price'] : (float)ao_v23_price_try((float)$pr['price'], (string)$pr['currency']);
            $price = $priceTry;
            $currency = 'TRY';
            $billing = (string)($pr['cycle'] ?? $billing);
        }
    } catch (Throwable $e) {
        error_log('[ao] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    }

    $labels = ao_cart_builder_package_labels();
    $customFields = [];
    $builderConfig = [];
    foreach ($labels as $key => $label) {
        $value = trim((string)($_POST[$key] ?? ''));
        if ($key === 'builder_type') $value = $builderType === 'mobilebuilder' ? 'Mobile Builder' : 'Site Builder';
        if ($key === 'output_format') $value = strtoupper($outputFormat);
        if ($value === '') continue;
        $builderConfig[$key] = $value;
        $customFields[] = ['key' => $key, 'label' => $label, 'type' => 'text', 'value' => $value];
    }

    $selectedBuilderAddons = array_values(array_unique(array_map('strval', (array)($_POST['builder_addons'] ?? []))));
    $addonItems = [];
    $provisionAddons = [];
    if ($selectedBuilderAddons) {
        try {
            $placeholders = implode(',', array_fill(0, count($selectedBuilderAddons), '?'));
            $params = array_merge([$slug], $selectedBuilderAddons);
            $aq = db()->prepare('SELECT a.addon_key,a.name,a.description,a.price,a.currency,a.provision_key,a.provision_value FROM product_checkout_addons a JOIN products p ON p.id=a.product_id WHERE p.slug=? AND a.is_active=1 AND a.addon_key IN ('.$placeholders.') ORDER BY a.sort_order,a.id');
            $aq->execute($params);
            foreach ($aq->fetchAll(PDO::FETCH_ASSOC) ?: [] as $addonRow) {
                $addonPrice = (float)($addonRow['price'] ?? 0);
                $addonCurrency = strtoupper((string)($addonRow['currency'] ?? 'TRY'));
                if ($addonPrice > 0 && $addonCurrency !== 'TRY' && function_exists('ao_v23_price_try')) {
                    $addonPrice = (float)ao_v23_price_try($addonPrice, $addonCurrency);
                    $addonCurrency = 'TRY';
                }
                $price += $addonPrice;
                $addonItem = [
                    'key' => (string)($addonRow['addon_key'] ?? ''),
                    'name' => (string)($addonRow['name'] ?? ''),
                    'description' => (string)($addonRow['description'] ?? ''),
                    'price' => $addonPrice,
                    'currency' => $addonCurrency,
                ];
                $addonItems[] = $addonItem;
                $customFields[] = ['key' => 'addon_'.$addonItem['key'], 'label' => 'Ek Modül', 'type' => 'text', 'value' => $addonItem['name'].' (+'.number_format($addonPrice, 2, '.', '').' '.$addonCurrency.')'];
                $provisionKey = trim((string)($addonRow['provision_key'] ?? ''));
                if ($provisionKey !== '') {
                    $provisionAddons[$provisionKey][] = [
                        'key' => $addonItem['key'],
                        'name' => $addonItem['name'],
                        'value' => trim((string)($addonRow['provision_value'] ?? '')),
                    ];
                }
            }
        } catch (Throwable $e) {
            error_log('[ao] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    if (!isset($_SESSION['ao_cart']) || !is_array($_SESSION['ao_cart'])) $_SESSION['ao_cart'] = [];
    $key = $slug . ':' . substr(hash('sha256', json_encode([$builderConfig, $selectedBuilderAddons], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)), 0, 10);
    $_SESSION['ao_cart'][$key] = [
        'slug' => $slug,
        'name' => (string)($product['name'] ?? 'Builder Çıktı Paketi'),
        'group' => (string)($product['group_name'] ?? 'Builder'),
        'price' => $price,
        'currency' => $currency,
        'cycle' => $billing ?: 'one_time',
        'qty' => 1,
        'domain_action' => 'existing',
        'domain_name' => '',
        'epp_code' => '',
        'addons' => array_map(static fn($item) => (string)($item['key'] ?? ''), $addonItems),
        'meta' => [
            'builder_type' => $builderType,
            'builder_config' => $builderConfig,
            'custom_fields' => $customFields,
            'addon_items' => $addonItems,
            'provision_addons' => $provisionAddons,
            'source_product_slug' => $slug,
        ],
    ];

    flash('success', 'Demo ayarlarına göre builder çıktı paketi oluşturuldu. Ödeme adımına geçebilirsiniz.');
    redirect_to('cart');
}

if ($route === 'cart/add') {
    $slug = trim((string)($_REQUEST['product'] ?? ''));
    $cycle = trim((string)($_REQUEST['cycle'] ?? ''));
    $domainOnly = ahost_domain_clean((string)($_REQUEST['domain'] ?? ''));
    $needsDomain = false;

    if ($slug === '' && $domainOnly !== '') {
        if (ahost_domain_valid($domainOnly)) {
            $availability = ao_domain_availability($domainOnly);
            if (!empty($availability['available'])) {
                $quote = ao_smart_domain_quote($domainOnly, 'register');
                if (!isset($_SESSION['ao_cart']) || !is_array($_SESSION['ao_cart'])) $_SESSION['ao_cart'] = [];

                $key = 'domain:' . $domainOnly;
                $_SESSION['ao_cart'][$key] = [
                    'slug' => $key,
                    'name' => 'Domain Kayıt: ' . $domainOnly,
                    'group' => 'Domain',
                    'price' => (float)($quote['sale_price'] ?? 0),
                    'currency' => $quote['currency'] ?? 'TRY',
                    'cycle' => 'annually',
                    'qty' => 1,
                    'domain_action' => 'register',
                    'domain_name' => $domainOnly,
                    'epp_code' => '',
                    'addons' => [],
                    'meta' => ao_cart_default_domain_meta(['registrar' => $quote['selected_registrar'] ?? 'domainnameapi']),
                ];

                flash('success', 'Domain sepete eklendi. Kayıt süresi, hosting ve nameserver seçeneklerini yapılandırabilirsiniz.');
                redirect_to('cart?step=domain-config');
            }
            flash('error', 'Bu domain kayıtlı görünüyor, sepete eklenmedi.');
        } else {
            flash('error', 'Geçerli bir domain yazın.');
        }
        redirect_to('domain');
    }

    $redirectAfterProduct = 'cart';

    if ($slug !== '') {
        try {
            $q = db()->prepare('SELECT p.*, g.name AS group_name, g.slug AS group_slug FROM products p LEFT JOIN product_groups g ON g.id=p.group_id WHERE p.slug=? AND p.is_active=1 LIMIT 1');
            $q->execute([$slug]);
            $p = $q->fetch();

            if ($p && function_exists('ao_module_filter_products') && !ao_module_filter_products([$p])) {
                $p = null;
                flash('warning', 'Bu ürünün bağlı olduğu özellik veya entegrasyon şu anda pasif.');
            }

            if ($p) {
                $needsDomain = ao_cart_product_needs_domain($p);
                $price = (float)($p['price'] ?? 0);
                $currency = $p['currency'] ?? 'TRY';
                $billing = $p['billing_cycle'] ?? 'monthly';

                try {
                    if ($cycle !== '') {
                        $pq = db()->prepare('SELECT cycle,price,price_try,price_usd,currency FROM product_pricing WHERE product_id=? AND is_active=1 AND price>=0 AND cycle=? LIMIT 1');
                        $pq->execute([(int)$p['id'], $cycle]);
                    } else {
                        $pq = db()->prepare('SELECT cycle,price,price_try,price_usd,currency FROM product_pricing WHERE product_id=? AND is_active=1 AND price>=0 ORDER BY FIELD(cycle,"monthly","annually","biennially","triennially","quarterly","semiannually","one_time","onetime"), id LIMIT 1');
                        $pq->execute([(int)$p['id']]);
                    }
                    $pr = $pq->fetch();
                    if ($pr) {
                        $priceTry = (float)($pr['price_try'] ?? 0);
                        if ($priceTry <= 0 && (float)($pr['price_usd'] ?? 0) > 0 && function_exists('ao_v23_price_try')) $priceTry = (float)ao_v23_price_try((float)$pr['price_usd'], 'USD');
                        if ($priceTry <= 0 && (float)($pr['price'] ?? 0) > 0) $priceTry = strtoupper((string)($pr['currency'] ?? 'TRY')) === 'TRY' || !function_exists('ao_v23_price_try') ? (float)$pr['price'] : (float)ao_v23_price_try((float)$pr['price'], (string)$pr['currency']);
                        $price = $priceTry;
                        $currency = 'TRY';
                        $billing = $pr['cycle'] ?: $billing;
                    }
                } catch (Throwable $e) {
                    error_log('[ao] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
                }

                $orderMeta = [];
                $metaKeys = [
                    'custom_disk', 'custom_traffic', 'custom_email', 'custom_database',
                    'radio_ip', 'whatsapp_number', 'facebook', 'instagram', 'twitter',
                    'youtube', 'tiktok', 'request_line_url', 'webscript_hosting_choice',
                    'webscript_install', 'panel_url', 'panel_username', 'panel_password', 'panel_note',
                ];
                foreach ($metaKeys as $mk) {
                    if (isset($_POST[$mk]) && trim((string)$_POST[$mk]) !== '') $orderMeta[$mk] = trim((string)$_POST[$mk]);
                }

                if (function_exists('ao_v2510_selected_config_summary')) {
                    $configSummary = ao_v2510_selected_config_summary((int)$p['id'], $_POST['config_options'] ?? []);
                    if (!empty($configSummary['items'])) {
                        $orderMeta['config_options'] = $configSummary['items'];
                        $orderMeta['config_options_total'] = (float)$configSummary['extra'];
                        $price += (float)$configSummary['extra'];
                    }
                }

                if (function_exists('ao_v249_product_custom_fields')) {
                    $customFields = ao_v249_product_custom_fields((int)$p['id'], (int)($p['group_id'] ?? 0));
                    $postedCustom = is_array($_POST['custom_fields'] ?? null) ? $_POST['custom_fields'] : [];
                    $customValues = [];
                    foreach ($customFields as $field) {
                        $fieldKey = (string)($field['field_key'] ?? '');
                        if ($fieldKey === '') continue;
                        $fieldType = (string)($field['field_type'] ?? 'text');
                        $label = (string)($field['label'] ?? $fieldKey);
                        $value = '';
                        if ($fieldType === 'file') {
                            $file = $_FILES['custom_files'] ?? null;
                            if (is_array($file) && !empty($file['tmp_name'][$fieldKey]) && is_uploaded_file($file['tmp_name'][$fieldKey])) {
                                $ext = strtolower(pathinfo((string)($file['name'][$fieldKey] ?? ''), PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'pdf'], true)) {
                                    $uploadDir = dirname(__DIR__, 2) . '/public/uploads/order-files';
                                    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);
                                    $fileName = 'field-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                                    if (@move_uploaded_file($file['tmp_name'][$fieldKey], $uploadDir . '/' . $fileName)) $value = 'uploads/order-files/' . $fileName;
                                }
                            }
                        } else {
                            $value = trim((string)($postedCustom[$fieldKey] ?? ''));
                        }
                        if (!empty($field['is_required']) && $value === '') {
                            flash('warning', $label . ' alanı zorunludur.');
                        }
                        if ($value !== '') {
                            $customValues[] = ['key' => $fieldKey, 'label' => $label, 'type' => $fieldType, 'value' => $value];
                        }
                    }
                    if ($customValues) $orderMeta['custom_fields'] = $customValues;
                }

                if (!empty($_FILES['logo_file']['tmp_name']) && is_uploaded_file($_FILES['logo_file']['tmp_name'])) {
                    $ext = strtolower(pathinfo((string)($_FILES['logo_file']['name'] ?? ''), PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true)) {
                        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/order-logos';
                        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);
                        $fileName = 'logo-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                        if (@move_uploaded_file($_FILES['logo_file']['tmp_name'], $uploadDir . '/' . $fileName)) $orderMeta['logo_file'] = 'uploads/order-logos/' . $fileName;
                    }
                }

                if (!isset($_SESSION['ao_cart']) || !is_array($_SESSION['ao_cart'])) $_SESSION['ao_cart'] = [];

                $key = $slug;
                if (isset($_SESSION['ao_cart'][$key])) {
                    $_SESSION['ao_cart'][$key]['qty'] = (int)($_SESSION['ao_cart'][$key]['qty'] ?? 1) + 1;
                    if ($orderMeta) $_SESSION['ao_cart'][$key]['meta'] = array_merge($_SESSION['ao_cart'][$key]['meta'] ?? [], $orderMeta);
                } else {
                    $_SESSION['ao_cart'][$key] = [
                        'slug' => $slug,
                        'name' => $p['name'],
                        'group' => $p['group_name'] ?? '',
                        'price' => $price,
                        'currency' => $currency,
                        'cycle' => $billing,
                        'qty' => 1,
                        'domain_action' => $needsDomain ? 'register' : 'existing',
                        'domain_name' => '',
                        'epp_code' => '',
                        'addons' => [],
                        'meta' => $orderMeta,
                    ];
                }

                flash('success', $needsDomain
                    ? 'Hosting sepete eklendi. Domain kaydetmek, transfer etmek veya mevcut domain/nameserver kullanmak için sepeti yapılandırın.'
                    : 'Ürün sepete eklendi.'
                );
                $redirectAfterProduct = $needsDomain ? 'cart?step=domain-choice' : 'cart';
            }
        } catch (Throwable $e) {
            flash('error', 'Ürün sepete eklenemedi.');
        }
    }

    redirect_to($redirectAfterProduct);
}

if ($route === 'cart/remove') {
    $slug = trim((string)($_GET['product'] ?? ''));
    if ($slug !== '' && isset($_SESSION['ao_cart'][$slug])) unset($_SESSION['ao_cart'][$slug]);
    redirect_to('cart');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'cart/update') {
    verify_csrf();
    $postedCoupon = strtoupper(trim((string)($_POST['coupon_code'] ?? '')));
    if ($postedCoupon !== '') {
        $_SESSION['ao_cart_coupon'] = preg_replace('~[^A-Z0-9_-]+~', '', $postedCoupon);
    } else {
        unset($_SESSION['ao_cart_coupon']);
    }

    foreach (($_POST['qty'] ?? []) as $slug => $qty) {
        $qty = max(0, (int)$qty);
        if (!isset($_SESSION['ao_cart'][$slug])) continue;
        if ($qty <= 0) {
            unset($_SESSION['ao_cart'][$slug]);
            continue;
        }

        $_SESSION['ao_cart'][$slug]['qty'] = $qty;
        $_SESSION['ao_cart'][$slug]['cycle'] = trim((string)(($_POST['cycle'][$slug] ?? $_SESSION['ao_cart'][$slug]['cycle'] ?? 'monthly')));

        $domainAction = trim((string)(($_POST['domain_action'][$slug] ?? 'register')));
        if (!in_array($domainAction, ['register', 'transfer', 'existing', 'dns'], true)) $domainAction = 'register';

        $_SESSION['ao_cart'][$slug]['domain_action'] = $domainAction;
        $_SESSION['ao_cart'][$slug]['domain_name'] = trim((string)(($_POST['domain_name'][$slug] ?? $_SESSION['ao_cart'][$slug]['domain_name'] ?? '')));
        $_SESSION['ao_cart'][$slug]['epp_code'] = $domainAction === 'transfer' ? trim((string)(($_POST['epp_code'][$slug] ?? ''))) : '';
        $_SESSION['ao_cart'][$slug]['addons'] = array_values(array_map('strval', $_POST['addons'][$slug] ?? []));
        $_SESSION['ao_cart'][$slug]['meta'] = $_SESSION['ao_cart'][$slug]['meta'] ?? [];
        $_SESSION['ao_cart'][$slug]['meta']['addon_items'] = [];
        $_SESSION['ao_cart'][$slug]['meta']['provision_addons'] = [];
        if ($_SESSION['ao_cart'][$slug]['addons']) {
            try {
                $placeholders = implode(',', array_fill(0, count($_SESSION['ao_cart'][$slug]['addons']), '?'));
                $params = array_merge([$slug], $_SESSION['ao_cart'][$slug]['addons']);
                $q = db()->prepare('SELECT a.addon_key,a.name,a.description,a.price,a.currency,a.provision_key,a.provision_value FROM product_checkout_addons a JOIN products p ON p.id=a.product_id WHERE p.slug=? AND a.is_active=1 AND a.addon_key IN ('.$placeholders.') ORDER BY a.sort_order,a.id');
                $q->execute($params);
                foreach($q->fetchAll(PDO::FETCH_ASSOC) ?: [] as $addonRow) {
                    $item = [
                        'key' => (string)($addonRow['addon_key'] ?? ''),
                        'name' => (string)($addonRow['name'] ?? ''),
                        'description' => (string)($addonRow['description'] ?? ''),
                        'price' => (float)($addonRow['price'] ?? 0),
                        'currency' => strtoupper((string)($addonRow['currency'] ?? 'TRY')),
                    ];
                    $_SESSION['ao_cart'][$slug]['meta']['addon_items'][] = $item;
                    $provisionKey = trim((string)($addonRow['provision_key'] ?? ''));
                    if ($provisionKey !== '') {
                        $_SESSION['ao_cart'][$slug]['meta']['provision_addons'][$provisionKey][] = [
                            'key' => $item['key'],
                            'name' => $item['name'],
                            'value' => trim((string)($addonRow['provision_value'] ?? '')),
                        ];
                    }
                }
            } catch (Throwable $e) {
                error_log('[ao] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            }
        }
        $_SESSION['ao_cart'][$slug]['meta']['registration_years'] = max(1, min(10, (int)(($_POST['registration_years'][$slug] ?? $_SESSION['ao_cart'][$slug]['meta']['registration_years'] ?? 1))));
        $hostingChoice = trim((string)(($_POST['hosting_choice'][$slug] ?? $_SESSION['ao_cart'][$slug]['meta']['hosting_choice'] ?? 'add_hosting')));
        if (!in_array($hostingChoice, ['add_hosting', 'use_existing_service', 'external_dns'], true)) $hostingChoice = 'add_hosting';
        $_SESSION['ao_cart'][$slug]['meta']['hosting_choice'] = $hostingChoice;
        $nameservers = array_values(array_filter(array_map('trim', (array)($_POST['nameservers'][$slug] ?? []))));
        $_SESSION['ao_cart'][$slug]['meta']['nameservers'] = array_slice($nameservers, 0, 6);

        if ($domainAction === 'transfer' && $_SESSION['ao_cart'][$slug]['domain_name'] !== '' && $_SESSION['ao_cart'][$slug]['epp_code'] === '') {
            flash('warning', 'Transfer seçilen domain için EPP / transfer kodu girilmelidir.');
        }

        try {
            $q = db()->prepare('SELECT pp.price,pp.price_try,pp.price_usd,pp.currency FROM product_pricing pp JOIN products p ON p.id=pp.product_id WHERE p.slug=? AND pp.cycle=? AND pp.is_active=1 LIMIT 1');
            $q->execute([$slug, $_SESSION['ao_cart'][$slug]['cycle']]);
            $pr = $q->fetch();
            if ($pr) {
                $extra = (float)($_SESSION['ao_cart'][$slug]['meta']['config_options_total'] ?? 0);
                $priceTry = (float)($pr['price_try'] ?? 0);
                if ($priceTry <= 0 && (float)($pr['price_usd'] ?? 0) > 0 && function_exists('ao_v23_price_try')) $priceTry = (float)ao_v23_price_try((float)$pr['price_usd'], 'USD');
                if ($priceTry <= 0 && (float)($pr['price'] ?? 0) > 0) $priceTry = strtoupper((string)($pr['currency'] ?? 'TRY')) === 'TRY' || !function_exists('ao_v23_price_try') ? (float)$pr['price'] : (float)ao_v23_price_try((float)$pr['price'], (string)$pr['currency']);
                $_SESSION['ao_cart'][$slug]['price'] = $priceTry + $extra;
                $_SESSION['ao_cart'][$slug]['currency'] = 'TRY';
            }
        } catch (Throwable $e) {
            error_log('[ao] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    redirect_to('cart');
}

