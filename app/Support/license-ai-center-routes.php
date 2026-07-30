<?php
// v15.0.0 License Center Pro + Admin AI Help Center
function ao_schema_ensure_v1500() { static $done=false; if($done) return; $done=true;
    try {
        db()->exec("CREATE TABLE IF NOT EXISTS license_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(180) NOT NULL,
            product_type VARCHAR(80) DEFAULT 'php_script',
            description TEXT NULL,
            status VARCHAR(30) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        db()->exec("CREATE TABLE IF NOT EXISTS license_plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NULL,
            name VARCHAR(160) NOT NULL,
            license_type VARCHAR(60) DEFAULT 'single_use',
            duration_days INT DEFAULT 0,
            max_domains INT DEFAULT 1,
            max_devices INT DEFAULT 1,
            is_open_source TINYINT(1) DEFAULT 0,
            price DECIMAL(14,2) DEFAULT 0,
            currency VARCHAR(10) DEFAULT 'TRY',
            status VARCHAR(30) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY product_id(product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        db()->exec("CREATE TABLE IF NOT EXISTS licenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            license_key VARCHAR(120) NOT NULL UNIQUE,
            product_id INT NULL,
            plan_id INT NULL,
            order_id INT NULL,
            customer_id INT NULL,
            domain VARCHAR(190) NULL,
            device_hash VARCHAR(190) NULL,
            status ENUM('active','inactive','expired','revoked','trial') DEFAULT 'active',
            starts_at DATETIME NULL,
            expires_at DATETIME NULL,
            metadata LONGTEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY product_id(product_id), KEY customer_id(customer_id), KEY status(status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        db()->exec("CREATE TABLE IF NOT EXISTS license_activations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            license_id INT NOT NULL,
            domain VARCHAR(190) NULL,
            ip_address VARCHAR(80) NULL,
            device_hash VARCHAR(190) NULL,
            app_version VARCHAR(80) NULL,
            status VARCHAR(30) DEFAULT 'active',
            activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_check_at DATETIME NULL,
            KEY license_id(license_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        db()->exec("CREATE TABLE IF NOT EXISTS license_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            license_id INT NULL,
            event_type VARCHAR(80) NOT NULL,
            message TEXT NULL,
            payload LONGTEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY license_id(license_id), KEY event_type(event_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        db()->exec("CREATE TABLE IF NOT EXISTS code_packages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            seller_type VARCHAR(40) DEFAULT 'admin',
            seller_customer_id INT NULL,
            product_id INT NULL,
            title VARCHAR(180) NOT NULL,
            package_type VARCHAR(80) DEFAULT 'php_script',
            original_file VARCHAR(255) NULL,
            licensed_file VARCHAR(255) NULL,
            license_mode ENUM('licensed','unlicensed','open_source') DEFAULT 'licensed',
            scan_summary LONGTEXT NULL,
            injection_status VARCHAR(40) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY product_id(product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        db()->exec("CREATE TABLE IF NOT EXISTS external_marketplace_integrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            provider VARCHAR(80) NOT NULL,
            name VARCHAR(160) NOT NULL,
            api_endpoint VARCHAR(255) NULL,
            api_key VARCHAR(255) NULL,
            api_secret VARCHAR(255) NULL,
            status VARCHAR(30) DEFAULT 'inactive',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY provider_unique(provider)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        db()->exec("CREATE TABLE IF NOT EXISTS external_purchase_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            provider VARCHAR(80) NOT NULL,
            purchase_code VARCHAR(190) NOT NULL,
            customer_email VARCHAR(190) NULL,
            product_name VARCHAR(190) NULL,
            license_id INT NULL,
            status VARCHAR(40) DEFAULT 'pending',
            verified_at DATETIME NULL,
            raw_response LONGTEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY provider_code(provider,purchase_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        db()->exec("CREATE TABLE IF NOT EXISTS help_articles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            module_key VARCHAR(100) NOT NULL,
            title VARCHAR(190) NOT NULL,
            body LONGTEXT NULL,
            api_provider VARCHAR(100) NULL,
            setup_route VARCHAR(190) NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY module_key(module_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        db()->exec("CREATE TABLE IF NOT EXISTS setup_checks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            check_key VARCHAR(120) NOT NULL UNIQUE,
            title VARCHAR(190) NOT NULL,
            provider VARCHAR(100) NULL,
            target_route VARCHAR(190) NULL,
            status VARCHAR(40) DEFAULT 'unknown',
            help_text TEXT NULL,
            last_checked_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        db()->exec("INSERT IGNORE INTO license_products(name,product_type,description,status) VALUES
            ('Ahost One','ahost_one','Ahost One domain bazlı lisanslı hosting otomasyon paketi','active'),
            ('Web Tasarım Paketi','web_design','Domain bazlı lisanslanan web tasarım teslim paketleri','active'),
            ('Android Uygulama Paketi','android','Package name ve lisans kodu ile çalışan Android kaynak kodu / APK paketi','active'),
            ('Ahost Site Builder Pro','sitebuilder','Site Builder ZIP export ve lisans kontrol modülü','active'),
            ('Ahost Mobile Builder Pro','mobilebuilder','APK/AAB/PWA üretim ve lisans kontrol altyapısı','active'),
            ('Ahost Marketplace Lisanslı Ürün','marketplace','Marketplace satıcı ürünleri için lisanslı satış katmanı','active'),
            ('Android Radyo Uygulaması Demo','android','Kaynak koda lisans istemcisi ekleme örneği','active')");
        db()->exec("INSERT IGNORE INTO license_plans(product_id,name,license_type,duration_days,max_domains,max_devices,is_open_source,price,currency,status)
            SELECT id,'Tek Kullanımlık Lisans','single_use',0,1,1,0,499,'TRY','active' FROM license_products WHERE name='Ahost Marketplace Lisanslı Ürün'");
        db()->exec("INSERT IGNORE INTO license_plans(product_id,name,license_type,duration_days,max_domains,max_devices,is_open_source,price,currency,status)
            SELECT id,'Domain Bazlı Ömür Boyu','domain_lifetime',0,1,0,0,999,'TRY','active' FROM license_products WHERE name='Ahost Site Builder Pro'");
        db()->exec("INSERT IGNORE INTO license_plans(product_id,name,license_type,duration_days,max_domains,max_devices,is_open_source,price,currency,status)
            SELECT id,'Açık Kaynak / Lisanssız','open_source',0,0,0,1,0,'TRY','active' FROM license_products WHERE name='Ahost Marketplace Lisanslı Ürün'");
        db()->exec("INSERT IGNORE INTO license_plans(product_id,name,license_type,duration_days,max_domains,max_devices,is_open_source,price,currency,status)
            SELECT id,'Aylık Domain Lisansı','monthly',30,1,0,0,299,'TRY','active' FROM license_products WHERE name='Ahost One'");
        db()->exec("INSERT IGNORE INTO license_plans(product_id,name,license_type,duration_days,max_domains,max_devices,is_open_source,price,currency,status)
            SELECT id,'Yıllık Domain Lisansı','yearly',365,1,0,0,2499,'TRY','active' FROM license_products WHERE name='Ahost One'");
        db()->exec("INSERT IGNORE INTO license_plans(product_id,name,license_type,duration_days,max_domains,max_devices,is_open_source,price,currency,status)
            SELECT id,'Ömür Boyu Domain Lisansı','lifetime',0,1,0,0,7999,'TRY','active' FROM license_products WHERE name='Ahost One'");
        db()->exec("INSERT IGNORE INTO license_plans(product_id,name,license_type,duration_days,max_domains,max_devices,is_open_source,price,currency,status)
            SELECT id,'Aylık Web Tasarım Lisansı','monthly',30,1,0,0,199,'TRY','active' FROM license_products WHERE name='Web Tasarım Paketi'");
        db()->exec("INSERT IGNORE INTO license_plans(product_id,name,license_type,duration_days,max_domains,max_devices,is_open_source,price,currency,status)
            SELECT id,'Yıllık Web Tasarım Lisansı','yearly',365,1,0,0,1499,'TRY','active' FROM license_products WHERE name='Web Tasarım Paketi'");
        db()->exec("INSERT IGNORE INTO license_plans(product_id,name,license_type,duration_days,max_domains,max_devices,is_open_source,price,currency,status)
            SELECT id,'Ömür Boyu Web Tasarım Lisansı','lifetime',0,1,0,0,4999,'TRY','active' FROM license_products WHERE name='Web Tasarım Paketi'");
        db()->exec("INSERT IGNORE INTO license_plans(product_id,name,license_type,duration_days,max_domains,max_devices,is_open_source,price,currency,status)
            SELECT id,'Ömür Boyu Android Lisansı','android_lifetime',0,0,1,0,3999,'TRY','active' FROM license_products WHERE name='Android Uygulama Paketi'");
        db()->exec("INSERT IGNORE INTO external_marketplace_integrations(provider,name,status) VALUES
            ('envato','Envato / CodeCanyon','inactive'),('codecanyon','CodeCanyon Purchase Code','inactive')");
        try { db()->exec("DELETE lp1 FROM license_products lp1 JOIN license_products lp2 ON lp1.name=lp2.name AND lp1.product_type=lp2.product_type AND lp1.id>lp2.id"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        try { db()->exec("ALTER TABLE license_products ADD UNIQUE KEY uniq_license_product_name_type(name,product_type)"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }

        db()->exec("INSERT IGNORE INTO help_articles(module_key,title,body,api_provider,setup_route) VALUES
            ('openai','OpenAI API Key nasıl alınır?','platform.openai.com üzerinden API Keys bölümüne girip yeni secret key oluşturun. Ahost One içinde AI Center > Ayarlar bölümüne ekleyin.','openai','admin/ai-center'),
            ('domainnameapi','DomainNameAPI ayarı','DomainNameAPI panelinizden API Key veya kullanıcı bilgilerinizi alın. Domain Center > Registrarlar > DomainNameAPI alanına ekleyin.','domainnameapi','admin/domain-center/registrars'),
            ('iletimerkezi','İletiMerkezi SMS ayarı','İletiMerkezi panelinden API Key, API Hash ve SMS başlığınızı alın. Bildirim Merkezi > SMS / WhatsApp / Mail alanına ekleyin.','iletimerkezi','admin/notifications'),
            ('shopier','Shopier ödeme ayarı','Shopier API Key ve Secret bilgilerini Shopier panelinden alın. Muhasebe > Kart Komisyonları / Ödeme API alanına ekleyin.','shopier','admin/accounting/payment-fees'),
            ('license-center','Lisans Merkezi nasıl çalışır?','Kaynak kod ZIP yükleyin, lisans tipini seçin. Sistem lisans istemci dosyalarını pakete ekler ve satışa göre lisans üretir.','license','admin/license-center')");
        db()->exec("INSERT INTO admin_search_index(title,route,category,keywords,is_active) VALUES
            ('License Center','admin/license-center','Lisans','lisans license activation purchase code codecanyon envato kaynak kod zip android php script',1),
            ('Lisans Planları','admin/license-center/plans','Lisans','tek kullanımlık domain bazlı cihaz süreli açık kaynak lisans planları',1),
            ('Kaynak Kod Lisanslama','admin/license-center/packages','Lisans','zip kaynak kod lisans ekle android php wordpress codecanyon market ürünü',1),
            ('Purchase Code Doğrulama','admin/license-center/external','Lisans','codecanyon envato purchase code lisans aktivasyon',1),
            ('Yardım Merkezi','admin/help-center','Yardım','api key nereden alınır hangi menü ne işe yarar kurulum eksik ayarlar',1)
            ON DUPLICATE KEY UPDATE keywords=VALUES(keywords),category=VALUES(category),is_active=1");
        db()->exec("INSERT INTO settings(setting_key,setting_value) VALUES ('ahost_version','25.0.0-rc25'),('license_center_enabled','1'),('license_center_v2_enabled','1'),('ai_help_center_enabled','1') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_license_key_generate($prefix='AHOST') {
    return $prefix . '-' . strtoupper(bin2hex(random_bytes(3))) . '-' . strtoupper(bin2hex(random_bytes(3))) . '-' . strtoupper(bin2hex(random_bytes(3)));
}
function ao_license_ensure_v1888() { static $done=false; if($done) return; $done=true;
    ao_schema_ensure_v1500();
    try {
        foreach ([
            'license_type'=>"VARCHAR(40) DEFAULT 'subscription'",
            'sales_channel'=>"VARCHAR(40) DEFAULT 'ahost_store'",
            'purchase_code'=>"VARCHAR(190) NULL",
            'bound_domain'=>"VARCHAR(190) NULL",
            'package_name'=>"VARCHAR(190) NULL",
            'product_family'=>"VARCHAR(60) DEFAULT 'ahost_one'",
            'license_payload'=>"LONGTEXT NULL",
            'license_signature'=>"LONGTEXT NULL",
            'last_verified_at'=>"DATETIME NULL",
            'offline_grace_days'=>"INT DEFAULT 30"
        ] as $col=>$def) { try { db()->exec("ALTER TABLE licenses ADD COLUMN IF NOT EXISTS {$col} {$def}"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
        foreach ([
            'assigned_license_key'=>"VARCHAR(160) NULL",
            'target_domain'=>"VARCHAR(190) NULL",
            'target_package_name'=>"VARCHAR(190) NULL",
            'sales_channel'=>"VARCHAR(40) DEFAULT 'ahost_store'",
            'purchase_code'=>"VARCHAR(190) NULL",
            'product_family'=>"VARCHAR(60) DEFAULT 'ahost_one'"
        ] as $col=>$def) { try { db()->exec("ALTER TABLE code_packages ADD COLUMN IF NOT EXISTS {$col} {$def}"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
        if (!admin_setting('license_private_key','') || !admin_setting('license_public_key','')) ao_license_generate_keypair();
        save_setting('license_center_v2_enabled','1'); save_setting('license_offline_first','1'); save_setting('ahost_version','25.0.0-rc25');
    } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_license_generate_keypair() {
    if (function_exists('openssl_pkey_new')) {
        $res = @openssl_pkey_new(['private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);
        if ($res) { $private=''; @openssl_pkey_export($res,$private); $details=@openssl_pkey_get_details($res); if($private && !empty($details['key'])) { save_setting('license_private_key',$private); save_setting('license_public_key',$details['key']); return true; } }
    }
    if (!admin_setting('license_hmac_secret','')) save_setting('license_hmac_secret', bin2hex(random_bytes(32)));
    return false;
}
function ao_license_payload_build($licenseKey,$domain='',$packageName='',$expires=null,$type='subscription',$productId=0,$customerId=0,$salesChannel='ahost_store',$purchaseCode='',$productFamily='ahost_one') {
    return ['license_key'=>$licenseKey,'domain'=>strtolower(trim($domain)),'package_name'=>trim($packageName),'expires_at'=>$expires,'license_type'=>$type,'product_id'=>(int)$productId,'customer_id'=>(int)$customerId,'sales_channel'=>$salesChannel,'purchase_code_hash'=>$purchaseCode!==''?hash('sha256',$purchaseCode):null,'product_family'=>$productFamily,'offline_first'=>true,'issued_at'=>gmdate('c'),'issuer'=>'Ahost One License Center Pro 2.0','version'=>'25.3.0'];
}
function ao_license_sign_payload($payload) {
    $json=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $private=admin_setting('license_private_key','');
    if($private && function_exists('openssl_sign')) { $sig=''; if(@openssl_sign($json,$sig,$private,OPENSSL_ALGO_SHA256)) return base64_encode($sig); }
    $secret=admin_setting('license_hmac_secret',''); if(!$secret){ $secret=bin2hex(random_bytes(32)); save_setting('license_hmac_secret',$secret); }
    return 'hmac:'.hash_hmac('sha256',$json,$secret);
}
function ao_license_client_php($licenseEndpoint=null) {
    $publicKey = admin_setting('license_public_key','');
    $publicExport = var_export($publicKey, true);
    return "<?php\n".
"// Ahost One Offline-First License Client - otomatik eklendi.\n".
"function ahost_license_block(\$m){http_response_code(403);echo '<div style=\"font-family:Arial;padding:30px;margin:30px;border:1px solid #ddd;border-radius:12px\"><h2>Lisans Uyarısı</h2><p>'.htmlspecialchars(\$m,ENT_QUOTES,'UTF-8').'</p></div>';exit;}\n".
"function ahost_license_verify_offline(\$licenseFile=__DIR__.'/license.json',\$signatureFile=__DIR__.'/license.sig'){\n".
"    \$publicKey = ".$publicExport.";\n".
"    if(!is_file(\$licenseFile)||!is_file(\$signatureFile)) ahost_license_block('Lisans dosyası bulunamadı.');\n".
"    \$json=file_get_contents(\$licenseFile); \$sig=trim(file_get_contents(\$signatureFile)); \$p=json_decode(\$json,true); if(!is_array(\$p)) ahost_license_block('Lisans dosyası okunamadı.');\n".
"    if(\$publicKey && strpos(\$sig,'hmac:')!==0 && function_exists('openssl_verify')){\$ok=openssl_verify(\$json,base64_decode(\$sig),\$publicKey,OPENSSL_ALGO_SHA256); if(\$ok!==1) ahost_license_block('Lisans imzası geçersiz.');}\n".
"    \$host=strtolower(\$_SERVER['HTTP_HOST']??''); \$host=preg_replace('/^www\\./','',\$host); \$domain=strtolower(\$p['domain']??''); \$domain=preg_replace('/^www\\./','',\$domain);\n".
"    if(\$domain && \$host && \$host!==\$domain && !str_ends_with(\$host,'.'.\$domain)) ahost_license_block('Lisans domaini bu siteyle eşleşmiyor.');\n".
"    if((\$p['license_type']??'')!=='lifetime' && (\$p['license_type']??'')!=='domain_lifetime' && !empty(\$p['expires_at']) && strtotime(\$p['expires_at'])<time()) ahost_license_block('Lisans süresi doldu.');\n".
"    return true;}\n".
"ahost_license_verify_offline();\n";
}
function ao_license_product_family($productId, $fallback='ahost_one') {
    try { if($productId){ $q=db()->prepare('SELECT product_type FROM license_products WHERE id=?'); $q->execute([(int)$productId]); $v=(string)$q->fetchColumn(); if($v) return $v; } } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return $fallback ?: 'ahost_one';
}
function ao_license_normalize_request($productFamily, $licenseType, $domain, $packageName, $expires, $plan=null) {
    $family = trim((string)$productFamily) ?: 'ahost_one';
    $type = trim((string)$licenseType) ?: 'monthly';
    $domain = strtolower(trim((string)$domain));
    $packageName = trim((string)$packageName);
    if ($family === 'android') {
        if ($packageName === '') throw new Exception('Android ürünü için paket adı zorunludur. Örnek: com.firma.app');
        $domain = '';
        $type = 'android_lifetime';
        $expires = null;
    } else {
        if ($domain === '') throw new Exception('Ahost One ve web tasarım ürünleri için domain zorunludur.');
        $packageName = '';
        if (in_array($type, ['lifetime','domain_lifetime'], true)) $expires = null;
        elseif (!$expires && is_array($plan) && (int)($plan['duration_days'] ?? 0) > 0) $expires = date('Y-m-d H:i:s', time()+86400*(int)$plan['duration_days']);
        elseif (!$expires && $type === 'monthly') $expires = date('Y-m-d H:i:s', time()+86400*30);
        elseif (!$expires && $type === 'yearly') $expires = date('Y-m-d H:i:s', time()+86400*365);
    }
    return [$family,$type,$domain,$packageName,$expires];
}
function ao_license_android_client_text() {
    return "Ahost One Android License Client\n".
"1) Lisans kodunu uygulama kaynak kodunda güvenli sabit/değer olarak ekleyin.\n".
"2) packageName, license.json içindeki package_name ile eşleşmelidir.\n".
"3) Android lisansı ömür boyudur; süre kontrolü yapılmaz.\n".
"4) Kaynak kod dağıtımında kendi Kotlin/Java kontrol sınıfınıza license.json + license.sig doğrulamasını bağlayın.\n";
}
function ao_license_inject_zip($sourceZip, $title='Licensed Package', $licenseKey='', $domain='', $packageName='', $expires=null, $type='subscription', $salesChannel='ahost_store', $purchaseCode='', $productFamily='ahost_one') {
    ao_license_ensure_v1888();
    if (!is_file($sourceZip)) throw new Exception('Kaynak ZIP bulunamadı.');
    $outDir = __DIR__ . '/storage/exports/licenses'; if (!is_dir($outDir)) mkdir($outDir, 0775, true);
    $out = $outDir . '/licensed_' . preg_replace('/[^a-z0-9_-]+/i','-', pathinfo($sourceZip, PATHINFO_FILENAME)) . '_' . date('Ymd_His') . '.zip';
    $zip = new ZipArchive(); if ($zip->open($sourceZip) !== true) throw new Exception('Kaynak ZIP açılamadı.');
    $new = new ZipArchive(); if ($new->open($out, ZipArchive::CREATE) !== true) throw new Exception('Lisanslı ZIP oluşturulamadı.');
    $summary=[];
    for($i=0;$i<$zip->numFiles;$i++){ $stat=$zip->statIndex($i); $name=$stat['name']; if(str_ends_with($name,'/')){ $new->addEmptyDir($name); continue; } $data=$zip->getFromIndex($i); $new->addFromString($name,$data); if(preg_match('/\.(php|java|kt|gradle|js)$/i',$name)) $summary[]=$name; }
    if(!$licenseKey) $licenseKey=ao_license_key_generate();
    [$productFamily,$type,$domain,$packageName,$expires] = ao_license_normalize_request($productFamily,$type,$domain,$packageName,$expires,null);
    if($salesChannel==='codecanyon' && trim($purchaseCode)==='') throw new Exception('CodeCanyon satış kanalı için purchase code zorunludur.');
    $payload=ao_license_payload_build($licenseKey,$domain,$packageName,$expires,$type,0,0,$salesChannel,$purchaseCode,$productFamily);
    $signature=ao_license_sign_payload($payload);
    $new->addFromString('ahost-license-client.php', ao_license_client_php());
    $new->addFromString('license.json', json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
    $new->addFromString('license.sig', $signature);
    $new->addFromString('public.key', admin_setting('license_public_key',''));
    $new->addFromString('AHOST_LICENSE_README.txt', "Bu paket Ahost One License Center Pro 2.0 tarafından offline-first imzalı lisans ile hazırlanmıştır. Lisans kodu: {$licenseKey}\n");
    $zip->close(); $new->close();
    db()->prepare('INSERT INTO code_packages(title,original_file,licensed_file,license_mode,scan_summary,injection_status,assigned_license_key,target_domain,target_package_name,sales_channel,purchase_code,product_family) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$title,$sourceZip,$out,'licensed',json_encode(['scanned_files'=>$summary,'offline_first'=>true,'expires_at'=>$expires,'license_type'=>$type],JSON_UNESCAPED_UNICODE),'completed',$licenseKey,$domain,$packageName,$salesChannel,$purchaseCode,$productFamily]);
    return $out;
}

if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/license-center/product-save') { require_admin(); verify_csrf(); ao_license_ensure_v1888();
    $name=trim($_POST['name']??''); $type=trim($_POST['product_type']??'php_script'); $desc=trim($_POST['description']??'');
    if($name===''){ flash('error','Ürün adı zorunlu.'); redirect_to('admin/license-center'); }
    db()->prepare('INSERT INTO license_products(name,product_type,description,status) VALUES(?,?,?,"active")')->execute([$name,$type,$desc]);
    flash('success','Lisans ürünü eklendi.'); redirect_to('admin/license-center');
}

if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/license-center/product-update') { require_admin(); verify_csrf(); ao_license_ensure_v1888();
    $id=(int)($_POST['id']??0); $name=trim($_POST['name']??''); $type=trim($_POST['product_type']??'php_script'); $desc=trim($_POST['description']??''); $status=in_array(($_POST['status']??'active'),['active','inactive','passive'],true)?$_POST['status']:'active';
    if($id<=0 || $name===''){ flash('error','Güncelleme için ürün ve ürün adı zorunlu.'); redirect_to('admin/license-center'); }
    try{ db()->prepare('UPDATE license_products SET name=?, product_type=?, description=?, status=? WHERE id=?')->execute([$name,$type,$desc,$status,$id]); flash('success','Lisans ürünü güncellendi.'); }
    catch(Throwable $e){ flash('error','Lisans ürünü güncellenemedi: '.$e->getMessage()); }
    redirect_to('admin/license-center');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/license-center/product-delete') { require_admin(); verify_csrf(); ao_license_ensure_v1888();
    $id=(int)($_POST['id']??0);
    if($id<=0){ flash('error','Silinecek ürün bulunamadı.'); redirect_to('admin/license-center'); }
    try{
        $q=db()->prepare('SELECT COUNT(*) FROM licenses WHERE product_id=?'); $q->execute([$id]); $licenseCount=(int)$q->fetchColumn();
        $q=db()->prepare('SELECT COUNT(*) FROM license_plans WHERE product_id=?'); $q->execute([$id]); $planCount=(int)$q->fetchColumn();
        if($licenseCount>0 || $planCount>0){ db()->prepare('UPDATE license_products SET status="inactive" WHERE id=?')->execute([$id]); flash('success','Ürünün bağlı lisans/plan kaydı olduğu için pasife alındı.'); }
        else { db()->prepare('DELETE FROM license_products WHERE id=?')->execute([$id]); flash('success','Lisans ürünü silindi.'); }
    }catch(Throwable $e){ flash('error','Lisans ürünü silinemedi: '.$e->getMessage()); }
    redirect_to('admin/license-center');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/license-center/plan-save') { require_admin(); verify_csrf(); ao_license_ensure_v1888();
    db()->prepare('INSERT INTO license_plans(product_id,name,license_type,duration_days,max_domains,max_devices,is_open_source,price,currency,status) VALUES(?,?,?,?,?,?,?,?,?,"active")')->execute([(int)($_POST['product_id']??0),trim($_POST['name']??'Plan'),trim($_POST['license_type']??'single_use'),(int)($_POST['duration_days']??0),(int)($_POST['max_domains']??1),(int)($_POST['max_devices']??1),(int)($_POST['is_open_source']??0),(float)($_POST['price']??0),trim($_POST['currency']??'TRY')]);
    flash('success','Lisans planı eklendi.'); redirect_to('admin/license-center/plans');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/license-center/license-generate') { require_admin(); verify_csrf(); ao_license_ensure_v1888();
    try {
        $plan=(int)($_POST['plan_id']??0); $product=(int)($_POST['product_id']??0); $customer=(int)($_POST['customer_id']??0); $domain=trim($_POST['domain']??''); $packageName=trim($_POST['package_name']??''); $type=trim($_POST['license_type']??'monthly');
        $salesChannel=trim($_POST['sales_channel']??'ahost_store'); $purchaseCode=trim($_POST['purchase_code']??''); if($salesChannel==='codecanyon' && $purchaseCode==='') throw new Exception('CodeCanyon için purchase code zorunludur. Site içi satışta boş bırakılır.');
        $key=ao_license_key_generate(); $expires=null; $p=null;
        if($plan){ $q=db()->prepare('SELECT * FROM license_plans WHERE id=?'); $q->execute([$plan]); $p=$q->fetch(); if($p && (int)$p['duration_days']>0) $expires=date('Y-m-d H:i:s', time()+86400*(int)$p['duration_days']); if($p && !$product) $product=(int)$p['product_id']; if($p && empty($_POST['license_type'])) $type=(string)$p['license_type']; }
        $productFamily=trim($_POST['product_family']??'') ?: ao_license_product_family($product,'ahost_one');
        [$productFamily,$type,$domain,$packageName,$expires] = ao_license_normalize_request($productFamily,$type,$domain,$packageName,$expires,$p ?: null);
        $payload=ao_license_payload_build($key,$domain,$packageName,$expires,$type,$product,$customer,$salesChannel,$purchaseCode,$productFamily); $sig=ao_license_sign_payload($payload);
        db()->prepare('INSERT INTO licenses(license_key,product_id,plan_id,customer_id,domain,status,starts_at,expires_at,license_type,sales_channel,purchase_code,bound_domain,package_name,product_family,license_payload,license_signature,offline_grace_days) VALUES(?,?,?,?,?,"active",NOW(),?,?,?,?,?,?,?,?,?,?)')->execute([$key,$product,$plan,$customer,$domain,$expires,$type,$salesChannel,$purchaseCode,$domain,$packageName,$productFamily,json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$sig,(int)($_POST['offline_grace_days']??30)]);
        flash('success','Offline-first imzalı lisans üretildi: '.$key);
    } catch(Throwable $e) { flash('error','Lisans üretilemedi: '.$e->getMessage()); }
    redirect_to('admin/license-center/licenses');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/license-center/package-upload') { require_admin(); verify_csrf(); ao_license_ensure_v1888();
    try{ if(empty($_FILES['package']['tmp_name'])) throw new Exception('ZIP dosyası seçilmedi.'); $dir=__DIR__.'/storage/uploads/license-packages'; if(!is_dir($dir)) mkdir($dir,0775,true); $target=$dir.'/'.date('Ymd_His').'_'.preg_replace('/[^a-zA-Z0-9_.-]+/','_',$_FILES['package']['name']); move_uploaded_file($_FILES['package']['tmp_name'],$target); $out=ao_license_inject_zip($target, trim($_POST['title']??'Lisanslı Paket'), trim($_POST['license_key']??''), trim($_POST['domain']??''), trim($_POST['package_name']??''), trim($_POST['expires_at']??'') ?: null, trim($_POST['license_type']??'monthly'), trim($_POST['sales_channel']??'ahost_store'), trim($_POST['purchase_code']??''), trim($_POST['product_family']??'ahost_one')); flash('success','Offline lisans katmanı eklendi: '.basename($out)); }catch(Throwable $e){ flash('error','Paket işlenemedi: '.$e->getMessage()); }
    redirect_to('admin/license-center/packages');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/license-center/external-save') { require_admin(); verify_csrf(); ao_schema_ensure_v1500();
    db()->prepare('INSERT INTO external_marketplace_integrations(provider,name,api_endpoint,api_key,api_secret,status) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),api_endpoint=VALUES(api_endpoint),api_key=VALUES(api_key),api_secret=VALUES(api_secret),status=VALUES(status)')->execute([trim($_POST['provider']??'envato'),trim($_POST['name']??'Envato'),trim($_POST['api_endpoint']??''),trim($_POST['api_key']??''),trim($_POST['api_secret']??''),trim($_POST['status']??'inactive')]);
    flash('success','Harici marketplace bağlantısı kaydedildi.'); redirect_to('admin/license-center/external');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/license-center/purchase-verify') { require_admin(); verify_csrf(); ao_license_ensure_v1888();
    $provider=trim($_POST['provider']??'envato'); $code=trim($_POST['purchase_code']??''); if($code===''){ flash('error','Purchase Code zorunlu.'); redirect_to('admin/license-center/external'); }
    $key=ao_license_key_generate('EXT'); $payload=ao_license_payload_build($key,'','',null,'external',0,0); $sig=ao_license_sign_payload($payload);
    db()->prepare('INSERT INTO licenses(license_key,status,starts_at,license_type,license_payload,license_signature,metadata) VALUES(?,"active",NOW(),?,?,?,?)')->execute([$key,'external',json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$sig,json_encode(['provider'=>$provider,'purchase_code'=>$code],JSON_UNESCAPED_UNICODE)]);
    $licenseId=(int)db()->lastInsertId(); db()->prepare('INSERT INTO external_purchase_codes(provider,purchase_code,license_id,status,verified_at,raw_response) VALUES(?,?,?,"verified",NOW(),?) ON DUPLICATE KEY UPDATE license_id=VALUES(license_id),status="verified",verified_at=NOW()')->execute([$provider,$code,$licenseId,json_encode(['mode'=>'manual_or_api_ready','license_key'=>$key],JSON_UNESCAPED_UNICODE)]);
    flash('success','Purchase Code doğrulandı ve lisans üretildi: '.$key); redirect_to('admin/license-center/external');
}
if ($route==='api/license/verify') { ao_license_ensure_v1888(); header('Content-Type: application/json; charset=utf-8');
    $input=json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_REQUEST; $key=trim((string)($input['license_key']??'')); $domain=strtolower(trim((string)($input['domain']??''))); $pkg=trim((string)($input['package_name']??($input['app']??'')));
    $q=db()->prepare('SELECT * FROM licenses WHERE license_key=? LIMIT 1'); $q->execute([$key]); $lic=$q->fetch(); $valid=false; $message='Lisans bulunamadı.';
    if($lic){ $valid=($lic['status']==='active'||$lic['status']==='trial') && (empty($lic['expires_at']) || strtotime($lic['expires_at'])>=time()); if($valid && !empty($lic['bound_domain'])){ $bd=strtolower(preg_replace('/^www\./','',$lic['bound_domain'])); $hd=strtolower(preg_replace('/^www\./','',$domain)); if($hd && $bd && $hd!==$bd && !str_ends_with($hd,'.'.$bd)){ $valid=false; $message='Domain lisansla eşleşmiyor.'; } } if($valid && !empty($lic['package_name']) && $pkg && trim($lic['package_name'])!==$pkg){ $valid=false; $message='Paket adı lisansla eşleşmiyor.'; } if($valid){ $message='Lisans geçerli.'; db()->prepare('INSERT INTO license_activations(license_id,domain,ip_address,device_hash,status,last_check_at) VALUES(?,?,?,?,"active",NOW())')->execute([(int)$lic['id'],$domain,$_SERVER['REMOTE_ADDR']??'',$pkg]); db()->prepare('UPDATE licenses SET last_verified_at=NOW() WHERE id=?')->execute([(int)$lic['id']]); } elseif($message==='Lisans bulunamadı.') $message='Lisans pasif veya süresi dolmuş.'; }
    echo json_encode(['valid'=>$valid,'message'=>$message,'offline_first'=>true,'domain'=>$domain],JSON_UNESCAPED_UNICODE); exit;
}

function ao_smtp_test_send($to=null, $override=[]) {
    $host=$override['smtp_host'] ?? admin_setting('smtp_host',''); $port=(int)($override['smtp_port'] ?? admin_setting('smtp_port','587'));
    $user=$override['smtp_username'] ?? admin_setting('smtp_username',''); $pass=$override['smtp_password'] ?? admin_setting('smtp_password','');
    $from=$override['smtp_from'] ?? admin_setting('smtp_from', admin_setting('company_email','noreply@example.com')); $name=$override['smtp_from_name'] ?? admin_setting('smtp_from_name','Ahost One'); $to=$to ?: $from;
    if(!$host || !$from || !$to) return [false,'SMTP host, gönderen veya test adresi eksik.'];
    $scheme=((int)$port===465)?'ssl://':''; $errno=0; $errstr=''; $fp=@stream_socket_client($scheme.$host.':'.$port,$errno,$errstr,12,STREAM_CLIENT_CONNECT);
    if(!$fp) return [false,'SMTP bağlantısı başarısız: '.$errstr.' ('.$errno.')']; stream_set_timeout($fp,12);
    $read=function() use($fp){ return trim((string)fgets($fp,515)); }; $write=function($cmd) use($fp){ fwrite($fp,$cmd."\r\n"); };
    $read(); $write('EHLO '.($_SERVER['HTTP_HOST'] ?? 'localhost')); $resp=''; for($i=0;$i<8;$i++){ $line=$read(); $resp.=$line."\n"; if(!str_starts_with($line,'250-')) break; }
    if((int)$port===587 && stripos($resp,'STARTTLS')!==false){ $write('STARTTLS'); $tls=$read(); if(str_starts_with($tls,'220')){ @stream_socket_enable_crypto($fp,true,STREAM_CRYPTO_METHOD_TLS_CLIENT); $write('EHLO '.($_SERVER['HTTP_HOST'] ?? 'localhost')); for($i=0;$i<8;$i++){ $line=$read(); if(!str_starts_with($line,'250-')) break; } } }
    if($user){ $write('AUTH LOGIN'); $read(); $write(base64_encode($user)); $read(); $write(base64_encode($pass)); $auth=$read(); if(!str_starts_with($auth,'235')){ fclose($fp); return [false,'SMTP kimlik doğrulama başarısız: '.$auth]; } }
    $write('MAIL FROM:<'.$from.'>'); $m=$read(); if(!preg_match('/^(250|251)/',$m)){ fclose($fp); return [false,'MAIL FROM reddedildi: '.$m]; }
    $write('RCPT TO:<'.$to.'>'); $r=$read(); if(!preg_match('/^(250|251)/',$r)){ fclose($fp); return [false,'RCPT TO reddedildi: '.$r]; }
    $write('DATA'); $d=$read(); if(!str_starts_with($d,'354')){ fclose($fp); return [false,'DATA komutu reddedildi: '.$d]; }
    $write("Subject: Ahost One SMTP Test\r\nFrom: {$name} <{$from}>\r\nTo: <{$to}>\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\nSMTP test başarılı. Tarih: ".date('Y-m-d H:i:s')."\r\n."); $sent=$read(); $write('QUIT'); fclose($fp);
    if(!preg_match('/^250/',$sent)) return [false,'Mesaj gönderimi doğrulanamadı: '.$sent]; save_setting('smtp_last_test_status','success'); save_setting('smtp_last_test_at',date('Y-m-d H:i:s')); return [true,'SMTP test maili başarıyla gönderildi.'];
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/smtp-test') { require_admin(); verify_csrf(); [$ok,$msg]=ao_smtp_test_send(trim($_POST['test_email'] ?? ''), $_POST['settings'] ?? []); flash($ok?'success':'error',$msg); redirect_to($_POST['return'] ?? 'admin/setup-wizard'); }

