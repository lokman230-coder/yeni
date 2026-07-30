<?php
// v18.8.3 Builder UX Rebuild + Domain Intelligence Real Mode
function ao_schema_ensure_v188(){ static $done=false; if($done) return; $done=true;
    try{ db()->exec("ALTER TABLE themes ADD COLUMN radius VARCHAR(24) DEFAULT '24px'"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("ALTER TABLE themes ADD COLUMN button_radius VARCHAR(24) DEFAULT '16px'"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("ALTER TABLE themes ADD COLUMN button_style VARCHAR(40) DEFAULT 'gradient'"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("ALTER TABLE themes ADD COLUMN background_color VARCHAR(24) DEFAULT '#f8fbff'"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("ALTER TABLE themes ADD COLUMN background_gradient VARCHAR(190) NULL"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("ALTER TABLE themes ADD COLUMN header_mode VARCHAR(40) DEFAULT 'sticky'"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("ALTER TABLE themes ADD COLUMN mobile_bottom_nav TINYINT(1) DEFAULT 1"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    foreach([
        'layout_width'=>"ALTER TABLE themes ADD COLUMN layout_width VARCHAR(40) DEFAULT 'boxed'",
        'container_width'=>"ALTER TABLE themes ADD COLUMN container_width VARCHAR(24) DEFAULT '1240px'",
        'section_spacing'=>"ALTER TABLE themes ADD COLUMN section_spacing VARCHAR(24) DEFAULT '34px'",
        'header_background'=>"ALTER TABLE themes ADD COLUMN header_background VARCHAR(40) DEFAULT '#ffffff'",
        'header_text_color'=>"ALTER TABLE themes ADD COLUMN header_text_color VARCHAR(40) DEFAULT '#0f172a'",
        'header_radius'=>"ALTER TABLE themes ADD COLUMN header_radius VARCHAR(24) DEFAULT '0px'",
        'header_shadow'=>"ALTER TABLE themes ADD COLUMN header_shadow VARCHAR(40) DEFAULT 'soft'",
        'header_blur'=>"ALTER TABLE themes ADD COLUMN header_blur TINYINT(1) DEFAULT 1",
        'topbar_enabled'=>"ALTER TABLE themes ADD COLUMN topbar_enabled TINYINT(1) DEFAULT 1",
        'footer_background'=>"ALTER TABLE themes ADD COLUMN footer_background VARCHAR(40) DEFAULT '#0f172a'",
        'footer_text_color'=>"ALTER TABLE themes ADD COLUMN footer_text_color VARCHAR(40) DEFAULT '#e5eef8'",
        'footer_radius'=>"ALTER TABLE themes ADD COLUMN footer_radius VARCHAR(24) DEFAULT '0px'",
        'card_shadow'=>"ALTER TABLE themes ADD COLUMN card_shadow VARCHAR(40) DEFAULT 'soft'",
        'content_density'=>"ALTER TABLE themes ADD COLUMN content_density VARCHAR(40) DEFAULT 'comfortable'",
        'currency_display_mode'=>"ALTER TABLE themes ADD COLUMN currency_display_mode VARCHAR(40) DEFAULT 'symbol'",
        'language_display_mode'=>"ALTER TABLE themes ADD COLUMN language_display_mode VARCHAR(40) DEFAULT 'flag_code'",
        'language_switch_mode'=>"ALTER TABLE themes ADD COLUMN language_switch_mode VARCHAR(40) DEFAULT 'refresh'",
    ] as $sql){ try{ db()->exec($sql); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
    try{ db()->exec("CREATE TABLE IF NOT EXISTS client_preferences (id INT AUTO_INCREMENT PRIMARY KEY, client_id INT NOT NULL, site_theme_id INT NULL, client_theme_id INT NULL, builder_layout_json LONGTEXT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_client_pref(client_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NULL, email VARCHAR(190) NULL, token_hash VARCHAR(190) NOT NULL, channel VARCHAR(40) DEFAULT 'email', expires_at DATETIME NOT NULL, used_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX token_hash(token_hash), INDEX email(email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("CREATE TABLE IF NOT EXISTS client_security_questions (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NOT NULL, question VARCHAR(190) NOT NULL, answer_hash VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_customer_question(customer_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("CREATE TABLE IF NOT EXISTS domain_price_cache (id INT AUTO_INCREMENT PRIMARY KEY, tld VARCHAR(40) NOT NULL, registrar VARCHAR(80) DEFAULT 'domainnameapi', cost_usd DECIMAL(12,4) DEFAULT 0, commission_percent DECIMAL(6,2) DEFAULT 20, sale_usd DECIMAL(12,4) DEFAULT 0, sale_try DECIMAL(12,2) DEFAULT 0, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_tld_registrar(tld,registrar)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->prepare("INSERT INTO settings(setting_key,setting_value) VALUES ('ahost_version','25.0.0-rc25'),('css_isolation_app_shell','1'),('inline_builder_enabled','1'),('client_layout_rebuild','1'),('theme_studio_pro','1'),('client_builder_pro','1'),('real_theme_preview','1'),('currency_margin_percent','5.00'),('domain_default_commission_percent','20.00') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    foreach(['com'=>10.00,'net'=>12.00,'org'=>11.00,'com.tr'=>8.50,'net.tr'=>8.00] as $tld=>$cost){
        try{ $rate=(float)ao_currency_rate('USD','TRY'); $comm=(float)admin_setting('domain_default_commission_percent','20'); $sale=$cost+($cost*$comm/100); db()->prepare('INSERT INTO domain_price_cache(tld,cost_usd,commission_percent,sale_usd,sale_try) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE cost_usd=VALUES(cost_usd),commission_percent=VALUES(commission_percent),sale_usd=VALUES(sale_usd),sale_try=VALUES(sale_try)')->execute([$tld,$cost,$comm,$sale,$sale*$rate]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    }
}
function ao_builder_context_from_route($route){
    $route=trim($route,'/');
    $base=trim(parse_url(app_base_path(), PHP_URL_PATH) ?: '', '/');
    if($base && str_starts_with($route,$base)) $route=trim(substr($route,strlen($base)),'/');
    if(str_starts_with($route,'admin')) return ['target'=>'admin','template'=>preg_replace('/[^a-z0-9_-]+/i','-', $route ?: 'dashboard')];
    if(str_starts_with($route,'client')) return ['target'=>'customer','template'=>preg_replace('/[^a-z0-9_-]+/i','-', $route ?: 'dashboard')];
    return ['target'=>'site','template'=>($route===''?'home':preg_replace('/[^a-z0-9_-]+/i','-', $route))];
}
function ao_domain_sale_price($tld,$currency='TRY'){
    ao_schema_ensure_v188(); $tld=ltrim(strtolower($tld),'.');
    try{ $q=db()->prepare('SELECT * FROM domain_price_cache WHERE tld=? LIMIT 1'); $q->execute([$tld]); $r=$q->fetch(); if($r){ return $currency==='USD' ? (float)$r['sale_usd'] : (float)$r['sale_try']; } }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $costs=['com'=>10,'net'=>12,'org'=>11,'com.tr'=>8.5,'net.tr'=>8]; $cost=$costs[$tld]??10; $comm=(float)admin_setting('domain_default_commission_percent','20'); $sale=$cost+($cost*$comm/100); return $currency==='USD' ? $sale : $sale*(float)ao_currency_rate('USD','TRY');
}
function ao_theme_preview_bar($themeId){
    if(!$themeId || !current_admin()) return '';
    return '<div class="preview-bar">Önizleme modu aktif <a href="'.e(url('admin/theme-center/apply-preview?id='.(int)$themeId)).'">Temayı Uygula</a><a href="'.e(url('admin/theme-center/preview-exit')).'">Çıkış</a></div>';
}
function ao_clear_theme_cache(){ try{ $_SESSION['theme_preview_id']=0; }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
if (!function_exists('ao_theme_delete_directory_safe')) {
    function ao_theme_delete_directory_safe(string $dir, string $themesRoot): void {
        if (!is_dir($dir)) return;
        $rootReal = realpath($themesRoot);
        $dirReal = realpath($dir);
        if (!$rootReal || !$dirReal) throw new Exception('Tema yolu çözümlenemedi.');
        $rootReal = rtrim(str_replace('\\','/',$rootReal), '/');
        $dirReal = rtrim(str_replace('\\','/',$dirReal), '/');
        if ($dirReal === $rootReal || !str_starts_with($dirReal.'/', $rootReal.'/')) {
            throw new Exception('Güvensiz tema silme yolu engellendi.');
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirReal, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $item) {
            $path = $item->getPathname();
            if ($item->isDir()) {
                if (!@rmdir($path)) throw new Exception('Tema klasörü silinemedi: '.$path);
            } else {
                if (!@unlink($path)) throw new Exception('Tema dosyası silinemedi: '.$path);
            }
        }
        if (!@rmdir($dirReal)) throw new Exception('Tema klasörü kaldırılamadı: '.$dirReal);
    }
}
ao_schema_ensure_v188();
