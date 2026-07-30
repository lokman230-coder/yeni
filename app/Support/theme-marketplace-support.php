<?php
// v9.3.0 Intelligence & Marketplace Pro + Theme Center Pro
function ao_schema_ensure_v930() {
    static $done=false; if($done) return; $done=true;
    try { db()->exec("CREATE TABLE IF NOT EXISTS themes (id int(11) NOT NULL AUTO_INCREMENT, slug varchar(80) NOT NULL, name varchar(160) NOT NULL, area varchar(40) DEFAULT 'site', description text DEFAULT NULL, preview_image varchar(255) DEFAULT NULL, primary_color varchar(20) DEFAULT '#2563eb', secondary_color varchar(20) DEFAULT '#7c3aed', font_family varchar(120) DEFAULT 'Inter, Arial, sans-serif', is_active tinyint(1) DEFAULT 0, status varchar(30) DEFAULT 'installed', created_at timestamp NOT NULL DEFAULT current_timestamp(), updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(), PRIMARY KEY(id), UNIQUE KEY slug_area(slug,area)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS marketplace_listings (id int(11) NOT NULL AUTO_INCREMENT, seller_type enum('admin','customer') DEFAULT 'admin', seller_customer_id int(11) DEFAULT NULL, listing_type varchar(60) DEFAULT 'domain', title varchar(190) NOT NULL, domain_name varchar(190) DEFAULT NULL, description text DEFAULT NULL, category varchar(120) DEFAULT NULL, price decimal(14,2) DEFAULT 0, currency varchar(10) DEFAULT 'TRY', status enum('draft','active','pending','sold','passive') DEFAULT 'draft', is_featured tinyint(1) DEFAULT 0, featured_until datetime DEFAULT NULL, is_premium tinyint(1) DEFAULT 0, is_urgent tinyint(1) DEFAULT 0, views int(11) DEFAULT 0, created_at timestamp NOT NULL DEFAULT current_timestamp(), updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(), PRIMARY KEY(id), KEY status(status), KEY listing_type(listing_type), KEY domain_name(domain_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS marketplace_offers (id int(11) NOT NULL AUTO_INCREMENT, listing_id int(11) NOT NULL, customer_id int(11) DEFAULT NULL, name varchar(160) DEFAULT NULL, email varchar(190) DEFAULT NULL, offer_amount decimal(14,2) NOT NULL, currency varchar(10) DEFAULT 'TRY', message text DEFAULT NULL, status enum('pending','accepted','rejected','countered') DEFAULT 'pending', created_at timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY(id), KEY listing_id(listing_id), KEY status(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS marketplace_feature_packages (id int(11) NOT NULL AUTO_INCREMENT, name varchar(160) NOT NULL, days int(11) NOT NULL, price decimal(14,2) NOT NULL, currency varchar(10) DEFAULT 'TRY', badge varchar(80) DEFAULT 'Öne Çıkan', is_active tinyint(1) DEFAULT 1, created_at timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY(id), UNIQUE KEY uniq_feature_days(days)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS domain_intelligence_reports (id int(11) NOT NULL AUTO_INCREMENT, domain_name varchar(190) NOT NULL, ssl_score int(11) DEFAULT 0, dns_score int(11) DEFAULT 0, seo_score int(11) DEFAULT 0, traffic_score int(11) DEFAULT 0, valuation_score int(11) DEFAULT 0, estimated_value decimal(14,2) DEFAULT 0, currency varchar(10) DEFAULT 'TRY', summary text DEFAULT NULL, raw_json longtext DEFAULT NULL, created_at timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY(id), KEY domain_name(domain_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $themes=[
        ['ahost-default','Ahost One','#2563eb','#0f172a'],
        ['ahost-prism','Ahost One Prism','#ff675d','#19b7a2'],
        ['ahostone-visual-exact','Ahostone Visual Exact','#0967f2','#0b1736']
    ];
    foreach($themes as $i=>$t){ try{ db()->prepare("INSERT IGNORE INTO themes(slug,name,area,description,primary_color,secondary_color,is_active,status) VALUES(?,?,?,?,?,?,?, 'installed')")->execute([$t[0],$t[1],'site',$t[1].' site ön yüz teması.',$t[2],$t[3],$i===0?1:0]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
    try { db()->exec("ALTER TABLE marketplace_feature_packages ADD UNIQUE KEY uniq_feature_days(days)"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("DELETE p1 FROM marketplace_feature_packages p1 JOIN marketplace_feature_packages p2 ON p1.days=p2.days AND p1.id>p2.id"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    foreach([['Öne Çıkarma 7 Gün',7,99],['Öne Çıkarma 15 Gün',15,179],['Öne Çıkarma 30 Gün',30,299],['Öne Çıkarma 60 Gün',60,499]] as $p){ try{ db()->prepare("INSERT INTO marketplace_feature_packages(name,days,price,currency,badge,is_active) VALUES(?,?,?,?,?,1) ON DUPLICATE KEY UPDATE name=VALUES(name),price=VALUES(price),currency=VALUES(currency),badge=VALUES(badge),is_active=1")->execute([$p[0],$p[1],$p[2],'TRY','Öne Çıkan']); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
    try{ save_setting('ahost_version','25.0.0-rc25'); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
ao_schema_ensure_v930();
function ao_active_theme($area='site'){
    ao_schema_ensure_v930();
    $area = $area === 'customer' ? 'client' : $area;
    $area = $area === 'auth' ? 'site' : $area;
    // Real theme preview mode: admin can browse whole site/admin/client with a temporary theme id.
    $previewId=(int)($_GET['theme_preview'] ?? ($_SESSION['theme_preview_id'] ?? 0));
    if($previewId && current_admin()){
        try{ $q=db()->prepare('SELECT * FROM themes WHERE id=? LIMIT 1'); $q->execute([$previewId]); if($r=$q->fetch()) return $r; }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    }
    // Customer personal preferences: site + client themes can be client-specific.
    $customer=current_customer();
    if($customer && in_array($area,['site','client'],true)){
        try{
            db()->exec("CREATE TABLE IF NOT EXISTS client_preferences (id INT AUTO_INCREMENT PRIMARY KEY, client_id INT NOT NULL, site_theme_id INT NULL, client_theme_id INT NULL, builder_layout_json LONGTEXT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_client_pref(client_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $q=db()->prepare('SELECT * FROM client_preferences WHERE client_id=? LIMIT 1'); $q->execute([(int)$customer['id']]); $pref=$q->fetch();
            $themeId=(int)($area==='site' ? ($pref['site_theme_id'] ?? 0) : ($pref['client_theme_id'] ?? 0));
            if($themeId){ $t=db()->prepare('SELECT * FROM themes WHERE id=? LIMIT 1'); $t->execute([$themeId]); if($r=$t->fetch()) return $r; }
        }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    }
    try{ $q=db()->prepare('SELECT * FROM themes WHERE area=? AND is_active=1 LIMIT 1'); $q->execute([$area]); if($r=$q->fetch()) return $r; }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return ['slug'=>'ahost-default','name'=>'Ahost Default','primary_color'=>'#2563eb','secondary_color'=>'#0f172a','font_family'=>'Inter, Arial, sans-serif','radius'=>'24px','button_radius'=>'16px'];
}
function ao_theme_style_vars($area='site'){
    $t=ao_active_theme($area);
    $safeCssSize = function($value, $default) {
        $value = trim((string)$value);
        return preg_match('/^\d{1,4}(\.\d+)?(px|rem|em|%)$/', $value) ? $value : $default;
    };
    $safeCssColor = function($value, $default) {
        $value = trim((string)$value);
        return preg_match('/^#[0-9a-fA-F]{3,8}$/', $value) ? $value : $default;
    };
    $safeChoice = function($value, array $allowed, $default) {
        $value = trim((string)$value);
        return in_array($value, $allowed, true) ? $value : $default;
    };
    $radius=$t['radius'] ?? '24px'; $button=$t['button_radius'] ?? '16px';
    $bg=$t['background_color'] ?? '#f8fbff'; $gradient=$t['background_gradient'] ?? '';
    $primary=$t['primary_color'] ?? '#2563eb';
    $secondary=$t['secondary_color'] ?? '#0f172a';
    if (($t['slug'] ?? '') === 'ahost-prism' && function_exists('admin_setting')) {
        $primary = admin_setting('prism_primary_color', $primary ?: '#ff675d');
        $secondary = admin_setting('prism_secondary_color', $secondary ?: '#19b7a2');
        $radius = admin_setting('prism_card_radius', $radius ?: '26px');
        $button = admin_setting('prism_button_radius', $button ?: '16px');
        $bg = admin_setting('prism_site_background_color', admin_setting('prism_surface_color', $bg ?: '#fff8ef'));
    }
    $layoutWidth = $safeChoice($t['layout_width'] ?? 'boxed', ['boxed','wide','full'], 'boxed');
    $headerMode = $safeChoice($t['header_mode'] ?? 'sticky', ['sticky','fixed','static','scroll'], 'sticky');
    $headerPosition = ['fixed'=>'fixed','sticky'=>'sticky','static'=>'relative','scroll'=>'relative'][$headerMode] ?? 'sticky';
    $headerPadding = in_array($headerMode, ['fixed','sticky'], true) ? 'var(--ao-public-header-height)' : '0px';
    $headerShadow = $safeChoice($t['header_shadow'] ?? 'soft', ['none','soft','deep'], 'soft');
    $cardShadow = $safeChoice($t['card_shadow'] ?? 'soft', ['none','soft','deep'], 'soft');
    $density = $safeChoice($t['content_density'] ?? 'comfortable', ['compact','comfortable','spacious'], 'comfortable');
    $currencyDisplay = $safeChoice($t['currency_display_mode'] ?? 'symbol', ['symbol','code','both'], 'symbol');
    $languageDisplay = $safeChoice($t['language_display_mode'] ?? 'flag_code', ['flag','code','name','flag_code','flag_name'], 'flag_code');
    $languageSwitch = $safeChoice($t['language_switch_mode'] ?? 'refresh', ['refresh','ajax'], 'refresh');
    $style='--ao-primary:'.e($primary).';--ao-secondary:'.e($secondary).';--ao-font:'.e($t['font_family'] ?? 'Inter, Arial, sans-serif').';--ao-radius:'.e($safeCssSize($radius,'24px')).';--btn-radius:'.e($safeCssSize($button,'16px')).';--ao-bg-custom:'.e($safeCssColor($bg,'#f8fbff')).';';
    $style.='--ao-theme-layout-width:'.e($layoutWidth).';--ao-theme-container:'.e($safeCssSize($t['container_width'] ?? '1240px','1240px')).';--ao-theme-section-spacing:'.e($safeCssSize($t['section_spacing'] ?? '34px','34px')).';';
    $style.='--ao-theme-header-mode:'.e($headerMode).';--ao-theme-header-position:'.e($headerPosition).';--ao-theme-header-padding:'.e($headerPadding).';--ao-theme-header-bg:'.e($safeCssColor($t['header_background'] ?? '#ffffff','#ffffff')).';--ao-theme-header-text:'.e($safeCssColor($t['header_text_color'] ?? '#0f172a','#0f172a')).';--ao-theme-header-radius:'.e($safeCssSize($t['header_radius'] ?? '0px','0px')).';--ao-theme-header-shadow:'.e($headerShadow).';--ao-theme-header-blur:'.(!empty($t['header_blur'])?'blur(16px)':'none').';--ao-theme-topbar-display:'.(!empty($t['topbar_enabled'])?'block':'none').';';
    $style.='--ao-theme-footer-bg:'.e($safeCssColor($t['footer_background'] ?? '#0f172a','#0f172a')).';--ao-theme-footer-text:'.e($safeCssColor($t['footer_text_color'] ?? '#e5eef8','#e5eef8')).';--ao-theme-footer-radius:'.e($safeCssSize($t['footer_radius'] ?? '0px','0px')).';--ao-theme-card-shadow:'.e($cardShadow).';--ao-theme-density:'.e($density).';';
    $style.='--ao-theme-currency-display:'.e($currencyDisplay).';--ao-theme-language-display:'.e($languageDisplay).';--ao-theme-language-switch:'.e($languageSwitch).';';
    if($gradient) $style.='--ao-bg-gradient:'.e($gradient).';';
    if (($t['slug'] ?? '') === 'ahost-prism' && function_exists('admin_setting')) {
        $safeColor = function($key, $default) {
            $v = trim((string)admin_setting($key, $default));
            return preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : $default;
        };
        $safeImage = function($key) {
            $v = trim((string)admin_setting($key, ''));
            if ($v === '') return 'none';
            $v = str_replace(["\r","\n","'",'"','\\',')','('], '', $v);
            return $v === '' ? 'none' : 'url('.$v.')';
        };
        $style .= '--ao-prism-primary:'.e($safeColor('prism_primary_color', '#ff675d')).';';
        $style .= '--ao-prism-secondary:'.e($safeColor('prism_secondary_color', '#19b7a2')).';';
        $style .= '--ao-prism-accent:'.e($safeColor('prism_accent_color', '#ff8a5c')).';';
        $style .= '--ao-prism-heading-color:'.e($safeColor('prism_heading_color', '#082f49')).';';
        $style .= '--ao-prism-surface-color:'.e($safeColor('prism_surface_color', '#fff8ef')).';';
        $style .= '--ao-prism-card-radius:'.e(admin_setting('prism_card_radius', '26px')).';';
        $style .= '--ao-prism-button-radius:'.e(admin_setting('prism_button_radius', '16px')).';';
        foreach ([
            'site'=>'Site',
            'header'=>'Header',
            'hero'=>'Hero',
            'domain'=>'Domain',
            'card'=>'Card',
            'footer'=>'Footer',
        ] as $prefix=>$label) {
            $style .= '--ao-prism-'.$prefix.'-bg:'.e($safeColor('prism_'.$prefix.'_background_color', $prefix === 'site' ? '#fff8ef' : '#ffffff')).';';
            $style .= '--ao-prism-'.$prefix.'-bg-image:'.e($safeImage('prism_'.$prefix.'_background_image')).';';
        }
    }
    return $style;
}

function ao_theme_asset_url($area='site', $file='assets/css/theme.css'){
    $area = trim((string)$area);
    if (in_array($area, ['customer', 'client', 'auth'], true)) {
        $area = 'site';
    }
    $file = ltrim(str_replace('\\','/', (string)$file), '/');
    if ($file === '' || str_contains($file, '..')) return '';
    if (!function_exists('ao_active_theme')) return '';
    $theme = ao_active_theme($area ?: 'site');
    $slug = ao_theme_safe_slug($theme['slug'] ?? '');
    if ($slug === '') return '';
    $meta = ao_theme_package_meta($slug, $area ?: 'site');
    $files = [$file];
    if ($file === 'assets/css/theme.css' && !empty($meta['assets']['css'])) array_unshift($files, ltrim(str_replace('\\','/', (string)$meta['assets']['css']), '/'));
    if ($file === 'assets/js/theme.js' && !empty($meta['assets']['js'])) array_unshift($files, ltrim(str_replace('\\','/', (string)$meta['assets']['js']), '/'));
    $root = dirname(__DIR__, 2);
    foreach(array_unique($files) as $candidateFile){
        if ($candidateFile === '' || str_contains($candidateFile, '..')) continue;
        $relCandidates = [];
        $candidateSlugs = ao_theme_slug_candidates($slug);
        foreach ($candidateSlugs as $candidateSlug) {
            $relCandidates[] = 'public/themes/'.$candidateSlug.'/'.$candidateFile;
            $relCandidates[] = 'public/themes/'.($area === 'customer' ? 'client' : $area).'/'.$candidateSlug.'/'.$candidateFile;
            if (($area === 'client') || ($area === 'customer')) {
                $relCandidates[] = 'public/themes/customer/'.$candidateSlug.'/'.$candidateFile;
            }
        }
        foreach ($candidateSlugs as $candidateSlug) {
            $relCandidates[] = 'themes/'.$candidateSlug.'/'.$candidateFile;
            $relCandidates[] = 'themes/'.($area === 'customer' ? 'client' : $area).'/'.$candidateSlug.'/'.$candidateFile;
            if (($area === 'client') || ($area === 'customer')) {
                $relCandidates[] = 'themes/customer/'.$candidateSlug.'/'.$candidateFile;
            }
        }
        foreach($relCandidates as $rel){
            if(is_file($root.'/'.$rel)) return url($rel).'?v='.@filemtime($root.'/'.$rel);
        }
    }
    return '';
}
function ao_theme_asset_urls($area='site', $type='css'){
    $requestedArea = trim((string)$area);
    $area = $requestedArea;
    $assetArea = in_array($area, ['customer', 'client', 'auth'], true) ? 'site' : ($area ?: 'site');
    $type = strtolower(trim((string)$type));
    if ($type !== 'css') {
        $single = ao_theme_asset_url($assetArea, 'assets/js/theme.js');
        return $single ? [$single] : [];
    }
    $route = trim((string)($_SERVER['AHOST_ROUTE_RESOLVED'] ?? ''), '/');
    $areaFile = $assetArea === 'admin' ? 'admin' : 'site';
    $files = [
        'assets/css/tokens.css',
        'assets/css/theme.css',
        'assets/css/'.$areaFile.'.css',
        'assets/css/header.css',
        'assets/css/footer.css',
    ];
    if ($area !== 'auth') {
        $files[] = 'assets/css/page.css';
    }
    if ($route === '') {
        $files[] = 'assets/css/slider.css';
        $files[] = 'assets/css/homepage.css';
    }
    if ($area === 'auth') {
        $files[] = 'assets/css/auth.css';
    }
    if ($assetArea === 'site') {
        if (preg_match('~^(domain|domain-center|domain-transfer|alan-adi|toplu-domain)~', $route)) {
            $files[] = 'assets/css/domains.css';
        }
        if (preg_match('~^(cart|sepet|checkout|siparis)~', $route)) {
            $files[] = 'assets/css/cart.css';
        }
        if (preg_match('~^(urun|urun-grubu|products|hosting|vps|web-tasarim|mobil-uygulama|dijital-hizmetler|backorder)~', $route)) {
            $files[] = 'assets/css/products.css';
        }
        if (preg_match('~^marketplace~', $route)) {
            $files[] = 'assets/css/marketplace.css';
        }
        if (preg_match('~(sitebuilder|mobilebuilder|builder)~', $route)) {
            $files[] = 'assets/css/builder.css';
        }
    }
    // Backward compatibility for older theme packages.
    $files[] = 'assets/tokens.css';
    $files[] = 'assets/'.$areaFile.'.css';
    $urls = [];
    foreach ($files as $file) {
        $url = ao_theme_asset_url($assetArea, $file);
        if ($url !== '') {
            $urls[$url] = $url;
        }
    }
    return array_values($urls);
}
function ao_marketplace_stats(){ ao_schema_ensure_v930(); $out=['active'=>0,'featured'=>0,'offers'=>0,'sold'=>0]; try{$out['active']=(int)db()->query("SELECT COUNT(*) FROM marketplace_listings WHERE status='active'")->fetchColumn();$out['featured']=(int)db()->query("SELECT COUNT(*) FROM marketplace_listings WHERE is_featured=1 AND status='active'")->fetchColumn();$out['offers']=(int)db()->query("SELECT COUNT(*) FROM marketplace_offers WHERE status='pending'")->fetchColumn();$out['sold']=(int)db()->query("SELECT COUNT(*) FROM marketplace_listings WHERE status='sold'")->fetchColumn();}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } return $out; }
function ao_domain_intelligence_run($domain){
    ao_schema_ensure_v930(); $domain=ahost_domain_clean($domain); if(!ahost_domain_valid($domain)) throw new Exception('Geçersiz domain.');
    $ssl=['SSL Durumu'=>'Pasif']; try{ $ctx=stream_context_create(['ssl'=>['capture_peer_cert'=>true,'verify_peer'=>false,'verify_peer_name'=>false]]); $client=@stream_socket_client('ssl://'.$domain.':443',$errno,$errstr,6,STREAM_CLIENT_CONNECT,$ctx); if($client){ $params=stream_context_get_params($client); $cert=$params['options']['ssl']['peer_certificate']??null; $parsed=$cert?openssl_x509_parse($cert):[]; $ssl=['SSL Durumu'=>'Aktif','Issuer'=>$parsed['issuer']['O']??'-','Bitiş'=>isset($parsed['validTo_time_t'])?date('Y-m-d',$parsed['validTo_time_t']):'-']; fclose($client);} }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $dnsCount=0; if(function_exists('dns_get_record')){ $rec=@dns_get_record($domain,DNS_ALL); $dnsCount=is_array($rec)?count($rec):0; }
    $whoisRaw=ao_raw_whois($domain); $whois=ao_parse_whois_text($whoisRaw); $seo=ao_page_basic_analysis($domain); $val=ao_domain_valuation_score($domain,$whois,$ssl,$dnsCount,$seo);
    $sslScore=($ssl['SSL Durumu']??'')==='Aktif'?90:30; $dnsScore=min(100,30+$dnsCount*5); $trafficScore=max(10,min(85,(int)($val['score']*0.7)));
    $summary='SSL: '.($ssl['SSL Durumu']??'-').', DNS kayıt: '.$dnsCount.', SEO skor: '.$val['seo_score'].', Tahmini değer: '.$val['value'].' TRY';
    $raw=['ssl'=>$ssl,'dns_count'=>$dnsCount,'whois'=>$whois,'seo'=>$seo,'valuation'=>$val];
    try{ db()->prepare('INSERT INTO domain_intelligence_reports(domain_name,ssl_score,dns_score,seo_score,traffic_score,valuation_score,estimated_value,currency,summary,raw_json) VALUES(?,?,?,?,?,?,?,?,?,?)')->execute([$domain,$sslScore,$dnsScore,$val['seo_score'],$trafficScore,$val['score'],$val['value'],'TRY',$summary,json_encode($raw,JSON_UNESCAPED_UNICODE)]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return ['domain'=>$domain,'ssl_score'=>$sslScore,'dns_score'=>$dnsScore,'seo_score'=>$val['seo_score'],'traffic_score'=>$trafficScore,'valuation_score'=>$val['score'],'estimated_value'=>$val['value'],'summary'=>$summary,'raw'=>$raw];
}

