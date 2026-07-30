<?php
// v18.1.0 Module Center Pro - ZIP/FTP install, safe upgrade, SQL lifecycle, configuration
function ao_v18_ensure_module_schema(){ static $done=false; if($done) return; $done=true;
    try { db()->exec("CREATE TABLE IF NOT EXISTS modules (id INT AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(120) UNIQUE NOT NULL, name VARCHAR(190) NOT NULL, type VARCHAR(80) DEFAULT 'other', version VARCHAR(50) DEFAULT '1.0.0', description TEXT NULL, path VARCHAR(255) NULL, is_enabled TINYINT(1) DEFAULT 0, is_core TINYINT(1) DEFAULT 0, is_core_feature TINYINT(1) DEFAULT 0, hidden_from_module_center TINYINT(1) DEFAULT 0, manifest_json LONGTEXT NULL, installed_version VARCHAR(50) NULL, needs_install TINYINT(1) DEFAULT 1, last_error TEXT NULL, installed_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    foreach([
        "ALTER TABLE modules ADD COLUMN installed_version VARCHAR(50) NULL",
        "ALTER TABLE modules ADD COLUMN needs_install TINYINT(1) DEFAULT 1",
        "ALTER TABLE modules ADD COLUMN last_error TEXT NULL",
        "ALTER TABLE modules ADD COLUMN is_core_feature TINYINT(1) DEFAULT 0",
        "ALTER TABLE modules ADD COLUMN hidden_from_module_center TINYINT(1) DEFAULT 0"
    ] as $sql){ try { db()->exec($sql); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
    try { db()->exec("CREATE TABLE IF NOT EXISTS module_backups (id INT AUTO_INCREMENT PRIMARY KEY, module_slug VARCHAR(120) NOT NULL, backup_path VARCHAR(255) NOT NULL, version VARCHAR(50) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS module_logs (id INT AUTO_INCREMENT PRIMARY KEY, module_slug VARCHAR(120) NOT NULL, level VARCHAR(40) DEFAULT 'info', message TEXT NULL, context LONGTEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS module_permissions (id INT AUTO_INCREMENT PRIMARY KEY, module_slug VARCHAR(120) NOT NULL, permission_key VARCHAR(160) NOT NULL, description VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_module_permission(module_slug,permission_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS module_events (id INT AUTO_INCREMENT PRIMARY KEY, module_slug VARCHAR(120) NOT NULL, event_type VARCHAR(80) NOT NULL, message TEXT NULL, payload LONGTEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS module_settings (id INT AUTO_INCREMENT PRIMARY KEY, module_slug VARCHAR(120) NOT NULL, setting_key VARCHAR(120) NOT NULL, setting_value LONGTEXT NULL, is_secret TINYINT(1) DEFAULT 0, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_module_setting(module_slug,setting_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_module_log($slug,$type,$message='',$payload=null){
    try { db()->prepare('INSERT INTO module_events(module_slug,event_type,message,payload) VALUES(?,?,?,?)')->execute([$slug,$type,$message,$payload===null?null:json_encode($payload,JSON_UNESCAPED_UNICODE)]); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->prepare('INSERT INTO module_logs(module_slug,level,message,context) VALUES(?,?,?,?)')->execute([$slug, $type==='error'?'error':'info', $message, $payload===null?null:json_encode($payload,JSON_UNESCAPED_UNICODE)]); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_module_secret_generate($generator='random_64'){
    $generator=(string)$generator;
    try {
        if($generator==='random_32') return bin2hex(random_bytes(16));
        if($generator==='uuid') { $d=random_bytes(16); $d[6]=chr((ord($d[6]) & 0x0f) | 0x40); $d[8]=chr((ord($d[8]) & 0x3f) | 0x80); return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d),4)); }
        if($generator==='api_key') return 'ak_'.bin2hex(random_bytes(24));
        if($generator==='webhook_secret') return 'whsec_'.bin2hex(random_bytes(32));
        if($generator==='license_key') return strtoupper(implode('-', str_split(substr(bin2hex(random_bytes(20)),0,40),5)));
        return bin2hex(random_bytes(32));
    } catch(Throwable $e) { return hash('sha256', uniqid('ao_secret_', true).microtime(true)); }
}
function ao_module_setting_is_secret($def){ return !empty($def['secret']) || !empty($def['is_secret']) || in_array(($def['type'] ?? ''), ['password','hidden'], true) || !empty($def['auto_generate']); }
function ao_module_insert_default_setting($slug,$key,$def,$forceRegenerate=false){
    $slug=ao_module_slug($slug); $key=preg_replace('/[^a-zA-Z0-9_\.\-]/','',(string)$key); if($slug==='' || $key==='') return;
    $secret=ao_module_setting_is_secret($def);
    try{ $q=db()->prepare('SELECT setting_value FROM module_settings WHERE module_slug=? AND setting_key=? LIMIT 1'); $q->execute([$slug,$key]); $existing=$q->fetchColumn(); }catch(Throwable $e){ $existing=false; }
    if($existing!==false && !$forceRegenerate) return;
    if(!empty($def['auto_generate'])) $val=ao_module_secret_generate($def['generator'] ?? 'random_64'); else $val=(string)($def['default'] ?? '');
    try{ db()->prepare('INSERT INTO module_settings(module_slug,setting_key,setting_value,is_secret) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),is_secret=VALUES(is_secret)')->execute([$slug,$key,$val,$secret?1:0]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_module_slug($v){ return preg_replace('/[^a-z0-9\-_]/','', strtolower((string)$v)); }
function ao_module_type($v){ return preg_replace('/[^a-z0-9\-_]/','', strtolower((string)($v ?: 'custom'))); }
function ao_module_manifest_files(){
    $root = __DIR__ . '/modules';
    if(!is_dir($root)) return [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    $files=[];
    foreach($it as $file){ if($file->getFilename()==='module.json') $files[]=$file->getPathname(); }
    return $files;
}
function ao_module_row($slug){ try { $s=db()->prepare('SELECT * FROM modules WHERE slug=? LIMIT 1'); $s->execute([$slug]); return $s->fetch() ?: null; } catch(Throwable $e){ return null; } }
function ao_module_manifest($slug){ $m=ao_module_row($slug); if(!$m || empty($m['manifest_json'])) return []; $j=json_decode($m['manifest_json'],true); return is_array($j)?$j:[]; }
function ao_module_product_layer($module){
    $slug=ao_module_slug(is_array($module) ? ($module['slug'] ?? '') : (string)$module);
    $type=ao_module_type(is_array($module) ? ($module['type'] ?? '') : '');
    $integrationTypes=['registrar'=>1,'domain'=>1,'payment'=>1,'sms'=>1,'communication'=>1,'server'=>1,'provider'=>1,'api'=>1,'gateway'=>1,'notification'=>1,'ai'=>1,'migration'=>1,'whmcs'=>1];
    $integrationSlugs=['registrar'=>1,'domainnameapi'=>1,'opensrs'=>1,'resellerclub'=>1,'enom'=>1,'namesilo'=>1,'sms'=>1,'cpanel-api'=>1,'directadmin-api'=>1,'plesk-api'=>1,'shopier'=>1,'paytr'=>1,'iyzico'=>1,'paystack'=>1,'iletimerkezi'=>1,'cloudflare-integration'=>1,'zapier-integration'=>1,'webhook-manager'=>1,'api-gateway'=>1,'openai'=>1,'gemini'=>1,'anthropic'=>1,'ai-provider'=>1,'provider'=>1,'whmcs-import'=>1,'whmcs-bridge'=>1,'migration-bridge'=>1];
    $optionalSlugs=['sitebuilder'=>1,'site-builder'=>1,'mobilebuilder'=>1,'mobile-builder'=>1,'marketplace'=>1,'module-marketplace'=>1,'license'=>1,'license-center'=>1,'update-center'=>1,'plugin-marketplace'=>1,'theme-marketplace'=>1,'white-label'=>1,'white-label-portal'=>1,'stock-photos'=>1,'freelance-gig'=>1,'domain-appraisal'=>1,'domain-backorder'=>1,'domain-parking'=>1,'whois-guard'=>1,'provider-center-pro'=>1,'build-center'=>1,'module-center-pro'=>1,'ai'=>1,'ai-content-writer'=>1,'ai-logo-generator'=>1,'churn-prediction'=>1,'git-deployment'=>1];
    $coreSlugs=['accounting'=>1,'activity-log'=>1,'audit-trail'=>1,'backup-manager'=>1,'blog'=>1,'bulk-operations'=>1,'commerce'=>1,'coupons'=>1,'customer-insights'=>1,'dns-manager'=>1,'dunning'=>1,'email-templates'=>1,'form-builder'=>1,'health-score'=>1,'kanban'=>1,'knowledge'=>1,'knowledge-base'=>1,'knowledge-academy-pro'=>1,'live-chat'=>1,'multi-language'=>1,'notifications'=>1,'rbac'=>1,'revenue-analytics'=>1,'security'=>1,'service-catalog'=>1,'sla'=>1,'subscription'=>1,'support'=>1,'support-widget-pro'=>1,'system'=>1,'workflow-automation'=>1,'abandoned-cart'=>1,'affiliate'=>1,'compliance'=>1,'early-access'=>1,'lifetime-commission'=>1,'points-system'=>1,'popup-builder'=>1,'pwa-admin'=>1,'referral-system'=>1,'reseller'=>1,'seo-analyzer'=>1,'ssl-autoinstall'=>1,'staging-environment'=>1,'vps-provisioning'=>1,'achievement-badges'=>1,'currency-translation-pro'=>1,'e-invoice'=>1];
    if(isset($integrationTypes[$type]) || isset($integrationSlugs[$slug]) || str_contains($slug,'-api')) return ['key'=>'integration','label'=>'Harici Entegrasyon','hint'=>'Registrar, ödeme, SMS, sunucu paneli veya dış API adaptörü. Modül olarak kalır.'];
    if(isset($optionalSlugs[$slug]) || in_array($type,['builder','license','marketplace','white-label'],true)) return ['key'=>'app','label'=>'Opsiyonel Uygulama','hint'=>'Ahost One içinde ayrı ürün/uygulama gibi yönetilir. Route ve dosyalar korunur.'];
    if(isset($coreSlugs[$slug]) || in_array($type,['core','system','support','commerce','knowledge','automation','security','analytics'],true)) return ['key'=>'core','label'=>'Çekirdek Özellik','hint'=>'Sistemin ana özelliği gibi konumlandırılır; dosyalar geriye uyumluluk için yerinde kalır.'];
    return ['key'=>'integration','label'=>'Harici Entegrasyon','hint'=>'Sınıflandırılmamış paket güvenli tarafta modül/adaptör olarak bırakıldı.'];
}
function ao_module_is_site_core_feature($module){
    $layer=ao_module_product_layer($module);
    return ($layer['key'] ?? '') === 'core';
}
function ao_module_center_redirect_for_core($slug){
    $slug=ao_module_slug($slug);
    $map=[
        'support-widget-pro'=>'admin/support/widget-settings',
        'support'=>'admin/support',
        'live-chat'=>'admin/support/live-chat',
        'knowledge'=>'admin/settings/site-features#knowledge',
        'knowledge-base'=>'admin/settings/site-features#knowledge',
        'knowledge-academy-pro'=>'admin/settings/site-features#knowledge',
        'blog'=>'admin/blog',
        'seo-analyzer'=>'admin/settings/seo',
        'notifications'=>'admin/notification-center',
        'email-templates'=>'admin/email-templates',
        'multi-language'=>'admin/settings/localization',
        'security'=>'admin/settings/security',
        'backup-manager'=>'admin/backup-center',
        'activity-log'=>'admin/logs',
        'audit-trail'=>'admin/logs',
        'workflow-automation'=>'admin/automation',
        'form-builder'=>'admin/settings/site-features#contact',
        'popup-builder'=>'admin/site-builder/popups',
        'coupons'=>'admin/product-center/promotions',
        'abandoned-cart'=>'admin/orders/abandoned',
        'affiliate'=>'admin/affiliate',
        'commerce'=>'admin/product-center',
        'service-catalog'=>'admin/product-center',
        'system'=>'admin/settings',
    ];
    return $map[$slug] ?? 'admin/settings/site-features';
}
function ao_module_apply_visibility_flags(){
    ao_v18_ensure_module_schema();
    try {
        $rows=db()->query('SELECT slug,type FROM modules')->fetchAll() ?: [];
        foreach($rows as $row){
            $layer=ao_module_product_layer($row);
            $isCore=(($layer['key'] ?? '') === 'core') ? 1 : 0;
            db()->prepare('UPDATE modules SET is_core=?, is_core_feature=?, hidden_from_module_center=? WHERE slug=?')->execute([$isCore,$isCore,$isCore,$row['slug']]);
        }
    } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_module_scan(){
    ao_v18_ensure_module_schema();
    $found=[];
    foreach(ao_module_manifest_files() as $manifest){
        $json = json_decode((string)@file_get_contents($manifest), true);
        if(!$json || empty($json['slug'])) continue;
        $slug = ao_module_slug($json['slug']); if($slug==='') continue;
        $path = str_replace(__DIR__ . '/', '', dirname($manifest));
        $name = $json['name'] ?? ($json['title'] ?? $slug);
        $type = ao_module_type($json['type'] ?? 'other');
        $version = (string)($json['version'] ?? '1.0.0');
        $desc = $json['description'] ?? '';
        $old = ao_module_row($slug);
        $enabled = $old ? (int)$old['is_enabled'] : 0;
        $needs = $old ? (int)($old['needs_install'] ?? 0) : 1;
        $installedVersion = $old['installed_version'] ?? null;
        if($old && (string)($old['version'] ?? '') !== $version){ $enabled = 0; $needs = 1; ao_module_log($slug,'ftp_version_changed','FTP ile farklı sürüm algılandı; SQL güvenliği için modül pasife alındı.', ['old'=>$old['version'] ?? null,'new'=>$version]); }
        $layer = ao_module_product_layer(['slug'=>$slug,'type'=>$type]);
        $isCore = (($layer['key'] ?? '') === 'core') ? 1 : 0;
        try {
            db()->prepare("INSERT INTO modules(slug,name,type,version,description,path,is_enabled,is_core,is_core_feature,hidden_from_module_center,manifest_json,installed_version,needs_install,last_error) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,NULL) ON DUPLICATE KEY UPDATE name=VALUES(name),type=VALUES(type),version=VALUES(version),description=VALUES(description),path=VALUES(path),is_enabled=VALUES(is_enabled),is_core=VALUES(is_core),is_core_feature=VALUES(is_core_feature),hidden_from_module_center=VALUES(hidden_from_module_center),manifest_json=VALUES(manifest_json),installed_version=VALUES(installed_version),needs_install=VALUES(needs_install),last_error=NULL")
                ->execute([$slug,$name,$type,$version,$desc,$path,$enabled,$isCore,$isCore,$isCore,json_encode($json,JSON_UNESCAPED_UNICODE),$installedVersion,$needs]);
        } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        $found[]=$slug;
    }
    ao_module_apply_visibility_flags();
    return $found;
}
function ao_module_registry_all(){ ao_v18_ensure_module_schema(); ao_module_scan(); try { return db()->query("SELECT * FROM modules ORDER BY type,name")->fetchAll(); } catch(Throwable $e) { return []; } }
function ao_module_registry_module_center(){ ao_v18_ensure_module_schema(); ao_module_scan(); try { return db()->query("SELECT * FROM modules WHERE COALESCE(hidden_from_module_center,0)=0 ORDER BY type,name")->fetchAll(); } catch(Throwable $e) { return []; } }
function ao_module_registry_core_features(){ ao_v18_ensure_module_schema(); ao_module_scan(); try { return db()->query("SELECT * FROM modules WHERE COALESCE(is_core_feature,0)=1 ORDER BY type,name")->fetchAll(); } catch(Throwable $e) { return []; } }
function ao_module_health($module){
    $slug=ao_module_slug($module['slug'] ?? '');
    $path=__DIR__.'/'.trim((string)($module['path'] ?? ''),'/');
    $issues=[]; $warnings=[];
    $manifestFile=$path.'/module.json';
    $manifest=is_file($manifestFile) ? json_decode((string)file_get_contents($manifestFile),true) : null;
    if(!is_array($manifest)) $issues[]='module.json eksik veya geçersiz';
    else {
        if(($manifest['slug'] ?? '')!==$slug) $issues[]='manifest slug eşleşmiyor';
        if(empty($manifest['name']) || empty($manifest['type']) || empty($manifest['version'])) $issues[]='manifest zorunlu alanları eksik';
    }
    $install=$path.'/install.sql';
    if(!is_file($install)) {
        // v26.2.5 düzeltmesi: install.sql dosyası olmayan modüllerin çoğu şemayı
        // Module.php içindeki install(PDO $db) metoduyla PHP koduyla kuruyor. Bu,
        // ayrı bir install.sql dosyası kadar geçerli bir kurulum yoludur; sadece
        // dosya yokluğuna bakıp "Hatalı" damgalamak yanlış pozitif üretiyordu.
        $hasPhpSchema=false;
        $moduleFile=$path.'/Module.php';
        if(is_file($moduleFile)){
            $src=(string)@file_get_contents($moduleFile);
            if(preg_match('~function\s+install\s*\(~i',$src) && preg_match('~(CREATE\s+TABLE|ALTER\s+TABLE|\$db\s*->\s*exec|\$db\s*->\s*prepare|db\(\)\s*->\s*exec|db\(\)\s*->\s*prepare)~i',$src)) $hasPhpSchema=true;
        }
        if($hasPhpSchema) $warnings[]='install.sql yok; şema Module.php install() içinde PHP ile oluşturuluyor';
        else $issues[]='install.sql eksik';
    } else {
        $sql=(string)file_get_contents($install);
        $meaningful=trim((string)preg_replace(['~/\*.*?\*/~s','~^\s*--.*$~m','~^\s*#.*$~m'],'',$sql));
        if($meaningful==='') $issues[]='install.sql yalnızca yorum içeriyor';
    }
    if(!is_file($path.'/Module.php')) $warnings[]='Module.php eksik';
    if(empty($manifest['settings'])) $warnings[]='yapılandırma alanı tanımlı değil';
    return ['ok'=>!$issues,'issues'=>$issues,'warnings'=>$warnings,'label'=>$issues?'Hatalı':($warnings?'Kontrol':'Sağlıklı')];
}
function ao_module_safe_path($path){ $root=realpath(__DIR__.'/modules'); $real=realpath($path); return $root && $real && str_starts_with($real,$root); }
function ao_module_rrmdir($dir){ if(!is_dir($dir)) return; $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST); foreach($it as $f){ $f->isDir()?@rmdir($f->getPathname()):@unlink($f->getPathname()); } @rmdir($dir); }
function ao_module_copydir($src,$dst){ if(!is_dir($dst)) mkdir($dst,0775,true); $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST); foreach($it as $f){ $to=$dst.'/'.substr($f->getPathname(),strlen($src)+1); if($f->isDir()) { if(!is_dir($to)) mkdir($to,0775,true); } else copy($f->getPathname(),$to); } }
function ao_module_execute_sql_file($slug,$file){
    if(!is_file($file)) return false;
    $sql = trim((string)@file_get_contents($file)); if($sql==='') return false;
    $meaningful=trim((string)preg_replace(['~/\*.*?\*/~s','~^\s*--.*$~m','~^\s*#.*$~m'],'',$sql));
    if($meaningful===''){ ao_module_log($slug,'sql_skipped',basename($file).' yalnızca yorum içeriyor.'); return false; }
    $pdo = db();
    try { $pdo->exec($sql); ao_module_log($slug,'sql_executed',basename($file).' çalıştırıldı.'); return true; }
    catch(Throwable $e){ ao_module_log($slug,'sql_error',$e->getMessage(),['file'=>basename($file)]); throw $e; }
}
function ao_module_apply_lifecycle_sql($slug,$action){
    $m=ao_module_row($slug); if(!$m) throw new Exception('Modül bulunamadı.');
    $dir=__DIR__.'/'.trim($m['path'],'/');
    $manifest=ao_module_manifest($slug);
    if($action==='enable'){
        $old=(string)($m['installed_version'] ?? ''); $new=(string)($m['version'] ?? '');
        $ran=false;
        if($old==='' || (int)($m['needs_install'] ?? 1)===1){ $ran = ao_module_execute_sql_file($slug,$dir.'/install.sql') || $ran; }
        if($old!=='' && $old!==$new){ $ran = ao_module_execute_sql_file($slug,$dir.'/upgrade.sql') || $ran; $verFile=$dir.'/upgrade_'.$old.'_to_'.$new.'.sql'; if(is_file($verFile)) $ran = ao_module_execute_sql_file($slug,$verFile) || $ran; }
        if(!empty($manifest['settings']) && is_array($manifest['settings'])){
            foreach($manifest['settings'] as $key=>$def){
                if(is_int($key) && is_array($def)) $key=$def['key'] ?? '';
                if(!is_array($def)) $def=['label'=>$key,'default'=>$def,'type'=>'text'];
                ao_module_insert_default_setting($slug,$key,$def,false);
            }
        }
        db()->prepare('UPDATE modules SET is_enabled=1, needs_install=0, installed_version=version, last_error=NULL WHERE slug=?')->execute([$slug]);
        ao_module_log($slug,'enable','Modül aktif edildi'.($ran?' ve SQL uygulandı.':'.'));
        return true;
    }
    if($action==='disable'){
        db()->prepare('UPDATE modules SET is_enabled=0 WHERE slug=?')->execute([$slug]); ao_module_log($slug,'disable','Modül pasif edildi.'); return true;
    }
    if($action==='delete'){
        ao_module_execute_sql_file($slug,$dir.'/uninstall.sql');
        db()->prepare('DELETE FROM module_settings WHERE module_slug=?')->execute([$slug]);
        db()->prepare('DELETE FROM modules WHERE slug=?')->execute([$slug]);
        if(ao_module_safe_path($dir)) ao_module_rrmdir($dir);
        ao_module_log($slug,'delete','Modül kaldırıldı, ayarları temizlendi.'); return true;
    }
    return false;
}
function ao_module_toggle($slug,$enabled){
    ao_v18_ensure_module_schema(); $slug=ao_module_slug($slug); if($slug==='') return false;
    try { return ao_module_apply_lifecycle_sql($slug,$enabled?'enable':'disable'); }
    catch(Throwable $e){ try{db()->prepare('UPDATE modules SET is_enabled=0,last_error=? WHERE slug=?')->execute([$e->getMessage(),$slug]);}catch(Throwable $x){} ao_module_log($slug,'error',$e->getMessage()); return false; }
}
function ao_module_zip_entries_are_safe($zip){
    for($i=0;$i<$zip->numFiles;$i++){ $n=str_replace('\\','/',$zip->getNameIndex($i)); if($n==='' || str_starts_with($n,'/') || str_contains($n,'../') || preg_match('~^[A-Za-z]:/~',$n)) return false; }
    return true;
}
function ao_module_upload_zip($field='module_zip'){
    if(empty($_FILES[$field]['tmp_name'])) throw new Exception('ZIP dosyası seçilmedi.');
    $tmp = $_FILES[$field]['tmp_name'];
    $zip = new ZipArchive();
    if($zip->open($tmp)!==true) throw new Exception('ZIP açılamadı.');
    if(!ao_module_zip_entries_are_safe($zip)){ $zip->close(); throw new Exception('ZIP içinde güvensiz dosya yolu var.'); }
    $manifestIndex = false; $manifestName='';
    for($i=0;$i<$zip->numFiles;$i++){ $name=$zip->getNameIndex($i); if(basename($name)==='module.json'){ $manifestIndex=$i; $manifestName=str_replace('\\','/',$name); break; } }
    if($manifestIndex===false){ $zip->close(); throw new Exception('module.json bulunamadı.'); }
    $manifest = json_decode($zip->getFromIndex($manifestIndex), true);
    if(!$manifest || empty($manifest['slug'])){ $zip->close(); throw new Exception('module.json geçersiz.'); }
    $slug = ao_module_slug($manifest['slug']); $type = ao_module_type($manifest['type'] ?? 'custom');
    $target = __DIR__ . '/modules/' . $type . '/' . $slug;
    $backupRoot = __DIR__ . '/storage/module-backups/' . date('Ymd-His') . '-' . $slug;
    if(is_dir($target)){ if(!is_dir(dirname($backupRoot))) mkdir(dirname($backupRoot),0775,true); ao_module_copydir($target,$backupRoot); try{ db()->prepare('INSERT INTO module_backups(module_slug,backup_path,version) VALUES(?,?,?)')->execute([$slug,str_replace(__DIR__.'/','',$backupRoot),(string)($manifest['version'] ?? '')]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } ao_module_rrmdir($target); }
    if(!is_dir($target)) mkdir($target,0775,true);
    $prefix = trim(str_replace('\\','/',dirname($manifestName)),'./');
    for($i=0;$i<$zip->numFiles;$i++){
        $name=str_replace('\\','/',$zip->getNameIndex($i));
        if(substr($name,-1)==='/') continue;
        $rel=$name;
        if($prefix!=='' && str_starts_with($rel,$prefix.'/')) $rel=substr($rel,strlen($prefix)+1);
        $rel=ltrim($rel,'/'); if($rel==='') continue;
        $dest=$target.'/'.$rel; if(!is_dir(dirname($dest))) mkdir(dirname($dest),0775,true);
        copy('zip://'.$tmp.'#'.$name,$dest);
    }
    $zip->close();
    ao_module_scan();
    ao_module_log($slug,'upload','ZIP yüklendi; modül güvenlik için pasif kaydedildi.', ['backup'=>is_dir($backupRoot)?str_replace(__DIR__.'/','',$backupRoot):null]);
    return $slug;
}
function ao_module_delete($slug){ $slug=ao_module_slug($slug); if($slug==='') return false; return ao_module_apply_lifecycle_sql($slug,'delete'); }
function ao_module_settings_definitions($slug){
    $m=ao_module_manifest($slug); $defs=[];
    if(!empty($m['settings']) && is_array($m['settings'])){
        foreach($m['settings'] as $key=>$def){
            if(is_int($key) && is_array($def)) $key=$def['key'] ?? '';
            $key=preg_replace('/[^a-zA-Z0-9_\.\-]/','',(string)$key); if($key==='') continue;
            $defs[$key]=is_array($def)?$def:['label'=>$key,'default'=>$def,'type'=>'text'];
        }
    }
    $slug=ao_module_slug($slug);
    try{ $s=db()->prepare('SELECT setting_key,setting_value,is_secret FROM module_settings WHERE module_slug=? ORDER BY setting_key'); $s->execute([$slug]); foreach($s->fetchAll() as $r){ if(empty($defs[$r['setting_key']])) $defs[$r['setting_key']]=['label'=>$r['setting_key'],'type'=>$r['is_secret']?'password':'text']; } }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return $defs;
}
function ao_module_settings_values($slug){ $out=[]; try{ $s=db()->prepare('SELECT setting_key,setting_value FROM module_settings WHERE module_slug=?'); $s->execute([$slug]); foreach($s->fetchAll() as $r) $out[$r['setting_key']]=$r['setting_value']; }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } return $out; }
function ao_module_central_settings_target($slug){
    $slug=ao_module_slug($slug);
    $map=[
        'ai'=>['url'=>'admin/settings#ai','label'=>'Genel Ayarlar > AI Center','message'=>'AI sağlayıcı API anahtarları tek merkezden yönetilir. Module Center bu modül için sadece kurulum ve aktiflik alanıdır.'],
        'openai'=>['url'=>'admin/settings#ai','label'=>'Genel Ayarlar > AI Center','message'=>'OpenAI, Gemini ve diğer AI API ayarları Genel Ayarlar altında tutulur.'],
        'domainnameapi'=>['url'=>'admin/settings#domain','label'=>'Genel Ayarlar > Domain','message'=>'DomainNameAPI kullanıcı/API bilgileri Genel Ayarlar > Domain sekmesinden yönetilir.'],
        'registrar'=>['url'=>'admin/settings#domain','label'=>'Genel Ayarlar > Domain','message'=>'Registrar API bilgileri Genel Ayarlar > Domain sekmesinden yönetilir.'],
        'iletimerkezi'=>['url'=>'admin/settings#smtp','label'=>'Genel Ayarlar > SMTP / SMS','message'=>'SMS sağlayıcı API bilgileri Genel Ayarlar > SMTP / SMS sekmesinden yönetilir.'],
        'sms'=>['url'=>'admin/settings#smtp','label'=>'Genel Ayarlar > SMTP / SMS','message'=>'SMS API bilgileri Genel Ayarlar > SMTP / SMS sekmesinden yönetilir.'],
        'shopier'=>['url'=>'admin/settings#finance','label'=>'Genel Ayarlar > Finans','message'=>'Shopier ödeme ayarları Genel Ayarlar > Finans sekmesinden yönetilir.'],
    ];
    return $map[$slug] ?? null;
}
function ao_module_save_settings($slug,$settings){
    $slug=ao_module_slug($slug); $defs=ao_module_settings_definitions($slug);
    foreach($defs as $key=>$def){
        if(!empty($def['readonly']) && !array_key_exists($key,$settings)) continue;
        if(!array_key_exists($key,$settings) && (($def['type'] ?? '')==='hidden' || !empty($def['auto_generate']))) continue;
        $val=$settings[$key] ?? '';
        if(($def['type'] ?? '')==='checkbox') $val = isset($settings[$key]) ? '1' : '0';
        $secret=ao_module_setting_is_secret($def);
        try{ db()->prepare('INSERT INTO module_settings(module_slug,setting_key,setting_value,is_secret) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),is_secret=VALUES(is_secret)')->execute([$slug,$key,(string)$val,$secret?1:0]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    }
    ao_module_log($slug,'settings','Modül yapılandırması kaydedildi.'); return true;
}
function ao_module_regenerate_setting($slug,$key){
    $slug=ao_module_slug($slug); $defs=ao_module_settings_definitions($slug); if(empty($defs[$key]) || empty($defs[$key]['auto_generate'])) throw new Exception('Bu ayar otomatik secret üretimini desteklemiyor.');
    ao_module_insert_default_setting($slug,$key,$defs[$key],true); ao_module_log($slug,'secret_regenerated',$key.' yeniden oluşturuldu.'); return true;
}

function ao_module_export_zip($slug){
    $slug=ao_module_slug($slug); if($slug==='') throw new Exception('Modül slug boş.');
    $m=ao_module_row($slug); if(!$m) throw new Exception('Modül bulunamadı.');
    $dir=realpath(__DIR__ . '/' . trim($m['path'],'/'));
    $root=realpath(__DIR__ . '/modules');
    if(!$dir || !$root || !str_starts_with($dir,$root) || !is_dir($dir)) throw new Exception('Modül klasörü güvenli alanda değil veya bulunamadı.');
    if(!class_exists('ZipArchive')) throw new Exception('PHP ZipArchive eklentisi aktif değil.');
    $exportDir=__DIR__.'/storage/module-exports'; if(!is_dir($exportDir)) mkdir($exportDir,0775,true);
    $version=preg_replace('/[^0-9A-Za-z._-]/','',(string)($m['version'] ?? '1.0.0'));
    $file=$exportDir.'/'.$slug.'-v'.$version.'-'.date('Ymd-His').'.zip';
    $zip=new ZipArchive(); if($zip->open($file, ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true) throw new Exception('ZIP oluşturulamadı.');
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS));
    foreach($it as $f){
        if($f->isDir()) continue;
        $rel=substr($f->getPathname(), strlen($dir)+1);
        if(str_contains($rel,'..')) continue;
        $zip->addFile($f->getPathname(), $slug.'/'.$rel);
    }
    $zip->close();
    ao_module_log($slug,'export','Modül ZIP olarak indirildi.', ['file'=>str_replace(__DIR__.'/','',$file)]);
    return $file;
}
function ao_module_download_response($slug){
    $file=ao_module_export_zip($slug);
    if(!is_file($file)) throw new Exception('ZIP dosyası oluşturulamadı.');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.basename($file).'"');
    header('Content-Length: '.filesize($file));
    readfile($file); exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/module-center/scan') { require_admin(); verify_csrf(); $found=ao_module_scan(); flash('success', count($found).' modül tarandı. FTP ile yeni/farklı sürüm geldiyse pasif kaydedildi.'); redirect_to('admin/module-center'); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/module-center/toggle') { require_admin(); verify_csrf(); $ok=ao_module_toggle($_POST['slug'] ?? '', (int)($_POST['enabled'] ?? 0)); flash($ok?'success':'error',$ok?'Modül durumu güncellendi. Aktif ederken gerekli SQL uygulandı.':'Modül güncellenemedi. Detay için olay kayıtlarına bakın.'); redirect_to('admin/module-center'); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/module-center/upload') { require_admin(); verify_csrf(); try{ $slug=ao_module_upload_zip(); flash('success','Modül ZIP olarak yüklendi ve güvenlik için pasif kaydedildi: '.$slug); } catch(Throwable $e){ flash('error',$e->getMessage()); } redirect_to('admin/module-center'); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/module-center/delete') { require_admin(); verify_csrf(); try{ ao_module_delete($_POST['slug'] ?? ''); flash('success','Modül silindi; uninstall.sql ve modül ayar temizliği çalıştırıldı.'); } catch(Throwable $e){ flash('error',$e->getMessage()); } redirect_to('admin/module-center'); }
if ($route==='admin/module-center/download') { require_admin(); try{ ao_module_download_response($_GET['slug'] ?? ''); }catch(Throwable $e){ flash('error','Modül indirilemedi: '.$e->getMessage()); redirect_to('admin/module-center'); } }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/module-center/config-save') { require_admin(); verify_csrf(); $slug=ao_module_slug($_POST['slug'] ?? ''); $module=ao_module_row($slug); if($module && (!empty($module['hidden_from_module_center']) || !empty($module['is_core_feature']))){ flash('info','Bu kayıt artık Site Özellikleri / Ayarlar altında yönetiliyor.'); redirect_to(ao_module_center_redirect_for_core($slug)); } if($target=ao_module_central_settings_target($slug)){ flash('info',$target['message']); redirect_to($target['url']); } ao_module_save_settings($slug,$_POST['settings'] ?? []); flash('success','Modül yapılandırması kaydedildi.'); redirect_to('admin/module-center/config?slug='.$slug); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/module-center/regenerate-secret') { require_admin(); verify_csrf(); $slug=ao_module_slug($_POST['slug'] ?? ''); $key=preg_replace('/[^a-zA-Z0-9_\.\-]/','',(string)($_POST['key'] ?? '')); try{ ao_module_regenerate_setting($slug,$key); flash('success','Secret yeniden oluşturuldu.'); }catch(Throwable $e){ flash('error',$e->getMessage()); } redirect_to('admin/module-center/config?slug='.$slug); }
if ($route==='admin/module-center/config') { require_admin(); ao_v18_ensure_module_schema(); ao_module_scan(); $slug=ao_module_slug($_GET['slug'] ?? ''); $module=ao_module_row($slug); if(!$module){ flash('error','Modül bulunamadı.'); redirect_to('admin/module-center'); } if(!empty($module['hidden_from_module_center']) || !empty($module['is_core_feature'])){ flash('info','Bu kayıt artık Site Özellikleri / Ayarlar altında yönetiliyor.'); redirect_to(ao_module_center_redirect_for_core($slug)); } view('module-center/config', ['pageTitle'=>'Modül Yapılandırma','module'=>$module,'defs'=>ao_module_settings_definitions($slug),'values'=>ao_module_settings_values($slug),'centralTarget'=>ao_module_central_settings_target($slug)]); exit; }

ao_schema_ensure_v186();
