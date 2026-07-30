<?php
// System management routes and helpers for backup, update, notification and product groups.
function ao_v1000_ensure_schema() { static $done=false; if($done) return; $done=true;
    try { ao_v980_ensure_schema(); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS update_history (id INT AUTO_INCREMENT PRIMARY KEY, version VARCHAR(40), migration_file VARCHAR(180), status VARCHAR(30) DEFAULT 'pending', message TEXT NULL, executed_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS notification_events (id INT AUTO_INCREMENT PRIMARY KEY, event_key VARCHAR(120), title VARCHAR(180), channel VARCHAR(50), status VARCHAR(30) DEFAULT 'active', template_id INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("INSERT IGNORE INTO admin_search_index(title,route,category,keywords,is_active) VALUES
        ('Update Center','admin/update-center','Sistem','güncelleme update migration sürüm versiyon database schema',1),
        ('Database Upgrade Wizard','admin/database-upgrade','Sistem','database upgrade wizard schema tablo kolon shopier odeme builder domain destek eksik kolon onar',1),
        ('Notification Center','admin/notification-center','Bildirim','mail sms whatsapp bildirim şablon olay tetikleyici epp fatura ticket',1),
        ('Yedek Geri Yükle','admin/backup-center','Sistem','restore geri yükle yedek database files full backup',1)"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("INSERT IGNORE INTO notification_events(event_key,title,channel,status) VALUES
        ('invoice.created','Fatura Oluşturuldu','mail,sms,whatsapp','active'),
        ('invoice.paid','Fatura Ödendi','mail,sms,whatsapp','active'),
        ('ticket.opened','Ticket Açıldı','mail,sms,whatsapp','active'),
        ('domain.epp.requested','EPP Kodu İstendi','sms,mail','active'),
        ('service.suspended','Hizmet Askıya Alındı','mail,sms','active'),
        ('domain.expiring','Domain Süresi Yaklaşıyor','mail,sms,whatsapp','active')"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_db_upgrade_ident($name){ return (bool)preg_match('/^[A-Za-z0-9_]+$/', (string)$name); }
function ao_db_upgrade_table_exists($table){
    if(!ao_db_upgrade_ident($table)) return false;
    try{ $q=db()->prepare('SHOW TABLES LIKE ?'); $q->execute([$table]); return (bool)$q->fetchColumn(); }catch(Throwable $e){ return false; }
}
function ao_db_upgrade_column_exists($table,$column){
    if(!ao_db_upgrade_ident($table) || !ao_db_upgrade_ident($column) || !ao_db_upgrade_table_exists($table)) return false;
    try{ $q=db()->prepare('SHOW COLUMNS FROM `'.$table.'` LIKE ?'); $q->execute([$column]); return (bool)$q->fetch(); }catch(Throwable $e){ return false; }
}
function ao_database_upgrade_specs(){
    return [
        ['category'=>'Core','table'=>'settings','title'=>'Settings table','create'=>"CREATE TABLE IF NOT EXISTS settings (id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(160) NOT NULL UNIQUE, setting_value LONGTEXT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",'columns'=>[
            'setting_key'=>"ALTER TABLE settings ADD COLUMN setting_key VARCHAR(160) NOT NULL AFTER id",
            'setting_value'=>"ALTER TABLE settings ADD COLUMN setting_value LONGTEXT NULL AFTER setting_key",
            'updated_at'=>"ALTER TABLE settings ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
        ]],
        ['category'=>'Core','table'=>'admin_search_index','title'=>'Admin search index','create'=>"CREATE TABLE IF NOT EXISTS admin_search_index (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(160) NOT NULL, route VARCHAR(190) NOT NULL, keywords TEXT NULL, category VARCHAR(100) NULL, module VARCHAR(100) NULL, description TEXT NULL, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_route_title(route,title)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",'columns'=>[
            'keywords'=>"ALTER TABLE admin_search_index ADD COLUMN keywords TEXT NULL AFTER route",
            'category'=>"ALTER TABLE admin_search_index ADD COLUMN category VARCHAR(100) NULL AFTER keywords",
            'module'=>"ALTER TABLE admin_search_index ADD COLUMN module VARCHAR(100) NULL AFTER category",
            'description'=>"ALTER TABLE admin_search_index ADD COLUMN description TEXT NULL AFTER module",
            'is_active'=>"ALTER TABLE admin_search_index ADD COLUMN is_active TINYINT(1) DEFAULT 1",
        ]],
        ['category'=>'Payment','table'=>'payments','title'=>'Payments compatibility','create'=>"CREATE TABLE IF NOT EXISTS payments (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NULL, invoice_id INT NULL, type VARCHAR(80) DEFAULT 'payment', method VARCHAR(80) DEFAULT 'manual', gateway VARCHAR(80) DEFAULT 'manual', amount DECIMAL(14,2) DEFAULT 0.00, currency VARCHAR(10) DEFAULT 'TRY', transaction_id VARCHAR(160) NULL, status VARCHAR(40) DEFAULT 'completed', notes TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY customer_id(customer_id), KEY invoice_id(invoice_id), KEY status(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",'columns'=>[
            'customer_id'=>"ALTER TABLE payments ADD COLUMN customer_id INT NULL AFTER id",
            'invoice_id'=>"ALTER TABLE payments ADD COLUMN invoice_id INT NULL AFTER customer_id",
            'type'=>"ALTER TABLE payments ADD COLUMN type VARCHAR(80) DEFAULT 'payment' AFTER customer_id",
            'method'=>"ALTER TABLE payments ADD COLUMN method VARCHAR(80) DEFAULT 'manual' AFTER type",
            'gateway'=>"ALTER TABLE payments ADD COLUMN gateway VARCHAR(80) DEFAULT 'manual' AFTER method",
            'amount'=>"ALTER TABLE payments ADD COLUMN amount DECIMAL(14,2) DEFAULT 0.00",
            'currency'=>"ALTER TABLE payments ADD COLUMN currency VARCHAR(10) DEFAULT 'TRY'",
            'transaction_id'=>"ALTER TABLE payments ADD COLUMN transaction_id VARCHAR(160) NULL",
            'status'=>"ALTER TABLE payments ADD COLUMN status VARCHAR(40) DEFAULT 'completed'",
            'notes'=>"ALTER TABLE payments ADD COLUMN notes TEXT NULL AFTER status",
            'created_at'=>"ALTER TABLE payments ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ]],
        ['category'=>'Payment','table'=>'credit_transactions','title'=>'Credit transactions balance snapshot','create'=>"CREATE TABLE IF NOT EXISTS credit_transactions (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NOT NULL, type VARCHAR(40) DEFAULT 'credit', amount DECIMAL(14,2) DEFAULT 0.00, balance_after DECIMAL(14,2) DEFAULT 0.00, currency VARCHAR(10) DEFAULT 'TRY', description VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY customer_id(customer_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",'columns'=>[
            'customer_id'=>"ALTER TABLE credit_transactions ADD COLUMN customer_id INT NULL AFTER id",
            'type'=>"ALTER TABLE credit_transactions ADD COLUMN type VARCHAR(40) DEFAULT 'credit' AFTER customer_id",
            'amount'=>"ALTER TABLE credit_transactions ADD COLUMN amount DECIMAL(14,2) DEFAULT 0.00 AFTER type",
            'balance_after'=>"ALTER TABLE credit_transactions ADD COLUMN balance_after DECIMAL(14,2) DEFAULT 0.00 AFTER amount",
            'currency'=>"ALTER TABLE credit_transactions ADD COLUMN currency VARCHAR(10) DEFAULT 'TRY' AFTER balance_after",
            'description'=>"ALTER TABLE credit_transactions ADD COLUMN description VARCHAR(255) NULL AFTER currency",
            'created_at'=>"ALTER TABLE credit_transactions ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ]],
        ['category'=>'Payment','table'=>'credit_topups','title'=>'Credit topups','create'=>"CREATE TABLE IF NOT EXISTS credit_topups (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NOT NULL, amount DECIMAL(12,2) NOT NULL, fee_amount DECIMAL(12,2) DEFAULT 0.00, total_amount DECIMAL(12,2) NOT NULL, currency VARCHAR(10) DEFAULT 'TRY', gateway VARCHAR(80) DEFAULT 'manual', status VARCHAR(40) DEFAULT 'pending', reference VARCHAR(80) NULL, invoice_id INT NULL, payment_id VARCHAR(160) NULL, notes TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, KEY customer_id(customer_id), KEY status(status), KEY gateway(gateway)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",'columns'=>[
            'customer_id'=>"ALTER TABLE credit_topups ADD COLUMN customer_id INT NULL AFTER id",
            'amount'=>"ALTER TABLE credit_topups ADD COLUMN amount DECIMAL(12,2) DEFAULT 0.00 AFTER customer_id",
            'fee_amount'=>"ALTER TABLE credit_topups ADD COLUMN fee_amount DECIMAL(12,2) DEFAULT 0.00 AFTER amount",
            'total_amount'=>"ALTER TABLE credit_topups ADD COLUMN total_amount DECIMAL(12,2) DEFAULT 0.00 AFTER fee_amount",
            'currency'=>"ALTER TABLE credit_topups ADD COLUMN currency VARCHAR(10) DEFAULT 'TRY' AFTER total_amount",
            'gateway'=>"ALTER TABLE credit_topups ADD COLUMN gateway VARCHAR(80) DEFAULT 'manual'",
            'status'=>"ALTER TABLE credit_topups ADD COLUMN status VARCHAR(40) DEFAULT 'pending'",
            'reference'=>"ALTER TABLE credit_topups ADD COLUMN reference VARCHAR(80) NULL",
            'invoice_id'=>"ALTER TABLE credit_topups ADD COLUMN invoice_id INT NULL",
            'payment_id'=>"ALTER TABLE credit_topups ADD COLUMN payment_id VARCHAR(160) NULL",
            'notes'=>"ALTER TABLE credit_topups ADD COLUMN notes TEXT NULL",
            'created_at'=>"ALTER TABLE credit_topups ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            'updated_at'=>"ALTER TABLE credit_topups ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP",
        ]],
        ['category'=>'Payment','table'=>'payment_gateway_transactions','title'=>'Gateway transactions','create'=>"CREATE TABLE IF NOT EXISTS payment_gateway_transactions (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NULL, invoice_id INT NULL, topup_id INT NULL, gateway VARCHAR(80) NOT NULL, gateway_order_id VARCHAR(120) NULL, gateway_transaction_id VARCHAR(160) NULL, amount DECIMAL(12,2) DEFAULT 0.00, fee_amount DECIMAL(12,2) DEFAULT 0.00, currency VARCHAR(10) DEFAULT 'TRY', status VARCHAR(40) DEFAULT 'pending', request_payload LONGTEXT NULL, response_payload LONGTEXT NULL, callback_payload LONGTEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, KEY gateway(gateway), KEY status(status), KEY customer_id(customer_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",'columns'=>[
            'customer_id'=>"ALTER TABLE payment_gateway_transactions ADD COLUMN customer_id INT NULL AFTER id",
            'invoice_id'=>"ALTER TABLE payment_gateway_transactions ADD COLUMN invoice_id INT NULL AFTER customer_id",
            'topup_id'=>"ALTER TABLE payment_gateway_transactions ADD COLUMN topup_id INT NULL AFTER invoice_id",
            'gateway'=>"ALTER TABLE payment_gateway_transactions ADD COLUMN gateway VARCHAR(80) DEFAULT 'manual' AFTER topup_id",
            'gateway_order_id'=>"ALTER TABLE payment_gateway_transactions ADD COLUMN gateway_order_id VARCHAR(120) NULL AFTER gateway",
            'gateway_transaction_id'=>"ALTER TABLE payment_gateway_transactions ADD COLUMN gateway_transaction_id VARCHAR(160) NULL AFTER gateway_order_id",
            'amount'=>"ALTER TABLE payment_gateway_transactions ADD COLUMN amount DECIMAL(12,2) DEFAULT 0.00 AFTER gateway_transaction_id",
            'fee_amount'=>"ALTER TABLE payment_gateway_transactions ADD COLUMN fee_amount DECIMAL(12,2) DEFAULT 0.00 AFTER amount",
            'currency'=>"ALTER TABLE payment_gateway_transactions ADD COLUMN currency VARCHAR(10) DEFAULT 'TRY' AFTER fee_amount",
            'status'=>"ALTER TABLE payment_gateway_transactions ADD COLUMN status VARCHAR(40) DEFAULT 'pending' AFTER currency",
            'request_payload'=>"ALTER TABLE payment_gateway_transactions ADD COLUMN request_payload LONGTEXT NULL",
            'response_payload'=>"ALTER TABLE payment_gateway_transactions ADD COLUMN response_payload LONGTEXT NULL",
            'callback_payload'=>"ALTER TABLE payment_gateway_transactions ADD COLUMN callback_payload LONGTEXT NULL",
            'created_at'=>"ALTER TABLE payment_gateway_transactions ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            'updated_at'=>"ALTER TABLE payment_gateway_transactions ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP",
        ]],
        ['category'=>'Payment','table'=>'shopier_settings','title'=>'Shopier settings','create'=>"CREATE TABLE IF NOT EXISTS shopier_settings (id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(120) NOT NULL UNIQUE, setting_value TEXT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",'columns'=>[]],
        ['category'=>'Payment','table'=>'module_shopier_callbacks','title'=>'Shopier callback log','create'=>"CREATE TABLE IF NOT EXISTS module_shopier_callbacks (id BIGINT AUTO_INCREMENT PRIMARY KEY, platform_order_id VARCHAR(190) NULL, status VARCHAR(30) DEFAULT 'received', amount DECIMAL(14,2) DEFAULT 0, payload_json LONGTEXT NULL, processed_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY platform_order_id(platform_order_id), KEY status(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",'columns'=>[
            'platform_order_id'=>"ALTER TABLE module_shopier_callbacks ADD COLUMN platform_order_id VARCHAR(190) NULL AFTER id",
            'status'=>"ALTER TABLE module_shopier_callbacks ADD COLUMN status VARCHAR(30) DEFAULT 'received' AFTER platform_order_id",
            'amount'=>"ALTER TABLE module_shopier_callbacks ADD COLUMN amount DECIMAL(14,2) DEFAULT 0 AFTER status",
            'payload_json'=>"ALTER TABLE module_shopier_callbacks ADD COLUMN payload_json LONGTEXT NULL",
            'processed_at'=>"ALTER TABLE module_shopier_callbacks ADD COLUMN processed_at DATETIME NULL",
            'created_at'=>"ALTER TABLE module_shopier_callbacks ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ]],
        ['category'=>'Customers','table'=>'customers','title'=>'Customer credit fields','create'=>"CREATE TABLE IF NOT EXISTS customers (id INT AUTO_INCREMENT PRIMARY KEY, first_name VARCHAR(120) NULL, last_name VARCHAR(120) NULL, email VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NULL, status VARCHAR(40) DEFAULT 'active', credit_balance DECIMAL(14,2) DEFAULT 0.00, balance DECIMAL(14,2) DEFAULT 0.00, currency VARCHAR(10) DEFAULT 'TRY', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",'columns'=>[
            'credit_balance'=>"ALTER TABLE customers ADD COLUMN credit_balance DECIMAL(14,2) DEFAULT 0.00",
            'balance'=>"ALTER TABLE customers ADD COLUMN balance DECIMAL(14,2) DEFAULT 0.00",
            'currency'=>"ALTER TABLE customers ADD COLUMN currency VARCHAR(10) DEFAULT 'TRY'",
        ]],
        ['category'=>'Domain','table'=>'domains','title'=>'Domain sync columns','create'=>"CREATE TABLE IF NOT EXISTS domains (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NULL, domain_name VARCHAR(190) NOT NULL, status VARCHAR(40) DEFAULT 'active', registration_date DATE NULL, expiry_date DATE NULL, next_due_date DATE NULL, auto_renew TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY customer_id(customer_id), KEY domain_name(domain_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",'columns'=>[
            'last_synced_at'=>"ALTER TABLE domains ADD COLUMN last_synced_at DATETIME NULL",
            'last_sync_status'=>"ALTER TABLE domains ADD COLUMN last_sync_status VARCHAR(40) NULL",
            'last_sync_message'=>"ALTER TABLE domains ADD COLUMN last_sync_message TEXT NULL",
        ]],
        ['category'=>'Builder','table'=>'builder_pro_layouts','title'=>'Builder Pro layouts','create'=>"CREATE TABLE IF NOT EXISTS builder_pro_layouts (id INT AUTO_INCREMENT PRIMARY KEY, target VARCHAR(32) NOT NULL, template_key VARCHAR(80) NOT NULL, title VARCHAR(190) NULL, layout_json LONGTEXT NULL, device_json LONGTEXT NULL, global_tokens_json LONGTEXT NULL, status VARCHAR(32) DEFAULT 'draft', created_by INT NULL, updated_by INT NULL, device VARCHAR(20) DEFAULT 'desktop', is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_builder_target_template(target, template_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",'columns'=>[
            'device_json'=>"ALTER TABLE builder_pro_layouts ADD COLUMN device_json LONGTEXT NULL",
            'global_tokens_json'=>"ALTER TABLE builder_pro_layouts ADD COLUMN global_tokens_json LONGTEXT NULL",
            'device'=>"ALTER TABLE builder_pro_layouts ADD COLUMN device VARCHAR(20) DEFAULT 'desktop'",
            'is_active'=>"ALTER TABLE builder_pro_layouts ADD COLUMN is_active TINYINT(1) DEFAULT 1",
        ]],
        ['category'=>'Builder','table'=>'builder_pro_revisions','title'=>'Builder Pro revisions','create'=>"CREATE TABLE IF NOT EXISTS builder_pro_revisions (id INT AUTO_INCREMENT PRIMARY KEY, layout_id INT NULL, target VARCHAR(32) NOT NULL, template_key VARCHAR(80) NOT NULL, layout_json LONGTEXT NULL, revision_note VARCHAR(190) NULL, created_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_bp_rev(layout_id), KEY idx_bp_target(target,template_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",'columns'=>[
            'revision_note'=>"ALTER TABLE builder_pro_revisions ADD COLUMN revision_note VARCHAR(190) NULL",
        ]],
        ['category'=>'Builder','table'=>'builder_pro_global_tokens','title'=>'Builder global tokens','create'=>"CREATE TABLE IF NOT EXISTS builder_pro_global_tokens (id INT AUTO_INCREMENT PRIMARY KEY, token_key VARCHAR(120) NOT NULL UNIQUE, token_value TEXT NULL, token_type VARCHAR(40) DEFAULT 'text', scope VARCHAR(40) DEFAULT 'global', updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",'columns'=>[]],
        ['category'=>'Builder','table'=>'builder_pro_components','title'=>'Builder global components','create'=>"CREATE TABLE IF NOT EXISTS builder_pro_components (id INT AUTO_INCREMENT PRIMARY KEY, component_key VARCHAR(120) NOT NULL UNIQUE, title VARCHAR(190) NOT NULL, target VARCHAR(32) DEFAULT 'site', component_json LONGTEXT NULL, is_global TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",'columns'=>[]],
        ['category'=>'Support','table'=>'support_widget_events','title'=>'Support widget events','create'=>"CREATE TABLE IF NOT EXISTS support_widget_events (id INT AUTO_INCREMENT PRIMARY KEY, event_type VARCHAR(80) NOT NULL, name VARCHAR(190) NULL, email VARCHAR(190) NULL, phone VARCHAR(80) NULL, query_text TEXT NULL, response_text LONGTEXT NULL, source_url VARCHAR(255) NULL, ip_address VARCHAR(80) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY event_type(event_type), KEY email(email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",'columns'=>[]],
        ['category'=>'Support','table'=>'support_widget_settings','title'=>'Support widget settings','create'=>"CREATE TABLE IF NOT EXISTS support_widget_settings (id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(160) UNIQUE NOT NULL, setting_value LONGTEXT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",'columns'=>[]],
        ['category'=>'Modules','table'=>'modules','title'=>'Module registry','create'=>"CREATE TABLE IF NOT EXISTS modules (id INT AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(120) UNIQUE NOT NULL, name VARCHAR(190) NOT NULL, type VARCHAR(80) DEFAULT 'other', version VARCHAR(50) DEFAULT '1.0.0', description TEXT NULL, path VARCHAR(255) NULL, is_enabled TINYINT(1) DEFAULT 0, is_core TINYINT(1) DEFAULT 0, manifest_json LONGTEXT NULL, installed_version VARCHAR(50) NULL, needs_install TINYINT(1) DEFAULT 1, last_error TEXT NULL, installed_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",'columns'=>[]],
        ['category'=>'Modules','table'=>'module_settings','title'=>'Module settings','create'=>"CREATE TABLE IF NOT EXISTS module_settings (id INT AUTO_INCREMENT PRIMARY KEY, module_slug VARCHAR(120) NOT NULL, setting_key VARCHAR(120) NOT NULL, setting_value LONGTEXT NULL, is_secret TINYINT(1) DEFAULT 0, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_module_setting(module_slug,setting_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",'columns'=>[]],
    ];
}
function ao_database_upgrade_check($apply=false){
    $rows=[]; $applied=0; $errors=0;
    foreach(ao_database_upgrade_specs() as $spec){
        $table=$spec['table']; $exists=ao_db_upgrade_table_exists($table);
        if(!$exists){
            $status='missing'; $message='Table missing';
            if($apply){
                try{ db()->exec($spec['create']); $applied++; $exists=true; $status='fixed'; $message='Table created'; }
                catch(Throwable $e){ $errors++; $status='error'; $message=$e->getMessage(); }
            }
            $rows[]=['category'=>$spec['category'],'target'=>$table,'title'=>$spec['title'],'status'=>$status,'message'=>$message];
        } else {
            $rows[]=['category'=>$spec['category'],'target'=>$table,'title'=>$spec['title'],'status'=>'ok','message'=>'Table exists'];
        }
        if($exists){
            foreach(($spec['columns'] ?? []) as $column=>$sql){
                $has=ao_db_upgrade_column_exists($table,$column);
                if($has){ $rows[]=['category'=>$spec['category'],'target'=>$table.'.'.$column,'title'=>$spec['title'].' / '.$column,'status'=>'ok','message'=>'Column exists']; continue; }
                $status='missing'; $message='Column missing';
                if($apply){
                    try{ db()->exec($sql); $applied++; $status='fixed'; $message='Column added'; }
                    catch(Throwable $e){ $errors++; $status='error'; $message=$e->getMessage(); }
                }
                $rows[]=['category'=>$spec['category'],'target'=>$table.'.'.$column,'title'=>$spec['title'].' / '.$column,'status'=>$status,'message'=>$message];
            }
        }
    }
    if($apply){
        try{ db()->exec("INSERT INTO settings(setting_key,setting_value) VALUES('database_upgrade_wizard_last_run',NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        try{ db()->exec("INSERT INTO admin_search_index(title,route,category,keywords,is_active) VALUES('Database Upgrade Wizard','admin/database-upgrade','Sistem','database upgrade wizard schema tablo kolon shopier odeme builder domain destek eksik kolon onar',1) ON DUPLICATE KEY UPDATE keywords=VALUES(keywords),category=VALUES(category),is_active=1"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        try{ db()->exec("INSERT IGNORE INTO shopier_settings(setting_key,setting_value) VALUES ('auth_mode','pat'),('pat',''),('api_key',''),('api_secret',''),('website_index','1'),('test_mode','1'),('callback_secret',''),('commission_gateway','shopier')"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    }
    return ['rows'=>$rows,'applied'=>$applied,'errors'=>$errors];
}
function ao_v1000_export_database_sql() {
    $pdo=db(); $out="-- Ahost One v10.0.0 database backup\n-- Generated: ".date('c')."\nSET FOREIGN_KEY_CHECKS=0;\n";
    $tables=[]; try { foreach($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM) as $r) $tables[]=$r[0]; } catch(Throwable $e){ return "-- Backup error: ".$e->getMessage()."\n"; }
    foreach($tables as $table){
        try { $cr=$pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_ASSOC); $create=array_values($cr)[1]??''; $out.="\nDROP TABLE IF EXISTS `$table`;\n$create;\n"; } catch(Throwable $e){ $out.="-- Create table error $table: ".$e->getMessage()."\n"; continue; }
        try { $rows=$pdo->query('SELECT * FROM `'.$table.'`')->fetchAll(PDO::FETCH_ASSOC); foreach($rows as $row){ $cols=array_map(fn($c)=>'`'.str_replace('`','``',$c).'`', array_keys($row)); $vals=[]; foreach($row as $v){ $vals[] = $v===null ? 'NULL' : $pdo->quote((string)$v); } $out.='INSERT INTO `'.$table.'` ('.implode(',',$cols).') VALUES ('.implode(',',$vals).');' . "\n"; } } catch(Throwable $e){ $out.="-- Data export error $table: ".$e->getMessage()."\n"; }
    }
    $out.="SET FOREIGN_KEY_CHECKS=1;\n"; return $out;
}
function ao_v1000_backup_create($type='database') {
    ao_v1000_ensure_schema();
    $dir=__DIR__.'/storage/backups'; if(!is_dir($dir)) @mkdir($dir,0775,true);
    $stamp=date('Ymd-His'); $file=''; $notes='';
    if($type==='database') { $file=$dir.'/ahost-one-db-'.$stamp.'.sql'; file_put_contents($file, ao_v1000_export_database_sql()); $notes='Veritabanı SQL yedeği oluşturuldu.'; }
    elseif($type==='files' || $type==='full') {
        if(class_exists('ZipArchive')) {
            $file=$dir.'/ahost-one-'.$type.'-'.$stamp.'.zip'; $zip=new ZipArchive(); $zip->open($file, ZipArchive::CREATE|ZipArchive::OVERWRITE);
            if($type==='full') $zip->addFromString('database-backup.sql', ao_v1000_export_database_sql());
            foreach(['app','public','themes','uploads','config'] as $folder){ $base=__DIR__.'/'.$folder; if(!is_dir($base)) continue; $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)); foreach($it as $f){ if($f->isFile()) $zip->addFile($f->getPathname(), $folder.'/'.substr($f->getPathname(), strlen($base)+1)); } }
            $zip->close(); $notes='ZIP yedeği oluşturuldu.';
        } else { $file=$dir.'/ahost-one-'.$type.'-'.$stamp.'.txt'; file_put_contents($file, "ZipArchive yok. Dosya yedeği için PHP zip eklentisini etkinleştirin.\n"); $notes='ZipArchive eksik; manifest oluşturuldu.'; }
    } else { $file=$dir.'/ahost-one-backup-'.$stamp.'.txt'; file_put_contents($file,'Unknown backup type'); $notes='Bilinmeyen yedek tipi.'; }
    $size=is_file($file)?filesize($file):0;
    try{ db()->prepare('INSERT INTO backup_jobs(job_type,backup_type,file_path,file_size,status,notes,created_by) VALUES(?,?,?,?,?,?,?)')->execute(['manual',$type,$file,$size,'created',$notes,$_SESSION['admin_id']??null]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return $file;
}
function ao_btk_csv_expr($table,$alias,$column,$as,$fallback=''){
    if(function_exists('ao_db_upgrade_column_exists') && ao_db_upgrade_column_exists($table,$column)){
        return $alias.'.`'.$column.'` AS `'.$as.'`';
    }
    return db()->quote((string)$fallback).' AS `'.$as.'`';
}
function ao_btk_csv_coalesce_expr($table,$alias,$columns,$as,$fallback=''){
    $parts=[];
    foreach((array)$columns as $column){
        if(function_exists('ao_db_upgrade_column_exists') && ao_db_upgrade_column_exists($table,$column)){
            $parts[]='NULLIF('.$alias.'.`'.$column.'`,"")';
        }
    }
    if($parts) return 'COALESCE('.implode(',',$parts).','.db()->quote((string)$fallback).') AS `'.$as.'`';
    return db()->quote((string)$fallback).' AS `'.$as.'`';
}
function ao_btk_csv_download($filename,$headers,$rows){
    while(ob_get_level()>0){ @ob_end_clean(); }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Pragma: no-cache');
    header('Expires: 0');
    $out=fopen('php://output','w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out,$headers,';');
    foreach($rows as $row){
        $line=[];
        foreach($headers as $h){ $line[]=(string)($row[$h] ?? ''); }
        fputcsv($out,$line,';');
    }
    fclose($out);
    exit;
}
function ao_btk_domain_export_rows(){
    if(!function_exists('ao_db_upgrade_table_exists') || !ao_db_upgrade_table_exists('domains')) return [];
    $select=[
        ao_btk_csv_expr('domains','d','id','domain_id'),
        ao_btk_csv_expr('domains','d','domain_name','domain'),
        ao_btk_csv_expr('domains','d','status','domain_status'),
        ao_btk_csv_expr('domains','d','registrar','registrar'),
        ao_btk_csv_expr('domains','d','registration_date','registration_date'),
        ao_btk_csv_expr('domains','d','expiry_date','expiry_date'),
        ao_btk_csv_expr('domains','d','next_due_date','next_due_date'),
        ao_btk_csv_expr('domains','d','auto_renew','auto_renew'),
        ao_btk_csv_expr('domains','d','lock_status','lock_status'),
        ao_btk_csv_expr('domains','d','created_at','created_at'),
        ao_btk_csv_expr('customers','c','id','customer_id'),
        ao_btk_csv_expr('customers','c','first_name','first_name'),
        ao_btk_csv_expr('customers','c','last_name','last_name'),
        ao_btk_csv_expr('customers','c','company_name','company_name'),
        ao_btk_csv_expr('customers','c','email','email'),
        ao_btk_csv_expr('customers','c','phone','phone'),
        ao_btk_csv_expr('customers','c','tc_identity_no','tc_identity_no'),
        ao_btk_csv_expr('customers','c','tax_number','tax_number'),
        ao_btk_csv_expr('customers','c','address1','address1'),
        ao_btk_csv_expr('customers','c','city','city'),
        ao_btk_csv_expr('customers','c','country','country'),
    ];
    try{
        return db()->query('SELECT '.implode(',',$select).' FROM domains d LEFT JOIN customers c ON c.id=d.customer_id ORDER BY d.id DESC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); return []; }
}
function ao_btk_hosting_export_rows(){
    if(!function_exists('ao_db_upgrade_table_exists') || !ao_db_upgrade_table_exists('services')) return [];
    $hasHosting=ao_db_upgrade_table_exists('hosting_accounts');
    $select=[
        ao_btk_csv_expr('services','s','id','service_id'),
        ao_btk_csv_expr('services','s','domain','service_domain'),
        ao_btk_csv_expr('services','s','status','service_status'),
        ao_btk_csv_expr('services','s','billing_cycle','billing_cycle'),
        ao_btk_csv_expr('services','s','next_due_date','next_due_date'),
        ao_btk_csv_expr('services','s','auto_renew','auto_renew'),
        ao_btk_csv_expr('services','s','created_at','created_at'),
        ao_btk_csv_expr('products','p','name','product_name'),
        ao_btk_csv_expr('product_groups','pg','name','product_group'),
        ao_btk_csv_expr('customers','c','id','customer_id'),
        ao_btk_csv_expr('customers','c','first_name','first_name'),
        ao_btk_csv_expr('customers','c','last_name','last_name'),
        ao_btk_csv_expr('customers','c','company_name','company_name'),
        ao_btk_csv_expr('customers','c','email','email'),
        ao_btk_csv_expr('customers','c','phone','phone'),
        ao_btk_csv_expr('customers','c','tc_identity_no','tc_identity_no'),
        ao_btk_csv_expr('customers','c','tax_number','tax_number'),
        ao_btk_csv_expr('customers','c','address1','address1'),
        ao_btk_csv_expr('customers','c','city','city'),
        ao_btk_csv_expr('customers','c','country','country'),
    ];
    if($hasHosting){
        $select[] = ao_btk_csv_expr('hosting_accounts','h','server_name','server_name');
        $select[] = ao_btk_csv_expr('hosting_accounts','h','server_ip','server_ip');
        $select[] = ao_btk_csv_coalesce_expr('hosting_accounts','h',['panel_username','username','whm_username'],'panel_username');
        $select[] = ao_btk_csv_coalesce_expr('hosting_accounts','h',['package_name','package'],'hosting_package');
        $joinHosting=' LEFT JOIN hosting_accounts h ON h.service_id=s.id';
    } else {
        $select[] = db()->quote('')." AS `server_name`";
        $select[] = db()->quote('')." AS `server_ip`";
        $select[] = db()->quote('')." AS `panel_username`";
        $select[] = db()->quote('')." AS `hosting_package`";
        $joinHosting='';
    }
    try{
        return db()->query('SELECT '.implode(',',$select).' FROM services s LEFT JOIN customers c ON c.id=s.customer_id LEFT JOIN products p ON p.id=s.product_id LEFT JOIN product_groups pg ON pg.id=p.group_id'.$joinHosting.' ORDER BY s.id DESC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); return []; }
}
if ($route === 'admin/reports/btk-domain-csv') {
    require_admin();
    $headers=['domain_id','domain','domain_status','registrar','registration_date','expiry_date','next_due_date','auto_renew','lock_status','created_at','customer_id','first_name','last_name','company_name','email','phone','tc_identity_no','tax_number','address1','city','country'];
    ao_btk_csv_download('btk-domain-'.date('Ymd-His').'.csv',$headers,ao_btk_domain_export_rows());
}
if ($route === 'admin/reports/btk-hosting-csv') {
    require_admin();
    $headers=['service_id','service_domain','service_status','billing_cycle','next_due_date','auto_renew','created_at','product_name','product_group','customer_id','first_name','last_name','company_name','email','phone','tc_identity_no','tax_number','address1','city','country','server_name','server_ip','panel_username','hosting_package'];
    ao_btk_csv_download('btk-hosting-'.date('Ymd-His').'.csv',$headers,ao_btk_hosting_export_rows());
}
function ao_v1000_migrations() {
    ao_v1000_ensure_schema(); $dir=__DIR__.'/database/migrations'; $files=is_dir($dir)?glob($dir.'/*.sql'):[]; sort($files); $done=[]; try{ foreach(db()->query("SELECT migration_file,status FROM update_history")->fetchAll() as $r) $done[$r['migration_file']]=$r['status']; }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return array_map(fn($f)=>['file'=>basename($f),'path'=>$f,'status'=>$done[basename($f)]??'pending'], $files);
}
function ao_v1000_run_migration($file) {
    ao_v1000_ensure_schema(); $safe=basename($file); $path=__DIR__.'/database/migrations/'.$safe; if(!is_file($path)) return ['ok'=>false,'message'=>'Migration dosyası bulunamadı.'];
    try { $sql=file_get_contents($path); db()->exec($sql); db()->prepare("INSERT INTO update_history(version,migration_file,status,message,executed_at) VALUES(?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE status=VALUES(status), message=VALUES(message), executed_at=VALUES(executed_at)")->execute(['10.0.0',$safe,'success','Migration çalıştırıldı.']); return ['ok'=>true,'message'=>'Migration çalıştırıldı: '.$safe]; } catch(Throwable $e){ try{db()->prepare("INSERT INTO update_history(version,migration_file,status,message,executed_at) VALUES(?,?,?,?,NOW())")->execute(['10.0.0',$safe,'error',$e->getMessage()]);}catch(Throwable $x){} return ['ok'=>false,'message'=>$e->getMessage()]; }
}
function ao_v1000_notification_summary() {
    ao_v1000_ensure_schema(); $events=[]; try{$events=db()->query('SELECT * FROM notification_events ORDER BY id')->fetchAll();}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $templates=[]; try{$templates=db()->query('SELECT * FROM notification_templates ORDER BY id DESC LIMIT 20')->fetchAll();}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return ['events'=>$events,'templates'=>$templates];
}

// v7.6.2 Admin Forgot Password with security question
function ao_admin_answer_normalize($value) {
    $value = trim((string)$value);
    if (function_exists('mb_strtolower')) return mb_strtolower($value, 'UTF-8');
    return strtolower($value);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/forgot-password') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    try { $s=db()->prepare('SELECT * FROM admins WHERE email=? LIMIT 1'); $s->execute([$email]); $a=$s->fetch(); }
    catch(Throwable $e) { $a=null; }
    if ($a) {
        $_SESSION['admin_reset_id'] = (int)$a['id'];
        $_SESSION['admin_reset_email'] = $a['email'];
        unset($_SESSION['admin_reset_verified']);
        redirect_to('admin/security-question');
    }
    // E-posta enumerate edilmesin diye genel mesaj veriyoruz.
    flash('error','Bu e-posta için admin hesabı bulunamadı.');
    redirect_to('admin/forgot-password');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/security-question') {
    verify_csrf();
    $id = (int)($_SESSION['admin_reset_id'] ?? 0);
    $answer = ao_admin_answer_normalize($_POST['security_answer'] ?? '');
    try { $s=db()->prepare('SELECT * FROM admins WHERE id=? LIMIT 1'); $s->execute([$id]); $a=$s->fetch(); }
    catch(Throwable $e) { $a=null; }
    $hash = $a['security_answer_hash'] ?? '';
    if ($a && $hash && password_verify($answer, $hash)) {
        $_SESSION['admin_reset_verified'] = 1;
        redirect_to('admin/reset-password');
    }
    flash('error','Güvenlik sorusu cevabı hatalı.');
    redirect_to('admin/security-question');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/reset-password') {
    verify_csrf();
    $id = (int)($_SESSION['admin_reset_id'] ?? 0);
    if (empty($_SESSION['admin_reset_verified']) || $id <= 0) redirect_to('admin/forgot-password');
    $p1 = (string)($_POST['password'] ?? '');
    $p2 = (string)($_POST['password_confirm'] ?? '');
    if (strlen($p1) < 8) { flash('error','Yeni şifre en az 8 karakter olmalı.'); redirect_to('admin/reset-password'); }
    if ($p1 !== $p2) { flash('error','Şifre tekrarı uyuşmuyor.'); redirect_to('admin/reset-password'); }
    try {
        db()->prepare('UPDATE admins SET password_hash=? WHERE id=?')->execute([password_hash($p1, PASSWORD_DEFAULT), $id]);
        unset($_SESSION['admin_reset_id'], $_SESSION['admin_reset_email'], $_SESSION['admin_reset_verified']);
        flash('success','Admin şifresi güncellendi. Yeni şifrenizle giriş yapabilirsiniz.');
        redirect_to('admin/login');
    } catch(Throwable $e) { flash('error','Şifre güncellenemedi: '.$e->getMessage()); redirect_to('admin/reset-password'); }
}
if (!function_exists('ao_language_codes_v2700')) {
    function ao_language_codes_v2700($raw): array {
        $codes = [];
        foreach (explode(',', (string)$raw) as $code) {
            $code = strtolower(preg_replace('~[^a-z_-]~i', '', trim($code)));
            if ($code !== '' && !in_array($code, $codes, true)) $codes[] = $code;
        }
        return $codes ?: ['tr'];
    }
}
if (!function_exists('ao_generate_language_pack_v2700')) {
    function ao_generate_language_pack_v2700(string $lang): array {
        $lang = strtolower(preg_replace('~[^a-z_-]~i', '', $lang));
        if ($lang === '' || $lang === 'tr' || !function_exists('ao_sync_language_file')) return ['created'=>false,'translated'=>0,'fallback'=>0];
        $path = ao_lang_file_path($lang);
        if (is_file($path)) return ['created'=>false,'translated'=>0,'fallback'=>0];
        $source = ao_load_lang('tr');
        if (!$source) return ['created'=>false,'translated'=>0,'fallback'=>0];
        $translations = [];
        if ((string)admin_setting('language_ai_auto_translate_enabled', '1') === '1' && function_exists('ao_ai_call_optional')) {
            foreach (array_chunk($source, 30, true) as $chunk) {
                $payload = json_encode($chunk, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $prompt = "Translate the JSON values from Turkish into language code {$lang}. Preserve every JSON key exactly. Keep brand names, URLs, HTML placeholders and variables unchanged. Return only a valid JSON object, with no markdown.\n\n{$payload}";
                $answer = ao_ai_call_optional($prompt);
                if (!is_string($answer) || trim($answer) === '') continue;
                if (preg_match('/\{.*\}/s', $answer, $match)) $answer = $match[0];
                $decoded = json_decode(trim($answer), true);
                if (!is_array($decoded)) continue;
                foreach ($chunk as $key => $value) {
                    if (isset($decoded[$key]) && is_scalar($decoded[$key]) && trim((string)$decoded[$key]) !== '') {
                        $translations[$key] = (string)$decoded[$key];
                    }
                }
            }
        }
        $pack = $source;
        foreach ($translations as $key => $value) $pack[$key] = $value;
        $created = ao_sync_language_file($lang, $pack);
        return ['created'=>$created,'translated'=>count($translations),'fallback'=>max(0, count($source) - count($translations))];
    }
}
if (!function_exists('ao_generate_new_language_packs_v2700')) {
    function ao_generate_new_language_packs_v2700($previous, $current): array {
        $existing = ao_language_codes_v2700($previous);
        $added = array_values(array_diff(ao_language_codes_v2700($current), $existing));
        $result = ['created'=>0,'translated'=>0,'fallback'=>0,'languages'=>[]];
        foreach ($added as $lang) {
            $one = ao_generate_language_pack_v2700($lang);
            if (!$one['created']) continue;
            $result['created']++;
            $result['translated'] += $one['translated'];
            $result['fallback'] += $one['fallback'];
            $result['languages'][] = $lang;
        }
        return $result;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/site-builder/popup-save') {
    require_admin(); verify_csrf();
    $popupFields = [
        'site_campaign_popup_enabled','site_campaign_popup_title','site_campaign_popup_button',
        'site_campaign_popup_body','site_campaign_popup_url','site_campaign_popup_image',
        'site_campaign_popup_start','site_campaign_popup_end','site_campaign_popup_cooldown_hours',
    ];
    foreach ($popupFields as $popupField) {
        $popupValue = trim((string)($_POST[$popupField] ?? ''));
        if (in_array($popupField, ['site_campaign_popup_start','site_campaign_popup_end'], true)) {
            $popupValue = $popupValue === '' ? '' : str_replace('T', ' ', $popupValue).':00';
        }
        if ($popupField === 'site_campaign_popup_cooldown_hours') $popupValue = (string)max(0, min(720, (int)$popupValue));
        save_setting($popupField, $popupValue);
    }
    flash('success', 'Kampanya popup ayarları kaydedildi.');
    redirect_to('admin/site-builder/popups');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($route === 'admin/settings/save' || $route === 'admin/settings/save-section')) {
    require_admin(); verify_csrf();
    try { db()->exec("CREATE TABLE IF NOT EXISTS settings (id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(160) NOT NULL UNIQUE, setting_value LONGTEXT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $previousLanguages = (string)admin_setting('enabled_languages', 'tr,en');
    $requestedLanguages = isset($_POST['settings']['enabled_languages']) ? (string)$_POST['settings']['enabled_languages'] : $previousLanguages;
    $savedCount = 0;
    foreach (($_POST['settings'] ?? []) as $k=>$v) {
        $key = preg_replace('/[^a-zA-Z0-9_\-]/','', (string)$k);
        if ($key !== '') { if (save_setting($key, is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : trim((string)$v))) $savedCount++; }
    }
    if ($route === 'admin/settings/save') {
        foreach (['production_mode','demo_data_enabled','force_https','company_name','company_email','company_phone','invoice_prefix','order_prefix'] as $k) {
            if (isset($_POST[$k])) { if (save_setting($k, trim((string)$_POST[$k]))) $savedCount++; }
        }
    }
    $section = preg_replace('/[^a-z0-9_\-]/','', (string)($_POST['section'] ?? ''));
    if ($section === 'inline') $section = '';
    if (!empty($_POST['settings']['global_active_theme_slug'])) {
        $slug = preg_replace('/[^a-z0-9_\-]/i','', (string)$_POST['settings']['global_active_theme_slug']);
        if ($slug !== '') {
            try { ao_schema_ensure_v930(); foreach(['site','admin','client'] as $area){ $q=db()->prepare('SELECT id FROM themes WHERE slug=? AND area=? LIMIT 1'); $q->execute([$slug,$area]); $tid=(int)$q->fetchColumn(); if($tid>0){ db()->prepare('UPDATE themes SET is_active=0 WHERE area=?')->execute([$area]); db()->prepare('UPDATE themes SET is_active=1 WHERE id=?')->execute([$tid]); } } if(function_exists('ao_clear_theme_cache')) ao_clear_theme_cache(); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        }
    }
    $languageResult = ao_generate_new_language_packs_v2700($previousLanguages, $requestedLanguages);
    if ($savedCount > 0 || empty($_POST['settings'])) {
        $message = 'Ayarlar kaydedildi.';
        if ($languageResult['created'] > 0) {
            $message .= ' '.implode(', ', $languageResult['languages']).' dil paketi oluşturuldu';
            $message .= $languageResult['translated'] > 0 ? ' ve AI ile çevrildi.' : '; AI yanıt vermediği için Türkçe güvenli kaynak kullanıldı.';
        }
        flash('success', $message);
    } else flash('error','Ayarlar kaydedilemedi. Veritabanı/settings tablosunu kontrol edin.');
    redirect_to($section ? ('admin/settings/'.$section) : 'admin/settings');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/smtp-test') { require_admin(); verify_csrf(); flash('success','SMTP test isteği alındı. Gerçek gönderim için SMTP bilgilerini kaydedip Bildirim Merkezi testini kullanın.'); redirect_to($_POST['return'] ?? 'admin/setup-wizard'); }

function ao_table_columns_v2334($table){
    static $cache=[]; if(isset($cache[$table])) return $cache[$table];
    $out=[]; try{ $q=db()->query('SHOW COLUMNS FROM `'.str_replace('`','',$table).'`'); foreach($q->fetchAll() as $r){ $out[$r['Field']]=$r; } }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return $cache[$table]=$out;
}
function ao_v2334_ensure_product_group_schema(){ static $done=false; if($done) return; $done=true;
    try{ db()->exec("CREATE TABLE IF NOT EXISTS product_groups (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160) NOT NULL, slug VARCHAR(190) NOT NULL UNIQUE, type VARCHAR(80) DEFAULT 'service', description TEXT NULL, sort_order INT DEFAULT 0, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $cols=ao_table_columns_v2334('product_groups');
    try{ if(!isset($cols['description'])) db()->exec("ALTER TABLE product_groups ADD COLUMN description TEXT NULL AFTER slug"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ if(!isset($cols['type'])) db()->exec("ALTER TABLE product_groups ADD COLUMN type VARCHAR(80) DEFAULT 'service' AFTER slug"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ if(!isset($cols['sort_order'])) db()->exec("ALTER TABLE product_groups ADD COLUMN sort_order INT DEFAULT 0 AFTER type"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ if(!isset($cols['is_active'])) db()->exec("ALTER TABLE product_groups ADD COLUMN is_active TINYINT(1) DEFAULT 1"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_v2334_seed_product_groups(){
    ao_v2334_ensure_product_group_schema();
    $defaults=[
        ['Hosting','hosting','Web hosting paketleri','hosting',10],
        ['Reseller Hosting','reseller-hosting','Bayi hosting ve reseller paketleri','reseller',15],
        ['VPS / Sunucu','vps-sunucu','VPS, dedicated ve yönetilebilir sunucular','server',20],
        ['Domain','domain','Domain kayıt, transfer ve DNS ürünleri','domain',30],
        ['SSL','ssl','SSL sertifikaları ve güvenlik ürünleri','ssl',40],
        ['Site Builder','sitebuilder','Hazır site, şablon ve builder paketleri','sitebuilder',50],
        ['Mobile Builder','mobilebuilder','Mobil uygulama builder paketleri','mobilebuilder',60],
        ['Web Tasarım','web-tasarim','Kurumsal site, e-ticaret ve özel tasarım','web',70],
        ['Web Scriptleri','web-scriptleri','Hazır PHP script, web yazılımı, kaynak kod ve kurulum paketleri','webscript',75],
        ['Mobil Uygulama','mobil-uygulama','Android/iOS proje hizmetleri','mobile',80],
        ['Android Uygulamaları','android-uygulamalari','Android APK/AAB uygulama, kaynak kod ve yayınlama paketleri','android',82],
        ['SEO','seo','SEO ve dijital pazarlama paketleri','seo',90],
        ['Radyo Hosting','radyo-hosting','Online radyo hosting, yayın paneli, AutoDJ ve radyo lisans paketleri','radio',92],
        ['Lisans Ürünleri','lisans-urunleri','Yazılım, radyo ve özel lisans ürünleri','license',95],
        ['Dijital Hizmetler','dijital-hizmetler','Ahost tarafından sunulan dijital hizmetler','digital',100],
        ['Marketplace','marketplace','Tema, script, domain ve dijital ürün ilan altyapısı','marketplace',110],
    ];
    $cols=ao_table_columns_v2334('product_groups');
    foreach($defaults as $d){
        $payload=['name'=>$d[0],'slug'=>$d[1],'is_active'=>1];
        if(isset($cols['description'])) $payload['description']=$d[2];
        if(isset($cols['type'])) $payload['type']=$d[3];
        if(isset($cols['sort_order'])) $payload['sort_order']=$d[4];
        $fields=array_keys($payload); $ph=implode(',',array_fill(0,count($fields),'?')); $upd=implode(',',array_map(fn($c)=>$c.'=VALUES('.$c.')',array_filter($fields,fn($c)=>$c!=='slug')));
        try{ db()->prepare('INSERT INTO product_groups('.implode(',',$fields).') VALUES('.$ph.') ON DUPLICATE KEY UPDATE '.$upd)->execute(array_values($payload)); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/product-center/group-save') {
    require_admin(); verify_csrf();
    $id=(int)($_POST['id']??0);
    $name=trim($_POST['name']??'');
    $slug=trim($_POST['slug']??'');
    $desc=trim($_POST['description']??'');
    $type=trim($_POST['type']??'service');
    $isActive=isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
    if($slug==='') $slug = preg_replace('/[^a-z0-9]+/','-', strtolower($name));
    $slug = trim(preg_replace('/[^a-z0-9\-]+/','-', strtolower($slug)),'-');
    try{
        if(!$name || !$slug) throw new Exception('Grup adı ve slug zorunlu.');
        ao_v2334_ensure_product_group_schema();
        $cols = ao_table_columns_v2334('product_groups');
        $payload = ['name'=>$name,'slug'=>$slug,'is_active'=>$isActive?1:0];
        if(isset($cols['description'])) $payload['description']=$desc;
        if(isset($cols['type'])) $payload['type']=$type;
        if(isset($cols['sort_order'])) $payload['sort_order']=(int)($_POST['sort_order']??0);
        if($id>0){
            $sets=[]; $vals=[];
            foreach($payload as $field=>$value){ $sets[]='`'.$field.'`=?'; $vals[]=$value; }
            $vals[]=$id;
            db()->prepare('UPDATE product_groups SET '.implode(',',$sets).' WHERE id=?')->execute($vals);
            flash('success','Ürün grubu güncellendi.');
        } else {
            $fields = array_keys($payload);
            $placeholders = implode(',', array_fill(0,count($fields),'?'));
            $updates = implode(',', array_map(fn($c)=>'`'.$c.'`=VALUES(`'.$c.'`)', array_filter($fields, fn($c)=>!in_array($c,['slug'],true))));
            db()->prepare('INSERT INTO product_groups(`'.implode('`,`',$fields).'`) VALUES('.$placeholders.') ON DUPLICATE KEY UPDATE '.$updates)->execute(array_values($payload));
            flash('success','Ürün grubu kaydedildi.');
        }
    }catch(Throwable $e){
        flash('error','Ürün grubu kaydedilemedi: '.$e->getMessage());
    }
    redirect_to('admin/product-center/groups');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/product-center/groups/seed-defaults') {
    require_admin(); verify_csrf();
    ao_v2334_seed_product_groups();
    flash('success','Varsayılan ürün grupları oluşturuldu/güncellendi.');
    redirect_to('admin/product-center/groups');
}

if ($route === 'admin/product-center/group-toggle') {
    require_admin(); verify_csrf();
    $id=(int)($_GET['id']??0);
    try{
        ao_v2334_ensure_product_group_schema();
        $q=db()->prepare('SELECT is_active FROM product_groups WHERE id=?'); $q->execute([$id]);
        $cur=(int)$q->fetchColumn();
        db()->prepare('UPDATE product_groups SET is_active=? WHERE id=?')->execute([$cur?0:1,$id]);
        flash('success',$cur?'Ürün grubu pasife alındı.':'Ürün grubu aktifleştirildi.');
    }catch(Throwable $e){ flash('error','Ürün grubu durumu değiştirilemedi: '.$e->getMessage()); }
    redirect_to('admin/product-center/groups');
}

if ($route === 'admin/product-center/group-delete') {
    require_admin(); verify_csrf();
    $id=(int)($_GET['id']??0);
    try{
        ao_v2334_ensure_product_group_schema();
        $q=db()->prepare('SELECT COUNT(*) FROM products WHERE group_id=?'); $q->execute([$id]);
        if((int)$q->fetchColumn()>0) throw new Exception('Bu gruba bağlı ürün var. Önce ürünleri başka gruba taşıyın veya silin.');
        db()->prepare('DELETE FROM product_groups WHERE id=?')->execute([$id]);
        flash('success','Ürün grubu silindi.');
    }catch(Throwable $e){ flash('error','Ürün grubu silinemedi: '.$e->getMessage()); }
    redirect_to('admin/product-center/groups');
}


function ao_v237_ensure_product_pricing_schema(){ static $done=false; if($done) return; $done=true;
    try{
        db()->exec("CREATE TABLE IF NOT EXISTS product_pricing (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            cycle VARCHAR(40) DEFAULT 'monthly',
            price DECIMAL(14,2) DEFAULT 0.00,
            setup_fee DECIMAL(14,2) DEFAULT 0.00,
            currency VARCHAR(10) DEFAULT 'TRY',
            UNIQUE KEY uniq_product_cycle (product_id,cycle)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $cols = [
        'price_usd' => 'DECIMAL(14,2) DEFAULT 0.00',
        'price_try' => 'DECIMAL(14,2) DEFAULT 0.00',
        'setup_fee_usd' => 'DECIMAL(14,2) DEFAULT 0.00',
        'setup_fee_try' => 'DECIMAL(14,2) DEFAULT 0.00',
        'base_currency' => "VARCHAR(10) DEFAULT 'USD'",
        'exchange_rate' => 'DECIMAL(16,6) DEFAULT 0.000000',
        'margin_percent' => 'DECIMAL(8,2) DEFAULT 0.00',
        'auto_convert' => 'TINYINT(1) DEFAULT 1',
        'is_active' => 'TINYINT(1) DEFAULT 0',
        'source_type' => 'VARCHAR(40) NULL',
        'external_id' => 'VARCHAR(80) NULL',
        'updated_at' => 'TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP'
    ];

    try{
        $existing = array_column(db()->query('SHOW COLUMNS FROM product_pricing')->fetchAll(PDO::FETCH_ASSOC),'Field');
        foreach($cols as $c=>$def){ if(!in_array($c,$existing,true)){ try{ db()->exec("ALTER TABLE product_pricing ADD COLUMN `$c` $def"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } } }
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{
        $pcols = array_column(db()->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_ASSOC),'Field');
        foreach(['source_type'=>'VARCHAR(40) NULL','external_id'=>'VARCHAR(80) NULL','source_id'=>'VARCHAR(80) NULL','currency_code'=>"VARCHAR(10) NULL"] as $c=>$def){
            if(!in_array($c,$pcols,true)){ try{ db()->exec("ALTER TABLE products ADD COLUMN `$c` $def"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
        }
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_v249_ensure_product_checkout_addons_schema(){ static $done=false; if($done) return; $done=true;
    try{
        db()->exec("CREATE TABLE IF NOT EXISTS product_addon_catalog (
            id INT AUTO_INCREMENT PRIMARY KEY,
            addon_key VARCHAR(80) NOT NULL UNIQUE,
            name VARCHAR(190) NOT NULL,
            description TEXT NULL,
            price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(10) NOT NULL DEFAULT 'TRY',
            provision_key VARCHAR(80) NULL,
            provision_value VARCHAR(190) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_product_addon_catalog_active (is_active),
            KEY idx_product_addon_catalog_sort (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        db()->exec("CREATE TABLE IF NOT EXISTS product_checkout_addons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            catalog_id INT NULL,
            addon_key VARCHAR(80) NOT NULL,
            name VARCHAR(190) NOT NULL,
            description TEXT NULL,
            price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(10) NOT NULL DEFAULT 'TRY',
            provision_key VARCHAR(80) NULL,
            provision_value VARCHAR(190) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_product_addon_key (product_id, addon_key),
            KEY idx_product_checkout_addons_product (product_id),
            KEY idx_product_checkout_addons_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        $cols = array_column(db()->query('SHOW COLUMNS FROM product_checkout_addons')->fetchAll(PDO::FETCH_ASSOC), 'Field');
        foreach ([
            'catalog_id' => 'INT NULL AFTER product_id',
            'provision_key' => 'VARCHAR(80) NULL AFTER currency',
            'provision_value' => 'VARCHAR(190) NULL AFTER provision_key',
        ] as $col => $def) {
            if (!in_array($col, $cols, true)) {
                try { db()->exec("ALTER TABLE product_checkout_addons ADD COLUMN `$col` $def"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
            }
        }
        try {
            db()->exec("INSERT INTO product_addon_catalog(addon_key,name,description,price,currency,provision_key,provision_value,sort_order,is_active) VALUES
                ('disk-1gb','1 GB Disk Alanı','Hosting paketine 1 GB ek disk alanı ekler.',0.00,'TRY','disk_gb','1',10,1),
                ('trafik-1gb','1 GB Trafik','Hosting paketine 1 GB ek trafik hakkı ekler.',0.00,'TRY','traffic_gb','1',20,1),
                ('eposta-10','10 E-posta Hesabı','Hosting paketine 10 ek e-posta hesabı tanımlar.',0.00,'TRY','email_accounts','10',30,1),
                ('site-tasima-kurulum','Site Taşıma ve Kurulum','Mevcut sitenizin hostinge taşınması ve temel kurulum hizmeti.',0.00,'TRY','service_task','site_migration_setup',40,1)
            ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), provision_key=VALUES(provision_key), provision_value=VALUES(provision_value), is_active=VALUES(is_active)");
        } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_v249_addon_catalog_rows($activeOnly=false){
    ao_v249_ensure_product_checkout_addons_schema();
    try{
        $sql = 'SELECT * FROM product_addon_catalog';
        if($activeOnly) $sql .= ' WHERE is_active=1';
        $sql .= ' ORDER BY sort_order,id';
        return db()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }catch(Throwable $e){ return []; }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/product-center/addon-save') {
    require_admin(); verify_csrf(); ao_v249_ensure_product_checkout_addons_schema();
    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $key = trim((string)($_POST['addon_key'] ?? ''));
    if($key === '') $key = $name;
    $key = trim(preg_replace('~[^a-z0-9_-]+~', '-', strtolower($key)), '-');
    $currency = strtoupper(trim((string)($_POST['currency'] ?? 'TRY')));
    if($currency === '' || $currency === 'TL') $currency = 'TRY';
    $price = function_exists('ao_v237_parse_money') ? ao_v237_parse_money($_POST['price'] ?? 0) : round((float)str_replace(',','.',(string)($_POST['price'] ?? 0)),2);
    $provisionKey = trim(preg_replace('~[^a-z0-9_:-]+~', '', strtolower((string)($_POST['provision_key'] ?? ''))));
    $provisionValue = trim((string)($_POST['provision_value'] ?? ''));
    try{
        if($name === '' || $key === '') throw new Exception('Ek paket adı zorunlu.');
        if($id > 0){
            db()->prepare('UPDATE product_addon_catalog SET addon_key=?,name=?,description=?,price=?,currency=?,provision_key=?,provision_value=?,is_active=?,sort_order=? WHERE id=?')
                ->execute([$key,$name,trim((string)($_POST['description'] ?? '')),$price,$currency,$provisionKey,$provisionValue,!empty($_POST['is_active'])?1:0,(int)($_POST['sort_order'] ?? 0),$id]);
        } else {
            db()->prepare('INSERT INTO product_addon_catalog(addon_key,name,description,price,currency,provision_key,provision_value,is_active,sort_order) VALUES(?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description),price=VALUES(price),currency=VALUES(currency),provision_key=VALUES(provision_key),provision_value=VALUES(provision_value),is_active=VALUES(is_active),sort_order=VALUES(sort_order)')
                ->execute([$key,$name,trim((string)($_POST['description'] ?? '')),$price,$currency,$provisionKey,$provisionValue,!empty($_POST['is_active'])?1:0,(int)($_POST['sort_order'] ?? 0)]);
        }
        flash('success','Ek paket tanımı kaydedildi.');
    }catch(Throwable $e){ flash('error','Ek paket kaydedilemedi: '.$e->getMessage()); }
    redirect_to('admin/product-center/addons');
}
if ($route === 'admin/product-center/addon-toggle') {
    require_admin(); verify_csrf(); ao_v249_ensure_product_checkout_addons_schema();
    $id = (int)($_GET['id'] ?? 0);
    try{
        $q=db()->prepare('SELECT is_active FROM product_addon_catalog WHERE id=? LIMIT 1'); $q->execute([$id]);
        $cur=(int)$q->fetchColumn();
        db()->prepare('UPDATE product_addon_catalog SET is_active=? WHERE id=?')->execute([$cur?0:1,$id]);
        flash('success','Ek paket durumu değiştirildi.');
    }catch(Throwable $e){ flash('error','İşlem başarısız.'); }
    redirect_to('admin/product-center/addons');
}
if ($route === 'admin/product-center/addon-delete') {
    require_admin(); verify_csrf(); ao_v249_ensure_product_checkout_addons_schema();
    $id = (int)($_GET['id'] ?? 0);
    try{
        db()->prepare('DELETE FROM product_addon_catalog WHERE id=?')->execute([$id]);
        db()->prepare('UPDATE product_checkout_addons SET catalog_id=NULL WHERE catalog_id=?')->execute([$id]);
        flash('success','Ek paket tanımı silindi. Ürünlere kopyalanmış serbest satırlar korunur.');
    }catch(Throwable $e){ flash('error','Ek paket silinemedi.'); }
    redirect_to('admin/product-center/addons');
}
function ao_v249_ensure_product_custom_fields_schema(){
    if(function_exists('ao_v238_ensure_schema')) { ao_v238_ensure_schema(); return; }
    try{
        db()->exec("CREATE TABLE IF NOT EXISTS product_custom_fields (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT DEFAULT 0,
            product_id INT DEFAULT 0,
            field_key VARCHAR(120) NOT NULL,
            label VARCHAR(190) NOT NULL,
            field_type VARCHAR(40) DEFAULT 'text',
            options TEXT NULL,
            is_required TINYINT(1) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_field_key(field_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_builder_output_products_ensure_v2630(){
    static $done=false; if($done) return; $done=true;
    try {
        ao_v2334_seed_product_groups();
        ao_v237_ensure_product_pricing_schema();
        $groupIds=[];
        foreach(['sitebuilder','mobilebuilder'] as $slug){
            $q=db()->prepare('SELECT id FROM product_groups WHERE slug=? LIMIT 1');
            $q->execute([$slug]);
            $groupIds[$slug]=(int)$q->fetchColumn();
        }
        $defs=[
            ['group'=>'sitebuilder','name'=>'Site Builder AI Başlangıç - 10 Sayfa','slug'=>'sitebuilder-ai-10-pages','price'=>999.00,'short'=>'AI yardımcı ile tek site ve 10 sayfaya kadar taslak oluşturma paketi.','desc'=>'Müşteri panelinde AI Site Builder kullanımı, tek web sitesi projesi ve 10 sayfaya kadar içerik/tasarım taslağı üretimi için başlangıç paketi.'],
            ['group'=>'sitebuilder','name'=>'Site Builder AI Sınırsız','slug'=>'sitebuilder-ai-unlimited','price'=>2499.00,'short'=>'AI yardımcı ile sınırsız sayfa ve gelişmiş site tasarım paketi.','desc'=>'Müşteri panelinde AI Site Builder ile sınırsız sayfa taslağı, tekrar düzenleme ve gelişmiş builder kullanım hakkı.'],
            ['group'=>'mobilebuilder','name'=>'Mobile Builder AI Başlangıç','slug'=>'mobilebuilder-ai-start','price'=>1499.00,'short'=>'AI yardımcı ile tek mobil uygulama taslağı oluşturma paketi.','desc'=>'Müşteri panelinde AI Mobile Builder kullanımı, tek uygulama taslağı ve temel PWA önizleme akışı için başlangıç paketi.'],
            ['group'=>'mobilebuilder','name'=>'Mobile Builder AI Sınırsız','slug'=>'mobilebuilder-ai-unlimited','price'=>3499.00,'short'=>'AI yardımcı ile sınırsız mobil uygulama taslak paketi.','desc'=>'Müşteri panelinde AI Mobile Builder ile sınırsız uygulama taslağı, ekran akışı ve özellik önerisi üretimi.'],
            ['group'=>'mobilebuilder','name'=>'Mobile Builder APK Çıktı Paketi','slug'=>'mobilebuilder-apk-output','price'=>1499.00,'short'=>'Android APK dosyası oluşturma ve teslim paketi.','desc'=>'Mobil uygulama projesi için Android APK çıktısı, temel imzalama yönlendirmesi ve teslim hazırlığı.'],
            ['group'=>'mobilebuilder','name'=>'Mobile Builder AAB Çıktı Paketi','slug'=>'mobilebuilder-aab-output','price'=>1999.00,'short'=>'Google Play uyumlu AAB çıktı paketi.','desc'=>'Mobil uygulama projesi için Google Play yayınlamaya uygun AAB çıktı hazırlığı, build notları ve teslim akışı.'],
            ['group'=>'mobilebuilder','name'=>'Mobile Builder Kaynak Kod Paketi','slug'=>'mobilebuilder-source-code','price'=>3999.00,'short'=>'Mobil uygulama kaynak kod teslim paketi.','desc'=>'Mobile Builder projesinin kaynak kod ZIP teslimi, yapılandırma notları ve geliştiriciye devredilebilir proje çıktısı.'],
            ['group'=>'sitebuilder','name'=>'Site Builder ZIP / Kaynak Kod Paketi','slug'=>'sitebuilder-output-package','price'=>2499.00,'short'=>'Site Builder site ZIP ve kaynak kod teslim paketi.','desc'=>'Site Builder ile hazırlanan sitenin yayınlanabilir ZIP/kaynak kod çıktısı ve kurulum notları.'],
        ];
        $pcols=ao_table_columns_v2334('products');
        $rate=function_exists('ao_v237_currency_rate') ? (float)ao_v237_currency_rate('USD') : 47.25;
        foreach($defs as $i=>$d){
            $payload=[
                'group_id'=>$groupIds[$d['group']] ?: null,
                'name'=>$d['name'],
                'slug'=>$d['slug'],
                'type'=>$d['group'],
                'module_name'=>$d['group'],
                'short_description'=>$d['short'],
                'description'=>$d['desc'],
                'price'=>$d['price'],
                'currency'=>'TRY',
                'billing_cycle'=>'one_time',
                'is_active'=>1,
                'visibility'=>'visible',
                'sort_order'=>900+$i,
            ];
            if(isset($pcols['currency_code'])) $payload['currency_code']='TRY';
            if(isset($pcols['is_featured'])) $payload['is_featured']=1;
            if(isset($pcols['is_popular'])) $payload['is_popular']=1;
            if(isset($pcols['is_new'])) $payload['is_new']=1;
            if(isset($pcols['source_type'])) $payload['source_type']='core_feature';
            if(isset($pcols['external_id'])) $payload['external_id']='builder-output:'.$d['slug'];
            $payload=array_filter($payload, fn($v,$k)=>isset($pcols[$k]), ARRAY_FILTER_USE_BOTH);
            $fields=array_keys($payload);
            $sql='INSERT INTO products(`'.implode('`,`',$fields).'`) VALUES('.implode(',',array_fill(0,count($fields),'?')).') ON DUPLICATE KEY UPDATE ';
            $sql.=implode(',',array_map(fn($c)=>'`'.$c.'`=VALUES(`'.$c.'`)', array_filter($fields, fn($c)=>$c!=='slug')));
            db()->prepare($sql)->execute(array_values($payload));
            $q=db()->prepare('SELECT id FROM products WHERE slug=? LIMIT 1'); $q->execute([$d['slug']]); $pid=(int)$q->fetchColumn();
            if($pid>0){
                $usd=$rate>0 ? round($d['price']/$rate,2) : 0.00;
                db()->prepare('INSERT INTO product_pricing(product_id,cycle,price,setup_fee,currency,price_usd,price_try,setup_fee_usd,setup_fee_try,base_currency,exchange_rate,margin_percent,auto_convert,is_active,source_type,external_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE price=VALUES(price),setup_fee=VALUES(setup_fee),currency=VALUES(currency),price_usd=VALUES(price_usd),price_try=VALUES(price_try),base_currency=VALUES(base_currency),exchange_rate=VALUES(exchange_rate),auto_convert=VALUES(auto_convert),is_active=VALUES(is_active),source_type=VALUES(source_type),external_id=VALUES(external_id)')
                    ->execute([$pid,'one_time',$d['price'],0,'TRY',$usd,$d['price'],0,0,'TRY',$rate,0,0,1,'core_feature','builder-output:'.$d['slug']]);
            }
        }
    } catch(Throwable $e){ error_log('[builder-output-products] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
ao_builder_output_products_ensure_v2630();
function ao_v237_currency_rate($currency='USD'){
    $currency = strtoupper($currency ?: 'USD');
    if($currency==='TL') $currency='TRY';
    if($currency==='TRY') return 1.0;
    try{
        if(function_exists('ao_v23_ensure_schema')) ao_v23_ensure_schema();
        $q=db()->prepare('SELECT final_rate FROM currency_rates WHERE currency_code=? LIMIT 1');
        $q->execute([$currency]);
        $rate=(float)$q->fetchColumn();
        if($rate>0) return $rate;
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return $currency==='EUR' ? 51.45 : ($currency==='GBP' ? 60.90 : 47.25);
}
function ao_v237_currency_margin($currency='USD'){
    $currency = strtoupper($currency ?: 'USD');
    try{
        $q=db()->prepare('SELECT margin_percent FROM currency_rates WHERE currency_code=? LIMIT 1');
        $q->execute([$currency]);
        return (float)$q->fetchColumn();
    }catch(Throwable $e){ return 0.0; }
}
function ao_v237_parse_money($v){
    if($v===null) return 0.0;
    $v=trim((string)$v);
    if($v==='') return 0.0;
    return round((float)str_replace(',','.',$v),2);
}
function ao_v237_price_try_from_currency($amount,$currency){
    $amount = ao_v237_parse_money($amount);
    $currency = strtoupper((string)($currency ?: 'TRY'));
    if($currency === 'TL') $currency = 'TRY';
    if($amount <= 0) return 0.0;
    if($currency === 'TRY') return $amount;
    return round($amount * ao_v237_currency_rate($currency), 2);
}
function ao_v237_price_currency_from_try($tryAmount,$currency){
    $tryAmount = ao_v237_parse_money($tryAmount);
    $currency = strtoupper((string)($currency ?: 'TRY'));
    if($currency === 'TL') $currency = 'TRY';
    if($tryAmount <= 0) return 0.0;
    if($currency === 'TRY') return $tryAmount;
    return round($tryAmount / max(0.01, ao_v237_currency_rate($currency)), 2);
}
function ao_v237_dual_from_inputs($usdRaw,$tryRaw,$rate){
    $usd = ao_v237_parse_money($usdRaw);
    $try = ao_v237_parse_money($tryRaw);
    // Eski formlar için uyumluluk: USD/TRY çiftinden marjlı satış kuru ile karşı değer üretilir.
    if($usd > 0){ $try = round($usd * $rate, 2); }
    elseif($try > 0 && $rate > 0){ $usd = round($try / $rate, 2); }
    return [$usd,$try];
}
function ao_v237_save_product_prices($productId){
    ao_v237_ensure_product_pricing_schema();
    $rate = ao_v237_currency_rate('USD');
    $margin = ao_v237_currency_margin('USD');
    $legacyProduct = [];
    try{
        $q = db()->prepare('SELECT price,currency,currency_code,billing_cycle FROM products WHERE id=? LIMIT 1');
        $q->execute([(int)$productId]);
        $legacyProduct = $q->fetch(PDO::FETCH_ASSOC) ?: [];
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $cycles = ['one_time'=>'Tek seferlik','monthly'=>'Aylık','quarterly'=>'3 Aylık','semiannually'=>'6 Aylık','annually'=>'Yıllık','biennially'=>'2 Yıllık','triennially'=>'3 Yıllık'];
    $removedCycles = (array)($_POST['price_removed'] ?? []);
    foreach($cycles as $cycle=>$label){
        if ((string)($removedCycles[$cycle] ?? '0') === '1') {
            try { db()->prepare('UPDATE product_pricing SET is_active=0 WHERE product_id=? AND cycle=?')->execute([(int)$productId,$cycle]); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
            continue;
        }
        $cycleSubmitted = array_key_exists($cycle, (array)($_POST['price_base'] ?? []))
            || array_key_exists($cycle, (array)($_POST['setup_base'] ?? []))
            || array_key_exists($cycle, (array)($_POST['price_try'] ?? []))
            || array_key_exists($cycle, (array)($_POST['setup_try'] ?? []))
            || array_key_exists($cycle, (array)($_POST['base_currency'] ?? []))
            || array_key_exists($cycle, (array)(($_POST['price_active']['USD'] ?? [])));
        $baseCurrency = strtoupper((string)($_POST['base_currency'][$cycle] ?? 'TRY'));
        if($baseCurrency === 'TL') $baseCurrency = 'TRY';
        $priceBase = ao_v237_parse_money($_POST['price_base'][$cycle] ?? null);
        $setupBase = ao_v237_parse_money($_POST['setup_base'][$cycle] ?? null);
        $priceTry = ao_v237_parse_money($_POST['price_try'][$cycle] ?? null);
        $setupTry = ao_v237_parse_money($_POST['setup_try'][$cycle] ?? null);
        if($priceBase > 0) $priceTry = ao_v237_price_try_from_currency($priceBase, $baseCurrency);
        elseif($priceTry > 0) $priceBase = ao_v237_price_currency_from_try($priceTry, $baseCurrency);
        if($setupBase > 0) $setupTry = ao_v237_price_try_from_currency($setupBase, $baseCurrency);
        elseif($setupTry > 0) $setupBase = ao_v237_price_currency_from_try($setupTry, $baseCurrency);
        $priceUsd = ao_v237_price_currency_from_try($priceTry, 'USD');
        $setupUsd = ao_v237_price_currency_from_try($setupTry, 'USD');
        $active = (string)($_POST['price_active']['USD'][$cycle] ?? '0') === '1' || (string)($_POST['price_active']['TRY'][$cycle] ?? '0') === '1' ? 1 : 0;
        if ($cycleSubmitted && $priceBase <= 0 && $setupBase <= 0 && $priceTry <= 0 && $setupTry <= 0 && $priceUsd <= 0 && $setupUsd <= 0) {
            $active = 0;
        }
        if(!$cycleSubmitted && $priceUsd <= 0 && $priceTry <= 0 && $setupUsd <= 0 && $setupTry <= 0){
            $existing = [];
            try{
                $q = db()->prepare('SELECT price,setup_fee,currency,price_usd,price_try,setup_fee_usd,setup_fee_try,is_active FROM product_pricing WHERE product_id=? AND cycle=? LIMIT 1');
                $q->execute([(int)$productId,$cycle]);
                $existing = $q->fetch(PDO::FETCH_ASSOC) ?: [];
            }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
            if($existing){
                $baseCurrency = strtoupper((string)($existing['base_currency'] ?? $existing['currency'] ?? $baseCurrency));
                if($baseCurrency === 'TL') $baseCurrency = 'TRY';
                $priceUsd = (float)($existing['price_usd'] ?? 0);
                $priceTry = (float)($existing['price_try'] ?? 0);
                $setupUsd = (float)($existing['setup_fee_usd'] ?? 0);
                $setupTry = (float)($existing['setup_fee_try'] ?? 0);
                $rawPrice = (float)($existing['price'] ?? 0);
                $rawSetup = (float)($existing['setup_fee'] ?? 0);
                $cur = strtoupper((string)($existing['currency'] ?? 'TRY'));
                if($priceTry <= 0 && $rawPrice > 0) $priceTry = ao_v237_price_try_from_currency($rawPrice, $cur);
                if($priceUsd <= 0 && $priceTry > 0) $priceUsd = ao_v237_price_currency_from_try($priceTry, 'USD');
                if($setupTry <= 0 && $rawSetup > 0) $setupTry = ao_v237_price_try_from_currency($rawSetup, $cur);
                if($setupUsd <= 0 && $setupTry > 0) $setupUsd = ao_v237_price_currency_from_try($setupTry, 'USD');
                $priceBase = $rawPrice > 0 ? $rawPrice : ao_v237_price_currency_from_try($priceTry, $baseCurrency);
                $setupBase = $rawSetup > 0 ? $rawSetup : ao_v237_price_currency_from_try($setupTry, $baseCurrency);
            }
            if($priceUsd <= 0 && $priceTry <= 0 && $cycle === ($legacyProduct['billing_cycle'] ?? 'monthly')) {
                $legacyPrice = (float)($legacyProduct['price'] ?? 0);
                $legacyCurrency = strtoupper((string)($legacyProduct['currency_code'] ?? $legacyProduct['currency'] ?? 'TRY'));
                if($legacyPrice > 0){
                    $baseCurrency = $legacyCurrency === 'TL' ? 'TRY' : $legacyCurrency;
                    $priceBase = $legacyPrice;
                    $priceTry = ao_v237_price_try_from_currency($legacyPrice, $baseCurrency);
                    $priceUsd = ao_v237_price_currency_from_try($priceTry, 'USD');
                    $active = 1;
                }
            }
        }
        try{
            db()->prepare('INSERT INTO product_pricing(product_id,cycle,price,setup_fee,currency,price_usd,price_try,setup_fee_usd,setup_fee_try,base_currency,exchange_rate,margin_percent,auto_convert,is_active) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,1,?) ON DUPLICATE KEY UPDATE price=VALUES(price), setup_fee=VALUES(setup_fee), currency=VALUES(currency), price_usd=VALUES(price_usd), price_try=VALUES(price_try), setup_fee_usd=VALUES(setup_fee_usd), setup_fee_try=VALUES(setup_fee_try), base_currency=VALUES(base_currency), exchange_rate=VALUES(exchange_rate), margin_percent=VALUES(margin_percent), auto_convert=1, is_active=VALUES(is_active)')
                ->execute([$productId,$cycle,$priceBase,$setupBase,$baseCurrency,$priceUsd,$priceTry,$setupUsd,$setupTry,$baseCurrency,ao_v237_currency_rate($baseCurrency),ao_v237_currency_margin($baseCurrency),$active]);
        }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    }
    try{
        $display = function_exists('ao_v2331_product_display_price') ? ao_v2331_product_display_price((int)$productId) : ['try'=>0];
        $displayTry = (float)($display['try'] ?? 0);
        $cols=array_column(db()->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_ASSOC),'Field');
        $set=[];$vals=[];
        if(in_array('price',$cols,true)){ $set[]='price=?'; $vals[]=$displayTry; }
        if(in_array('currency',$cols,true)){ $set[]='currency=?'; $vals[]='TRY'; }
        if(in_array('currency_code',$cols,true)){ $set[]='currency_code=?'; $vals[]='TRY'; }
        if($set){ $vals[]=$productId; db()->prepare('UPDATE products SET '.implode(',',$set).' WHERE id=?')->execute($vals); }
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_v249_save_product_checkout_addons($productId){
    ao_v249_ensure_product_checkout_addons_schema();
    $rows = (array)($_POST['checkout_addons'] ?? []);
    $catalogIds = (array)($rows['catalog_id'] ?? []);
    $names = (array)($rows['name'] ?? []);
    $keys = (array)($rows['key'] ?? []);
    $descriptions = (array)($rows['description'] ?? []);
    $prices = (array)($rows['price'] ?? []);
    $currencies = (array)($rows['currency'] ?? []);
    $provisionKeys = (array)($rows['provision_key'] ?? []);
    $provisionValues = (array)($rows['provision_value'] ?? []);
    $actives = (array)($rows['active'] ?? []);
    try{
        db()->prepare('DELETE FROM product_checkout_addons WHERE product_id=?')->execute([(int)$productId]);
        $st = db()->prepare('INSERT INTO product_checkout_addons(product_id,catalog_id,addon_key,name,description,price,currency,provision_key,provision_value,is_active,sort_order) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
        $sort = 0;
        foreach($names as $idx=>$nameRaw){
            $name = trim((string)$nameRaw);
            if($name === '') continue;
            $catalogId = max(0, (int)($catalogIds[$idx] ?? 0));
            $key = trim((string)($keys[$idx] ?? ''));
            if($key === '') $key = $name;
            $key = trim(preg_replace('~[^a-z0-9_-]+~', '-', strtolower($key)), '-');
            if($key === '') $key = 'ek-paket-'.$sort;
            $currency = strtoupper(trim((string)($currencies[$idx] ?? 'TRY')));
            if($currency === 'TL' || $currency === '') $currency = 'TRY';
            $price = ao_v237_parse_money($prices[$idx] ?? 0);
            $provisionKey = trim(preg_replace('~[^a-z0-9_:-]+~', '', strtolower((string)($provisionKeys[$idx] ?? ''))));
            $provisionValue = trim((string)($provisionValues[$idx] ?? ''));
            $active = (string)($actives[$idx] ?? '0') === '1' ? 1 : 0;
            $st->execute([(int)$productId,$catalogId ?: null,$key,$name,trim((string)($descriptions[$idx] ?? '')),$price,$currency,$provisionKey,$provisionValue,$active,$sort]);
            $sort++;
        }
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); throw $e; }
}
function ao_v249_save_product_custom_fields($productId,$groupId=0){
    ao_v249_ensure_product_custom_fields_schema();
    $rows = (array)($_POST['custom_fields'] ?? []);
    $labels = (array)($rows['label'] ?? []);
    $keys = (array)($rows['key'] ?? []);
    $types = (array)($rows['type'] ?? []);
    $options = (array)($rows['options'] ?? []);
    $required = (array)($rows['required'] ?? []);
    $active = (array)($rows['active'] ?? []);
    $allowed = ['text','textarea','url','tel','email','number','select','file'];
    try{
        db()->prepare('DELETE FROM product_custom_fields WHERE product_id=?')->execute([(int)$productId]);
        $st = db()->prepare('INSERT INTO product_custom_fields(group_id,product_id,field_key,label,field_type,options,is_required,is_active,sort_order) VALUES(?,?,?,?,?,?,?,?,?)');
        $sort = 0;
        foreach($labels as $idx=>$labelRaw){
            $label = trim((string)$labelRaw);
            if($label === '') continue;
            $key = trim((string)($keys[$idx] ?? ''));
            if($key === '') $key = $label;
            $key = trim(preg_replace('~[^a-z0-9_-]+~', '-', strtolower($key)), '-');
            if($key === '') $key = 'alan-'.$sort;
            $key = 'p'.(int)$productId.'-'.$key;
            $type = trim((string)($types[$idx] ?? 'text'));
            if(!in_array($type,$allowed,true)) $type = 'text';
            $st->execute([(int)$groupId,(int)$productId,$key,$label,$type,trim((string)($options[$idx] ?? '')), (string)($required[$idx] ?? '0') === '1' ? 1 : 0, (string)($active[$idx] ?? '0') === '1' ? 1 : 0, $sort]);
            $sort++;
        }
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); throw $e; }
}
function ao_v237_refresh_try_prices(){
    ao_v237_ensure_product_pricing_schema();
    try{
        $rows = db()->query("SELECT id,price,setup_fee,currency,base_currency FROM product_pricing WHERE auto_convert=1")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $st = db()->prepare('UPDATE product_pricing SET price_try=?, setup_fee_try=?, price_usd=?, setup_fee_usd=?, exchange_rate=?, margin_percent=? WHERE id=?');
        foreach($rows as $row){
            $currency = strtoupper((string)($row['base_currency'] ?? $row['currency'] ?? 'TRY'));
            if($currency === 'TL') $currency = 'TRY';
            $priceTry = ao_v237_price_try_from_currency((float)($row['price'] ?? 0), $currency);
            $setupTry = ao_v237_price_try_from_currency((float)($row['setup_fee'] ?? 0), $currency);
            $st->execute([
                $priceTry,
                $setupTry,
                ao_v237_price_currency_from_try($priceTry, 'USD'),
                ao_v237_price_currency_from_try($setupTry, 'USD'),
                ao_v237_currency_rate($currency),
                ao_v237_currency_margin($currency),
                (int)$row['id']
            ]);
        }
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{
        // Ürün listesi ve site vitrinleri eski products.price alanını okuyorsa sıfır görünmesin diye
        // aktif ilk periyot fiyatı products tablosuna da yansıtılır.
        $ids = db()->query('SELECT DISTINCT product_id FROM product_pricing')->fetchAll(PDO::FETCH_COLUMN);
        foreach($ids as $pid){
            $display = ao_v2331_product_display_price((int)$pid);
            if(($display['try'] ?? 0) > 0){
                $cols=array_column(db()->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_ASSOC),'Field');
                $set=[]; $vals=[];
                if(in_array('price',$cols,true)){ $set[]='price=?'; $vals[]=$display['try']; }
                if(in_array('currency',$cols,true)){ $set[]='currency=?'; $vals[]='TRY'; }
                if(in_array('currency_code',$cols,true)){ $set[]='currency_code=?'; $vals[]='TRY'; }
                if($set){ $vals[]=(int)$pid; db()->prepare('UPDATE products SET '.implode(',',$set).' WHERE id=?')->execute($vals); }
            }
        }
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}



