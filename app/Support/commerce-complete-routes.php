<?php
// v12.0.0 Commerce Complete - Domain + Hosting + Marketplace completion
function ao_schema_ensure_v1200() {
    static $done=false; if($done) return; $done=true;
    try { db()->exec("CREATE TABLE IF NOT EXISTS marketplace_escrow (id INT AUTO_INCREMENT PRIMARY KEY, listing_id INT NULL, order_id INT NULL, buyer_customer_id INT NULL, seller_customer_id INT NULL, amount DECIMAL(14,2) DEFAULT 0, currency VARCHAR(10) DEFAULT 'TRY', status ENUM('pending','funded','delivered','approved','released','disputed','refunded') DEFAULT 'pending', release_note TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, KEY listing_id(listing_id), KEY status(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS marketplace_auctions (id INT AUTO_INCREMENT PRIMARY KEY, listing_id INT NOT NULL, start_price DECIMAL(14,2) DEFAULT 0, min_increment DECIMAL(14,2) DEFAULT 10, buy_now_price DECIMAL(14,2) DEFAULT NULL, starts_at DATETIME NULL, ends_at DATETIME NULL, status ENUM('draft','active','ended','cancelled') DEFAULT 'draft', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY listing_id(listing_id), KEY status(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS marketplace_revenue (id INT AUTO_INCREMENT PRIMARY KEY, source_type VARCHAR(80) NOT NULL, source_id INT NULL, amount DECIMAL(14,2) DEFAULT 0, currency VARCHAR(10) DEFAULT 'TRY', description TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY source_type(source_type)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS hosting_health_checks (id INT AUTO_INCREMENT PRIMARY KEY, server_id INT NULL, service_id INT NULL, check_type VARCHAR(80) DEFAULT 'server', status ENUM('pass','warning','fail') DEFAULT 'pass', load_avg VARCHAR(80) DEFAULT NULL, disk_percent DECIMAL(6,2) DEFAULT NULL, memory_percent DECIMAL(6,2) DEFAULT NULL, message TEXT NULL, checked_at DATETIME DEFAULT CURRENT_TIMESTAMP, KEY server_id(server_id), KEY service_id(service_id), KEY status(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS hosting_operation_queue (id INT AUTO_INCREMENT PRIMARY KEY, service_id INT NULL, server_id INT NULL, operation VARCHAR(80) NOT NULL, status ENUM('pending','running','done','failed') DEFAULT 'pending', request_payload LONGTEXT NULL, response_payload LONGTEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, executed_at DATETIME NULL, KEY service_id(service_id), KEY operation(operation), KEY status(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS domain_operation_logs (id INT AUTO_INCREMENT PRIMARY KEY, domain_id INT NULL, domain_name VARCHAR(190) NOT NULL, operation VARCHAR(80) NOT NULL, registrar VARCHAR(120) DEFAULT NULL, status ENUM('pending','success','failed') DEFAULT 'pending', message TEXT NULL, raw_response LONGTEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY domain_name(domain_name), KEY operation(operation), KEY status(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS registrar_capability_matrix (id INT AUTO_INCREMENT PRIMARY KEY, registrar_slug VARCHAR(120) NOT NULL, operation VARCHAR(80) NOT NULL, is_supported TINYINT(1) DEFAULT 1, test_status ENUM('unknown','pass','fail') DEFAULT 'unknown', last_message TEXT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_reg_operation(registrar_slug,operation)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS commerce_completion_checks (id INT AUTO_INCREMENT PRIMARY KEY, module_key VARCHAR(120) NOT NULL, check_key VARCHAR(120) NOT NULL, title VARCHAR(190) NOT NULL, status ENUM('pass','warning','fail') DEFAULT 'warning', detail TEXT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_check(module_key,check_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE marketplace_listings ADD COLUMN sale_model ENUM('fixed','offer','auction') DEFAULT 'fixed'"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE marketplace_listings ADD COLUMN commission_percent DECIMAL(8,2) DEFAULT 5.00"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE marketplace_listings ADD COLUMN delivery_days INT DEFAULT 7"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE marketplace_offers ADD COLUMN counter_amount DECIMAL(14,2) DEFAULT NULL"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE marketplace_offers ADD COLUMN admin_note TEXT NULL"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $ops=['register','renew','transfer','epp','whois','dns','nameserver','lock','privacy'];
    foreach(['domainnameapi','resellerclub','enom','natro','isimtescil'] as $reg){ foreach($ops as $op){ try{ db()->prepare("INSERT INTO registrar_capability_matrix(registrar_slug,operation,is_supported,test_status) VALUES(?,?,1,'unknown') ON DUPLICATE KEY UPDATE is_supported=VALUES(is_supported)")->execute([$reg,$op]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } } }
    $cats=[['domain','Domain','domain',10],['hosting','Hosting','hosting',20],['web-tasarim','Web Tasarım','service',30],['seo','SEO Paketi','service',40],['logo-tasarim','Logo Tasarımı','service',50],['mobil-uygulama','Mobil Uygulama','service',60],['script-yazilim','Script / Yazılım','digital',70],['dijital-urun','Dijital Ürün','digital',80],['freelancer-hizmet','Freelancer Hizmeti','service',90]];
    foreach($cats as $c){ try{ db()->prepare("INSERT INTO marketplace_categories(slug,name,listing_type,sort_order,is_active) VALUES(?,?,?,?,1) ON DUPLICATE KEY UPDATE name=VALUES(name),listing_type=VALUES(listing_type),sort_order=VALUES(sort_order),is_active=1")->execute($c); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
    foreach([['Öne Çıkarma 7 Gün',7,99],['Öne Çıkarma 15 Gün',15,179],['Öne Çıkarma 30 Gün',30,299],['Öne Çıkarma 60 Gün',60,499]] as $p){ try{ db()->prepare("INSERT INTO marketplace_feature_packages(name,days,price,currency,badge,is_active) VALUES(?,?,?,?,?,1) ON DUPLICATE KEY UPDATE name=VALUES(name),price=VALUES(price),currency=VALUES(currency),badge=VALUES(badge),is_active=1")->execute([$p[0],$p[1],$p[2],'TRY','Öne Çıkan']); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
    $checks=[
      ['domain','registrars','Registrar operasyonları','warning','DomainNameAPI aktif; diğer registrarlar test bekliyor.'],
      ['domain','intelligence','Domain Intelligence','pass','SSL/DNS/WHOIS/SEO/değerleme ekranları mevcut.'],
      ['hosting','operations','Hosting operasyonları','warning','Create/suspend/unsuspend/terminate kuyruk ve butonları mevcut; canlı panel testi gerekir.'],
      ['hosting','health','Hosting sağlık kontrolü','pass','Sunucu ve hizmet sağlık kayıt tablosu hazır.'],
      ['marketplace','categories','Çoklu marketplace kategori','pass','Domain, hosting, web tasarım, SEO, logo, mobil uygulama, script ve dijital ürün kategorileri eklendi.'],
      ['marketplace','escrow','Escrow iş akışı','pass','Escrow kayıt altyapısı hazır.'],
      ['marketplace','featured','Öne çıkarma paketleri','pass','7/15/30/60 gün paketleri tekil.']
    ];
    foreach($checks as $c){ try{ db()->prepare("INSERT INTO commerce_completion_checks(module_key,check_key,title,status,detail) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status),detail=VALUES(detail)")->execute($c); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
    try{ db()->prepare("INSERT INTO settings(setting_key,setting_value) VALUES('ahost_version','25.0.0-rc25') ON DUPLICATE KEY UPDATE setting_value='25.0.0-rc25'")->execute(); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $search=[
      ['Commerce Complete','admin/commerce-complete','Ticaret','domain hosting marketplace tamamlandı commerce complete üretim kontrol'],
      ['Marketplace Teklifleri','admin/marketplace/offers','Marketplace','teklif karşı teklif kabul red marketplace'],
      ['Marketplace Escrow','admin/marketplace/escrow','Marketplace','escrow emanet ödeme iş teslim alıcı onay'],
      ['Marketplace Açık Artırma','admin/marketplace/auctions','Marketplace','açık artırma auction teklif minimum artış'],
      ['Hosting Sağlık Kontrolü','admin/hosting-server/health','Hosting','hosting sağlık disk cpu ram load sunucu kontrol'],
      ['Domain Operasyon Logları','admin/domain-center/operations','Domain','domain kayıt yenileme transfer epp whois dns operasyon log']
    ];
    foreach($search as $s){ try{ db()->prepare("INSERT INTO admin_search_index(title,route,category,keywords,is_active) VALUES(?,?,?,?,1) ON DUPLICATE KEY UPDATE keywords=VALUES(keywords),category=VALUES(category),is_active=1")->execute($s); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
}
ao_schema_ensure_v1200();
function ao_commerce_completion_summary(){
    ao_schema_ensure_v1200(); $rows=[];
    try{ $rows=db()->query('SELECT * FROM commerce_completion_checks ORDER BY module_key,check_key')->fetchAll(); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return $rows;
}
function ao_v1200_count($table,$where='1=1'){ try{return (int)db()->query("SELECT COUNT(*) FROM $table WHERE $where")->fetchColumn();}catch(Throwable $e){return 0;} }

if ($route === 'admin/marketplace/offer-update' && $_SERVER['REQUEST_METHOD']==='POST') {
    require_admin(); verify_csrf(); ao_schema_ensure_v1200();
    $id=(int)($_POST['id']??0); $status=$_POST['status']??'pending'; $counter=$_POST['counter_amount']!=='' ? (float)$_POST['counter_amount'] : null; $note=trim($_POST['admin_note']??'');
    try{ db()->prepare('UPDATE marketplace_offers SET status=?, counter_amount=?, admin_note=? WHERE id=?')->execute([$status,$counter,$note,$id]); flash('success','Teklif güncellendi.'); }catch(Throwable $e){ flash('error','Teklif güncellenemedi: '.$e->getMessage()); }
    redirect_to('admin/marketplace/offers');
}
if ($route === 'admin/hosting-server/queue-operation' && $_SERVER['REQUEST_METHOD']==='POST') {
    require_admin(); verify_csrf(); ao_schema_ensure_v1200();
    $service=(int)($_POST['service_id']??0); $op=$_POST['operation']??'health-check';
    try{ db()->prepare('INSERT INTO hosting_operation_queue(service_id,operation,status,request_payload) VALUES(?,?,"pending",?)')->execute([$service,$op,json_encode($_POST,JSON_UNESCAPED_UNICODE)]); flash('success','Hosting operasyonu kuyruğa alındı.'); }catch(Throwable $e){ flash('error','Operasyon eklenemedi: '.$e->getMessage()); }
    redirect_to('admin/hosting-server/accounts');
}
if ($route === 'marketplace/offer' && $_SERVER['REQUEST_METHOD']==='POST') {
    ao_schema_ensure_v1200();
    $listing=(int)($_POST['listing_id']??0); $amount=(float)($_POST['offer_amount']??0); $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $msg=trim($_POST['message']??'');
    try{ db()->prepare('INSERT INTO marketplace_offers(listing_id,name,email,offer_amount,message,status) VALUES(?,?,?,?,?,"pending")')->execute([$listing,$name,$email,$amount,$msg]); flash('success','Teklifiniz alındı.'); }catch(Throwable $e){ flash('error','Teklif alınamadı: '.$e->getMessage()); }
    redirect_to('marketplace');
}
if ($route === 'marketplace/listing-save' && $_SERVER['REQUEST_METHOD']==='POST') {
    require_customer(); verify_csrf(); ao_schema_ensure_v1200();
    $customer = current_customer();
    try {
        $title = trim((string)($_POST['title'] ?? ''));
        if ($title === '') throw new Exception('İlan başlığı zorunlu.');
        $price = max(0, (float)($_POST['price'] ?? 0));
        if ($price <= 0) throw new Exception('Satış fiyatı sıfırdan büyük olmalı.');
        $allowedTypes = ['domain','web_design','seo','logo_design','digital_content','mobile_app','hosting','software','service'];
        $listingType = trim((string)($_POST['listing_type'] ?? 'service'));
        if (!in_array($listingType, $allowedTypes, true)) $listingType = 'service';
        $currency = strtoupper(trim((string)($_POST['currency'] ?? 'TRY')));
        if (!in_array($currency, ['TRY','USD','EUR'], true)) $currency = 'TRY';
        $saleModel = trim((string)($_POST['sale_model'] ?? 'fixed'));
        if (!in_array($saleModel, ['fixed','offer','auction'], true)) $saleModel = 'fixed';
        $commission = (float)admin_setting('marketplace_default_commission_percent', '5');
        if ($commission < 0) $commission = 0;
        if ($commission > 80) $commission = 80;
        $deliveryDays = max(1, min(365, (int)($_POST['delivery_days'] ?? 7)));
        $domainField = $listingType === 'domain' ? ahost_domain_clean($_POST['domain_name'] ?? '') : trim((string)($_POST['domain_name'] ?? ''));
        db()->prepare('INSERT INTO marketplace_listings(seller_type,seller_customer_id,listing_type,title,domain_name,description,category,price,currency,status,is_featured,is_premium,is_urgent,sale_model,commission_percent,delivery_days) VALUES("customer",?,?,?,?,?,?,?,?, "pending",0,0,0,?,?,?)')
          ->execute([(int)($customer['id'] ?? 0), $listingType, $title, $domainField, trim((string)($_POST['description'] ?? '')), trim((string)($_POST['category'] ?? '')), $price, $currency, $saleModel, $commission, $deliveryDays]);
        flash('success','İlanınız alındı. Admin onayından sonra marketplace vitrininde yayınlanacak.');
    } catch(Throwable $e) {
        flash('error','İlan oluşturulamadı: '.$e->getMessage());
    }
    redirect_to('marketplace#ilan-olustur');
}
