<?php
// v17.0.0 Production Ready Style Layer + Setup Wizard + Admin UX
function ao_v1700_ensure_schema() {
    static $done=false; if($done) return; $done=true;
    try { db()->exec("CREATE TABLE IF NOT EXISTS setup_wizard_steps (id INT AUTO_INCREMENT PRIMARY KEY, step_key VARCHAR(120) UNIQUE NOT NULL, title VARCHAR(190) NOT NULL, description TEXT NULL, category VARCHAR(80) DEFAULT 'general', route VARCHAR(190) NULL, status ENUM('pending','done','skipped') DEFAULT 'pending', required TINYINT(1) DEFAULT 1, sort_order INT DEFAULT 0, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS module_visibility (id INT AUTO_INCREMENT PRIMARY KEY, module_key VARCHAR(120) UNIQUE NOT NULL, title VARCHAR(190) NOT NULL, is_enabled TINYINT(1) DEFAULT 1, route VARCHAR(190) NULL, category VARCHAR(80) DEFAULT 'core', updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS setup_wizard_runs (id INT AUTO_INCREMENT PRIMARY KEY, admin_id INT NULL, action VARCHAR(80) NOT NULL, payload TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }

    $steps = [
        ['site_identity','Logo, Site Adı ve Site Linki','Marka adı, logo, site URL ve iletişim bilgilerini tamamlayın.','Başlangıç','admin/settings',1,10],
        ['theme','Tema Seçimi ve Önizleme','Ön yüz, admin ve müşteri paneli temasını seçip uygulayın.','Görünüm','admin/theme-center',1,20],
        ['domain_registrar','DomainNameAPI / Registrar Ayarları','Domain sorgulama, kayıt, yenileme, EPP ve transfer için registrar ayarlarını yapın.','Domain','admin/domain-center/registrars',1,30],
        ['server','Sunucu / WHM / DirectAdmin / Plesk','Hosting otomasyonu için sunucu ekleyin ve bağlantı testini yapın.','Hosting','admin/hosting-server/servers',1,40],
        ['products','Ürün Grupları ve Paketler','Hosting, domain, SSL, web tasarım, SEO ve diğer ürünlerinizi tanımlayın.','Ürün','admin/product-center/groups',1,50],
        ['payment','Ödeme Yöntemleri ve Kart Komisyonu','Shopier, sanal POS ve ödeme komisyonlarını yapılandırın.','Ödeme','admin/accounting/payment-fees',1,60],
        ['smtp','SMTP Mail Ayarları','Fatura, ticket, şifre sıfırlama ve sistem bildirimleri için SMTP ayarlarını girin.','Bildirim','admin/notification-center',1,70],
        ['sms','SMS / İletiMerkezi Ayarları','İletiMerkezi veya diğer SMS sağlayıcılarını bağlayın, bakiye sorgulayın ve test SMS gönderin.','Bildirim','admin/notifications',0,80],
        ['whatsapp','WhatsApp Bildirimleri','WhatsApp API veya webhook sağlayıcınızı bağlayın.','Bildirim','admin/notification-center',0,90],
        ['ai','Yapay Zeka API Ayarları','OpenAI/Gemini/Claude gibi AI sağlayıcı API anahtarlarını girin.','AI','admin/ai-center',0,100],
        ['sitebuilder','Site Builder Ayarları','Site Builder, export ZIP ve tema entegrasyonunu kontrol edin.','Builder','admin/site-builder',0,110],
        ['mobilebuilder','Mobile Builder ve Build Center','PWA/Flutter/Android export, SDK, Gradle ve build kuyruğu ayarlarını kontrol edin.','Builder','admin/mobile-builder',0,120],
        ['license','Lisans Merkezi','Site Builder, Mobile Builder, tema, marketplace ve kaynak kod lisans kurallarını tanımlayın.','Lisans','admin/license-center',0,130],
        ['marketplace','Marketplace Ayarları','Domain, hosting, web tasarım, SEO, logo ve dijital ürün satış kurallarını ayarlayın.','Marketplace','admin/marketplace',0,140],
        ['security','Güvenlik ve Yetkiler','Admin rolleri, 2FA, IP kısıtlama, oturum süresi ve CSRF ayarlarını kontrol edin.','Güvenlik','admin/security',1,150],
        ['backup','Backup / Restore','Veritabanı ve dosya yedekleme planını oluşturun.','Sistem','admin/backup-center',1,160],
        ['scan','QA Scan Center','Kurulum sonunda QA Scan Center ile sistem, rota ve görsel kalite raporu alın.','Sistem','admin/qa-scan-center',1,170],
    ];
    foreach($steps as $x){ try{ db()->prepare("INSERT INTO setup_wizard_steps(step_key,title,description,category,route,required,sort_order) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title),description=VALUES(description),category=VALUES(category),route=VALUES(route),required=VALUES(required),sort_order=VALUES(sort_order)")->execute($x); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
    $mods = [
        ['domain','Domain Center',1,'admin/domain-center','commerce'],['hosting','Hosting & Server',1,'admin/hosting-server','commerce'],['marketplace','Marketplace',1,'admin/marketplace','commerce'],['sitebuilder','Site Builder',1,'admin/site-builder','builder'],['mobilebuilder','Mobile Builder',1,'admin/mobile-builder','builder'],['buildcenter','Build Center',1,'admin/build-center','builder'],['license','License Center',1,'admin/license-center','system'],['notification','Notification Center',1,'admin/notification-center','system'],['backup','Backup Center',1,'admin/backup-center','system'],['scan','QA Scan Center',1,'admin/qa-scan-center','system'],['ai','AI Center',1,'admin/ai-center','ai']
    ];
    foreach($mods as $m){ try{ db()->prepare("INSERT INTO module_visibility(module_key,title,is_enabled,route,category) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title),route=VALUES(route),category=VALUES(category)")->execute($m); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
    try { save_setting('ahost_version','25.0.0-rc25'); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_v1700_setup_live_checks(){
    ao_v1700_ensure_schema();
    $has = function($key){ return trim((string)admin_setting($key,'')) !== ''; };
    $safeCount = function($table){ try { return table_count($table); } catch(Throwable $e){ return 0; } };
    $checks = [];
    // Logo zorunluysa logo_url da aranır; kapalıysa site adı + URL + iletişim yeterlidir.
    $requireLogo = admin_setting('setup_require_logo','1') === '1';
    $checks['site_identity'] = $has('site_name') && $has('site_url') && ($has('company_email') || $has('company_phone')) && (!$requireLogo || $has('logo_url'));
    $checks['theme'] = $has('theme_front') && $has('theme_admin') && $has('theme_customer');
    $checks['domain_registrar'] = $safeCount('domain_registrars')>0 || $has('domainnameapi_api_key') || ($has('domainnameapi_username') && $has('domainnameapi_password'));
    $checks['server'] = $safeCount('server_nodes')>0 || $safeCount('hosting_servers')>0;
    $checks['products'] = $safeCount('products')>0 || $safeCount('product_groups')>0;
    $checks['payment'] = $safeCount('payment_gateways')>0 || $safeCount('payment_fee_rules')>0 || $has('shopier_api_key');
    $checks['smtp'] = ($has('smtp_host') && ($has('smtp_username') || $has('smtp_from')));
    $checks['sms'] = $has('iletimerkezi_api_key') || $has('sms_provider');
    $checks['whatsapp'] = $has('whatsapp_token') || $has('whatsapp_provider');
    $checks['ai'] = $has('ai_api_key');
    $checks['sitebuilder'] = true;
    $checks['mobilebuilder'] = true;
    $checks['license'] = $safeCount('license_products')>0 || $has('license_key');
    $checks['marketplace'] = $safeCount('marketplace_categories')>0;
    $checks['security'] = true;
    $checks['backup'] = true;
    $checks['scan'] = true;
    return $checks;
}
function ao_v1700_setup_apply_live_checks(){
    $checks = ao_v1700_setup_live_checks();
    foreach($checks as $k=>$ok){
        try{
            if($ok){ db()->prepare("UPDATE setup_wizard_steps SET status='done' WHERE step_key=? AND status!='skipped'")->execute([$k]); }
            else { db()->prepare("UPDATE setup_wizard_steps SET status='pending' WHERE step_key=? AND status='done'")->execute([$k]); }
        }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    }
}
function ao_v1700_setup_rows(){ ao_v1700_ensure_schema(); ao_v1700_setup_apply_live_checks(); try{return db()->query('SELECT * FROM setup_wizard_steps ORDER BY sort_order,id')->fetchAll();}catch(Throwable $e){return [];} }
function ao_v1700_setup_progress(){ $rows=ao_v1700_setup_rows(); if(!$rows) return 0; $done=0; $total=0; foreach($rows as $r){ if((int)($r['required']??0)===1){ $total++; if(($r['status']??'')==='done' || ($r['status']??'')==='skipped') $done++; } } if($total===0){$total=count($rows); foreach($rows as $r){ if(($r['status']??'')==='done' || ($r['status']??'')==='skipped') $done++; }} return (int)round($done*100/max(1,$total)); }
function ao_v1700_group_steps($rows){ $g=[]; foreach($rows as $r){ $g[$r['category']?:'Genel'][]=$r; } return $g; }
function ao_v1700_setup_autocheck(){ ao_v1700_setup_apply_live_checks(); }

function ao_v1886_upload_branding($field, $targetName){
    if(empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return '';
    if(($_FILES[$field]['error'] ?? 0) !== UPLOAD_ERR_OK) return '';
    $tmp=$_FILES[$field]['tmp_name']; $mime=@mime_content_type($tmp) ?: '';
    $allowed=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp','image/svg+xml'=>'svg'];
    if(!isset($allowed[$mime])) return '';
    $dir=__DIR__.'/uploads/branding'; if(!is_dir($dir)) @mkdir($dir,0775,true);
    $file=$targetName.'.'.$allowed[$mime];
    if(@move_uploaded_file($tmp,$dir.'/'.$file)) return url('uploads/branding/'.$file);
    return '';
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/setup-wizard/save') { require_admin(); verify_csrf(); ao_v1700_ensure_schema();
    foreach($_POST['step_status'] ?? [] as $k=>$v){ if(in_array($v,['pending','done','skipped'],true)){ try{ db()->prepare('UPDATE setup_wizard_steps SET status=? WHERE step_key=?')->execute([$v,$k]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } } }
    foreach($_POST['settings'] ?? [] as $k=>$v){
        $allowed = ['site_name','site_url','logo_url','favicon_url','company_name','company_email','company_phone','company_address','default_currency','default_language','timezone','maintenance_mode','seo_title','seo_description','smtp_host','smtp_port','smtp_username','smtp_password','smtp_from','smtp_from_name','sms_provider','iletimerkezi_api_key','iletimerkezi_api_hash','iletimerkezi_sender','whatsapp_provider','whatsapp_token','ai_provider','ai_api_key','shopier_auth_mode','shopier_pat','shopier_api_key','shopier_api_secret','domainnameapi_auth_mode','domainnameapi_api_key','theme_front','theme_admin','theme_customer','registrar_provider','domainnameapi_api_secret','domainnameapi_username','domainnameapi_password','domainnameapi_test_domain','server_panel_type','server_name','server_hostname','server_ip','server_port','server_ssl','server_username','server_api_token','setup_product_hosting','setup_product_domain','setup_product_ssl','setup_product_vps','setup_product_sitebuilder','setup_product_mobilebuilder','setup_product_web','setup_product_seo','setup_product_mobile','setup_product_marketplace','recaptcha_site_key','recaptcha_secret_key','admin_2fa_enabled','google_maps_api_key','google_analytics_id'];
        if(in_array($k,$allowed,true)){ save_setting($k, is_array($v)?json_encode($v,JSON_UNESCAPED_UNICODE):(string)$v); }
    }
    if(isset($_POST['settings']['shopier_pat']) || isset($_POST['settings']['shopier_auth_mode']) || isset($_POST['settings']['shopier_api_key']) || isset($_POST['settings']['shopier_api_secret'])){
        ao_shopier_save_settings([
            'auth_mode'=>$_POST['settings']['shopier_auth_mode'] ?? 'pat',
            'pat'=>$_POST['settings']['shopier_pat'] ?? '',
            'api_key'=>$_POST['settings']['shopier_api_key'] ?? '',
            'api_secret'=>$_POST['settings']['shopier_api_secret'] ?? '',
            'website_index'=>ao_shopier_setting('website_index','1'),
            'test_mode'=>ao_shopier_setting('test_mode','1'),
            'callback_secret'=>ao_shopier_setting('callback_secret','')
        ]);
    }
    if($logoUpload = ao_v1886_upload_branding('logo_file','logo')) save_setting('logo_url',$logoUpload);
    if($favUpload = ao_v1886_upload_branding('favicon_file','favicon')) save_setting('favicon_url',$favUpload);
    foreach($_POST['module_enabled'] ?? [] as $key=>$val){
        try { db()->prepare("UPDATE module_visibility SET is_enabled=? WHERE module_key=?")->execute([(int)($val==='1'), $key]); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    }
    if(isset($_POST['complete'])){
        save_setting('setup_wizard_completed','1');
        save_setting('setup_wizard_dismissed','1');
    } else {
        save_setting('setup_wizard_completed','0');
    }
    if(isset($_POST['dont_show_again'])) save_setting('setup_wizard_dismissed','1');
    try { db()->prepare("INSERT INTO setup_wizard_runs(admin_id,action,payload) VALUES(?,?,?)")->execute([$_SESSION['admin_id'] ?? null,'save',json_encode(['settings'=>array_keys($_POST['settings'] ?? []),'complete'=>isset($_POST['complete'])],JSON_UNESCAPED_UNICODE)]); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    if(isset($_POST['run_scan'])){ ao_v1700_setup_autocheck(); flash('success','Ayarlar kaydedildi ve sistem kontrolü çalıştırıldı.'); redirect_to('admin/setup-wizard'); }
    flash('success','Kurulum sihirbazı ayarları kaydedildi.'); redirect_to('admin/setup-wizard');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/setup-wizard/dismiss') { require_admin(); verify_csrf(); ao_v1700_ensure_schema(); save_admin_pref('setup_wizard_popup_dismissed','1'); try{db()->prepare("INSERT INTO setup_wizard_runs(admin_id,action,payload) VALUES(?,?,?)")->execute([$_SESSION['admin_id'] ?? null,'dismiss','{}']);}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } flash('success','Kurulum sihirbazı gizlendi. Menüden tekrar açabilirsiniz.'); redirect_to('admin/dashboard'); }
if ($route==='admin/setup-wizard/autocheck') { require_admin(); ao_v1700_setup_autocheck(); flash('success','Otomatik kontrol tamamlandı.'); redirect_to('admin/setup-wizard'); }
if ($route==='admin/setup-wizard') { require_admin(); ao_v1700_ensure_schema(); view('setup-wizard/index', ['pageTitle'=>'Kurulum Sihirbazı & Yardım Kılavuzu','steps'=>ao_v1700_setup_rows(),'progress'=>ao_v1700_setup_progress()]); exit; }


