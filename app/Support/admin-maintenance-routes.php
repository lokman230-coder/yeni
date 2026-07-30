<?php
// Admin maintenance helpers for security, cache and backup screens.
function ao_v980_ensure_schema() { static $done=false; if($done) return; $done=true;
    try { db()->exec("CREATE TABLE IF NOT EXISTS admin_roles (id INT AUTO_INCREMENT PRIMARY KEY, role_key VARCHAR(80) UNIQUE, name VARCHAR(160), description TEXT NULL, is_system TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS role_permissions (id INT AUTO_INCREMENT PRIMARY KEY, role_key VARCHAR(80), permission_key VARCHAR(160), is_allowed TINYINT(1) DEFAULT 1, UNIQUE KEY role_perm (role_key, permission_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS admin_security_settings (id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(120) UNIQUE, setting_value TEXT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS backup_jobs (id INT AUTO_INCREMENT PRIMARY KEY, job_type VARCHAR(50) DEFAULT 'manual', backup_type VARCHAR(50) DEFAULT 'full', file_path VARCHAR(255) NULL, file_size BIGINT DEFAULT 0, status VARCHAR(30) DEFAULT 'created', notes TEXT NULL, created_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS cache_events (id INT AUTO_INCREMENT PRIMARY KEY, cache_area VARCHAR(80), action VARCHAR(80), deleted_items INT DEFAULT 0, status VARCHAR(30) DEFAULT 'success', message TEXT NULL, created_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS two_factor_recovery_codes (id INT AUTO_INCREMENT PRIMARY KEY, admin_id INT NOT NULL, code_hash VARCHAR(255) NOT NULL, used_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("INSERT IGNORE INTO admin_roles(role_key,name,description,is_system) VALUES
        ('super_admin','Süper Admin','Tüm modül ve sistem ayarlarına erişir.',1),
        ('finance_manager','Finans Müdürü','Fatura, ödeme, iade ve kart komisyonlarını yönetir.',0),
        ('support_agent','Destek Personeli','Ticket ve bilgi bankası işlemlerini yürütür.',0),
        ('domain_operator','Domain Operatörü','Domain, registrar, DNS, WHOIS ve EPP işlemlerini yönetir.',0),
        ('hosting_operator','Hosting Operatörü','Sunucu, hosting hesabı ve servis operasyonlarını yönetir.',0),
        ('marketplace_manager','Marketplace Yöneticisi','İlan, teklif, öne çıkarma ve komisyonları yönetir.',0),
        ('content_editor','İçerik Editörü','Tema, sayfa, duyuru ve içerik alanlarını yönetir.',0)"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("INSERT IGNORE INTO admin_security_settings(setting_key,setting_value) VALUES
        ('two_factor_enabled','0'),('ip_whitelist',''),('api_secret_encryption','planned'),('session_timeout_minutes','20'),('csrf_protection','1'),('rate_limit_login','1')"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("INSERT IGNORE INTO admin_search_index(title,route,category,keywords,is_active) VALUES
        ('Güvenlik ve Yetkiler','admin/security','Sistem','rol yetki izin 2fa google authenticator güvenlik ip whitelist admin rolleri',1),
        ('Cache Temizleme Merkezi','admin/cache-center','Sistem','cache temizle önbellek css js tema route view temizle',1),
        ('Backup Center','admin/backup-center','Sistem','yedek backup geri yükle veritabanı dosya tam sistem',1)"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_v980_security_items() {
    ao_v980_ensure_schema();
    $roles=[]; try { $roles=db()->query('SELECT * FROM admin_roles ORDER BY is_system DESC, name')->fetchAll(); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $settings=[]; try { foreach(db()->query('SELECT setting_key,setting_value FROM admin_security_settings')->fetchAll() as $r) $settings[$r['setting_key']]=$r['setting_value']; } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return ['roles'=>$roles,'settings'=>$settings];
}
function ao_v980_cache_clear($area='all') {
    $targets=[];
    if ($area==='all' || $area==='views') $targets[] = __DIR__.'/storage/cache/views';
    if ($area==='all' || $area==='routes') $targets[] = __DIR__.'/storage/cache/routes';
    if ($area==='all' || $area==='assets') $targets[] = __DIR__.'/storage/cache/assets';
    if ($area==='all' || $area==='theme') $targets[] = __DIR__.'/storage/cache/themes';
    if ($area==='all' || $area==='analysis') $targets[] = __DIR__.'/storage/cache/analysis';
    $deleted=0;
    foreach($targets as $dir){ if(!is_dir($dir)) continue; $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST); foreach($it as $f){ if($f->isFile() || $f->isLink()){ @unlink($f->getPathname()); $deleted++; } elseif($f->isDir()) @rmdir($f->getPathname()); } }
    try{ db()->prepare('INSERT INTO cache_events(cache_area,action,deleted_items,status,message,created_by) VALUES(?,?,?,?,?,?)')->execute([$area,'clear',$deleted,'success','Cache temizleme tamamlandı.',$_SESSION['admin_id']??null]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return $deleted;
}
function ao_v980_backup_create($type='database') {
    $dir=__DIR__.'/storage/backups'; if(!is_dir($dir)) @mkdir($dir,0775,true);
    $file=$dir.'/ahost-backup-'.$type.'-'.date('Ymd-His').'.txt';
    $content="Ahost One Backup Manifest\nType: $type\nDate: ".date('c')."\n\n";
    if ($type==='database' || $type==='full') { try { foreach(db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM) as $t){ $content.='TABLE: '.$t[0].' | ROWS: '.table_count($t[0])."\n"; } } catch(Throwable $e){ $content.='DB error: '.$e->getMessage()."\n"; } }
    if ($type==='files' || $type==='full') { $content.="FILES: app, public, themes, uploads included by restore policy.\n"; }
    file_put_contents($file,$content); $size=filesize($file) ?: 0;
    try{ db()->prepare('INSERT INTO backup_jobs(job_type,backup_type,file_path,file_size,status,notes,created_by) VALUES(?,?,?,?,?,?,?)')->execute(['manual',$type,$file,$size,'created','Manifest backup created; production builds can switch to zip/tar archive.',$_SESSION['admin_id']??null]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return $file;
}
function ao_v980_backup_rows() { ao_v980_ensure_schema(); try{return db()->query('SELECT * FROM backup_jobs ORDER BY id DESC LIMIT 20')->fetchAll();}catch(Throwable $e){return [];} }
function ao_v980_cache_rows() { ao_v980_ensure_schema(); try{return db()->query('SELECT * FROM cache_events ORDER BY id DESC LIMIT 20')->fetchAll();}catch(Throwable $e){return [];} }

