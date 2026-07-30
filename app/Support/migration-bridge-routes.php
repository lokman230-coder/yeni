<?php
// Migration Bridge schema, preview and import routes.
function ao_bridge_ensure_schema() { static $done=false; if($done) return; $done=true;
    try { db()->exec("CREATE TABLE IF NOT EXISTS bridge_connections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(160) NOT NULL,
        source_type VARCHAR(40) DEFAULT 'source',
        source_host VARCHAR(190) NOT NULL,
        source_database VARCHAR(190) NOT NULL,
        source_username VARCHAR(190) NOT NULL,
        source_password TEXT NULL,
        source_charset VARCHAR(40) DEFAULT 'utf8mb4',
        table_prefix VARCHAR(40) DEFAULT 'tbl',
        status VARCHAR(40) DEFAULT 'ready',
        last_test_status VARCHAR(40) NULL,
        last_test_message TEXT NULL,
        last_test_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS bridge_runs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        connection_id INT NOT NULL,
        run_type VARCHAR(40) NOT NULL,
        status VARCHAR(40) DEFAULT 'running',
        summary_json LONGTEXT NULL,
        error_message TEXT NULL,
        started_at DATETIME NULL,
        finished_at DATETIME NULL,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY connection_id(connection_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS bridge_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        run_id INT NOT NULL,
        entity_type VARCHAR(80) NOT NULL,
        source_id VARCHAR(80) NULL,
        source_label VARCHAR(255) NULL,
        target_table VARCHAR(120) NULL,
        target_id INT NULL,
        action_name VARCHAR(80) NULL,
        status VARCHAR(40) DEFAULT 'ok',
        message TEXT NULL,
        payload_json LONGTEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY run_id(run_id), KEY entity_type(entity_type), KEY status(status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS bridge_import_maps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        connection_id INT NOT NULL,
        entity_type VARCHAR(80) NOT NULL,
        source_id VARCHAR(80) NOT NULL,
        target_table VARCHAR(120) NOT NULL,
        target_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_bridge_map(connection_id,entity_type,source_id,target_table),
        KEY target_lookup(target_table,target_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_bridge_ensure_target_schema() { static $done=false; if($done) return; $done=true;
    $sqls = [];
    $sqls[] = "CREATE TABLE IF NOT EXISTS customers (id INT AUTO_INCREMENT PRIMARY KEY, first_name VARCHAR(120) NULL, last_name VARCHAR(120) NULL, company_name VARCHAR(190) NULL, email VARCHAR(190) UNIQUE NULL, password_hash VARCHAR(255) NULL, phone VARCHAR(80) NULL, address1 VARCHAR(255) NULL, address2 VARCHAR(255) NULL, city VARCHAR(120) NULL, state VARCHAR(120) NULL, postcode VARCHAR(40) NULL, country VARCHAR(80) NULL, status VARCHAR(40) DEFAULT 'active', notes TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS product_groups (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160) NOT NULL, slug VARCHAR(190) UNIQUE NOT NULL, description TEXT NULL, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS products (id INT AUTO_INCREMENT PRIMARY KEY, group_id INT NULL, name VARCHAR(190) NOT NULL, slug VARCHAR(220) UNIQUE NOT NULL, type VARCHAR(60) DEFAULT 'service', description TEXT NULL, module_name VARCHAR(120) NULL, auto_setup VARCHAR(40) DEFAULT 'pending', is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY group_id(group_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS domains (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NULL, domain_name VARCHAR(190) NOT NULL, registrar VARCHAR(120) NULL, status VARCHAR(40) DEFAULT 'active', registration_date DATE NULL, expiry_date DATE NULL, next_due_date DATE NULL, auto_renew TINYINT(1) DEFAULT 1, lock_status TINYINT(1) DEFAULT 1, epp_code VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY customer_id(customer_id), KEY domain_name(domain_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS services (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NULL, product_id INT NULL, domain VARCHAR(190) NULL, status VARCHAR(40) DEFAULT 'active', billing_cycle VARCHAR(60) NULL, next_due_date DATE NULL, auto_renew TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY customer_id(customer_id), KEY product_id(product_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS invoices (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NULL, invoice_number VARCHAR(80) UNIQUE NOT NULL, status VARCHAR(40) DEFAULT 'unpaid', subtotal DECIMAL(14,2) DEFAULT 0, tax DECIMAL(14,2) DEFAULT 0, total DECIMAL(14,2) DEFAULT 0, due_date DATE NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY customer_id(customer_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS invoice_items (id INT AUTO_INCREMENT PRIMARY KEY, invoice_id INT NOT NULL, description VARCHAR(255) NOT NULL, amount DECIMAL(14,2) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY invoice_id(invoice_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS tickets (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NULL, subject VARCHAR(255) NOT NULL, department VARCHAR(120) DEFAULT 'General', priority VARCHAR(40) DEFAULT 'medium', status VARCHAR(40) DEFAULT 'open', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY customer_id(customer_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS ticket_replies (id INT AUTO_INCREMENT PRIMARY KEY, ticket_id INT NOT NULL, sender_type VARCHAR(40) DEFAULT 'customer', message LONGTEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY ticket_id(ticket_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS server_nodes (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160) NOT NULL, panel_type VARCHAR(80) DEFAULT 'whm', hostname VARCHAR(190) NULL, ip_address VARCHAR(80) NULL, username VARCHAR(120) NULL, api_token TEXT NULL, status VARCHAR(40) DEFAULT 'active', test_mode TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS hosting_accounts (id INT AUTO_INCREMENT PRIMARY KEY, service_id INT NULL, server_name VARCHAR(160) NULL, server_ip VARCHAR(80) NULL, whm_username VARCHAR(120) NULL, panel_password TEXT NULL, package_name VARCHAR(160) NULL, cpanel_url VARCHAR(255) NULL, webmail_url VARCHAR(255) NULL, whm_url VARCHAR(255) NULL, directadmin_url VARCHAR(255) NULL, vps_panel_url VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY service_id(service_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS domain_registrars (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160) NOT NULL, slug VARCHAR(160) UNIQUE NOT NULL, module_name VARCHAR(160) NULL, status VARCHAR(40) DEFAULT 'active', test_mode TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS registrar_configs (id INT AUTO_INCREMENT PRIMARY KEY, registrar_id INT NOT NULL, config_key VARCHAR(160) NOT NULL, config_value TEXT NULL, UNIQUE KEY uniq_reg_cfg(registrar_id,config_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS orders (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NULL, order_number VARCHAR(80) UNIQUE NULL, status VARCHAR(40) DEFAULT 'pending', total DECIMAL(14,2) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY customer_id(customer_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    foreach($sqls as $sql){ try { db()->exec($sql); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
    try { db()->exec("ALTER TABLE domains ADD COLUMN next_due_date DATE NULL AFTER expiry_date"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_bridge_required_source_entities($conn) {
    return [
        'customers'=>['table'=>ao_bridge_table($conn,'clients'),'label'=>'CONCAT(firstname," ",lastname," <",email,">")','required'=>true],
        'product_groups'=>['table'=>ao_bridge_table($conn,'productgroups'),'label'=>'name','required'=>false],
        'products'=>['table'=>ao_bridge_table($conn,'products'),'label'=>'name','required'=>false],
        'product_pricing'=>['table'=>ao_bridge_table($conn,'pricing'),'label'=>'CONCAT(type," #",relid," ",monthly,"/",annually)','required'=>false],
        'domain_pricing'=>['table'=>ao_bridge_table($conn,'domainpricing'),'label'=>'extension','required'=>false],
        'servers'=>['table'=>ao_bridge_table($conn,'servers'),'label'=>'name','required'=>false],
        'registrars'=>['table'=>ao_bridge_table($conn,'registrars'),'label'=>'registrar','required'=>false],
        'services'=>['table'=>ao_bridge_table($conn,'hosting'),'label'=>'CONCAT(domain," / ",domainstatus)','required'=>false],
        'domains'=>['table'=>ao_bridge_table($conn,'domains'),'label'=>'domain','required'=>false],
        'orders'=>['table'=>ao_bridge_table($conn,'orders'),'label'=>'CONCAT("Order #",id," ",status)','required'=>false],
        'invoices'=>['table'=>ao_bridge_table($conn,'invoices'),'label'=>'CONCAT("Invoice #",id," ",status," ",total)','required'=>false],
        'invoice_items'=>['table'=>ao_bridge_table($conn,'invoiceitems'),'label'=>'description','required'=>false],
        'tickets'=>['table'=>ao_bridge_table($conn,'tickets'),'label'=>'title','required'=>false],
    ];
}
function ao_bridge_table_exists($pdo,$table) {
    try { $s=$pdo->prepare('SHOW TABLES LIKE ?'); $s->execute([$table]); return (bool)$s->fetchColumn(); } catch(Throwable $e) { return false; }
}
function ao_bridge_safe_count($pdo,$table) {
    if(!ao_bridge_table_exists($pdo,$table)) return ['exists'=>false,'count'=>0,'message'=>'Tablo bulunamadı'];
    try { return ['exists'=>true,'count'=>(int)$pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn(),'message'=>'OK']; } catch(Throwable $e) { return ['exists'=>true,'count'=>0,'message'=>$e->getMessage()]; }
}
function ao_bridge_test_connection_full($conn) {
    $pdo=ao_bridge_connect($conn); $entities=ao_bridge_required_source_entities($conn); $summary=[]; $errors=[];
    foreach($entities as $entity=>$cfg){ $res=ao_bridge_safe_count($pdo,$cfg['table']); $summary[$entity]=['table'=>$cfg['table'],'exists'=>$res['exists'],'count'=>$res['count'],'message'=>$res['message']]; if($cfg['required'] && !$res['exists']) $errors[]=$cfg['table'].' zorunlu tablo bulunamadı.'; }
    return ['ok'=>empty($errors),'summary'=>$summary,'message'=>empty($errors)?'Bağlantı başarılı. Kaynak tablolar okunabiliyor.':implode(' ', $errors)];
}

// Migration Bridge source database helpers.
function ao_bridge_prefix($prefix) { return preg_replace('/[^a-zA-Z0-9_]/', '', (string)$prefix); }
function ao_bridge_table($conn, $name) { return ao_bridge_prefix($conn['table_prefix'] ?? 'tbl') . $name; }
function ao_bridge_connect($conn) {
    $charset = $conn['source_charset'] ?: 'utf8mb4';
    $dsn = 'mysql:host='.$conn['source_host'].(!empty($conn['source_port'])?';port='.(int)$conn['source_port']:'').';dbname='.$conn['source_database'].';charset='.$charset;
    return new PDO($dsn, $conn['source_username'], $conn['source_password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
}
function ao_bridge_get_connection($id) {
    ao_bridge_ensure_schema();
    $q=db()->prepare('SELECT * FROM bridge_connections WHERE id=? LIMIT 1'); $q->execute([(int)$id]); return $q->fetch() ?: null;
}
function ao_bridge_log_item($runId,$entity,$sourceId,$label,$targetTable,$targetId,$action,$status,$message,$payload=[]) {
    ao_bridge_ensure_schema();
    try { $q=db()->prepare('INSERT INTO bridge_items(run_id,entity_type,source_id,source_label,target_table,target_id,action_name,status,message,payload_json) VALUES(?,?,?,?,?,?,?,?,?,?)'); $q->execute([$runId,$entity,(string)$sourceId,$label,$targetTable,$targetId,$action,$status,$message,json_encode($payload, JSON_UNESCAPED_UNICODE)]); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_bridge_map_target($connectionId,$entity,$sourceId,$targetTable) {
    ao_bridge_ensure_schema();
    $q=db()->prepare('SELECT target_id FROM bridge_import_maps WHERE connection_id=? AND entity_type=? AND source_id=? AND target_table=? LIMIT 1');
    $q->execute([(int)$connectionId,$entity,(string)$sourceId,$targetTable]);
    $v=$q->fetchColumn(); return $v ? (int)$v : 0;
}
function ao_bridge_save_map($connectionId,$entity,$sourceId,$targetTable,$targetId) {
    ao_bridge_ensure_schema();
    $q=db()->prepare('INSERT INTO bridge_import_maps(connection_id,entity_type,source_id,target_table,target_id) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE target_id=VALUES(target_id)');
    $q->execute([(int)$connectionId,$entity,(string)$sourceId,$targetTable,(int)$targetId]);
}
function ao_bridge_source_count($pdo,$table) { try { return (int)$pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn(); } catch(Throwable $e) { return 0; } }
function ao_bridge_source_sample($pdo,$table,$labelSql,$limit=5) {
    try { return $pdo->query('SELECT id, '.$labelSql.' AS label FROM `'.$table.'` ORDER BY id DESC LIMIT '.(int)$limit)->fetchAll(); } catch(Throwable $e) { return []; }
}
function ao_bridge_status_from_source($status) {
    $s=strtolower((string)$status);
    if(in_array($s,['active','completed','paid','open','answered'],true)) return 'active';
    if(in_array($s,['suspended'],true)) return 'suspended';
    if(in_array($s,['terminated','cancelled','canceled'],true)) return 'terminated';
    if(in_array($s,['pending'],true)) return 'pending';
    return $s ?: 'active';
}

function ao_bridge_db_columns($table) {
    static $cache = [];
    $table = preg_replace('/[^a-zA-Z0-9_]/','',(string)$table);
    if(isset($cache[$table])) return $cache[$table];
    try {
        $q = db()->query('SHOW COLUMNS FROM `'.$table.'`');
        $cols = [];
        foreach($q->fetchAll() as $r) $cols[$r['Field']] = true;
        return $cache[$table] = $cols;
    } catch(Throwable $e) { return $cache[$table] = []; }
}
function ao_bridge_has_column($table,$col) { $cols=ao_bridge_db_columns($table); return isset($cols[$col]); }
function ao_bridge_filter_data($table,$data) {
    $cols=ao_bridge_db_columns($table); $out=[];
    foreach($data as $k=>$v){ if(isset($cols[$k])) $out[$k]=$v; }
    return $out;
}
function ao_bridge_insert_dynamic($table,$data) {
    $data=ao_bridge_filter_data($table,$data);
    if(!$data) throw new Exception($table.' için uyumlu kolon bulunamadı.');
    $cols=array_keys($data); $sql='INSERT INTO `'.$table.'` (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($cols),'?')).')';
    $q=db()->prepare($sql); $q->execute(array_values($data)); return (int)db()->lastInsertId();
}
function ao_bridge_update_dynamic($table,$id,$data) {
    $data=ao_bridge_filter_data($table,$data); if(!$data) return;
    $sets=[]; foreach(array_keys($data) as $c) $sets[]='`'.$c.'`=?';
    $vals=array_values($data); $vals[]=(int)$id;
    db()->prepare('UPDATE `'.$table.'` SET '.implode(',',$sets).' WHERE id=?')->execute($vals);
}
function ao_bridge_find_by($table,$where) {
    $where=ao_bridge_filter_data($table,$where); if(!$where) return 0;
    $parts=[]; $vals=[]; foreach($where as $k=>$v){ $parts[]='`'.$k.'`=?'; $vals[]=$v; }
    $q=db()->prepare('SELECT id FROM `'.$table.'` WHERE '.implode(' AND ',$parts).' LIMIT 1'); $q->execute($vals); return (int)$q->fetchColumn();
}
function ao_bridge_slug($text,$fallback='item') {
    $slug=preg_replace('/[^a-z0-9]+/','-',strtolower((string)$text)); $slug=trim($slug,'-'); return $slug ?: $fallback;
}
function ao_bridge_first($arr,$keys,$default=null) {
    foreach((array)$keys as $k){ if(isset($arr[$k]) && $arr[$k]!=='' && $arr[$k]!==null) return $arr[$k]; }
    return $default;
}
function ao_bridge_dependency_order() {
    return ['customers','product_groups','products','product_pricing','domain_pricing','servers','registrars','services','domains','orders','invoices','invoice_items','tickets'];
}
function ao_bridge_normalize_date($v) {
    $v=trim((string)$v); if($v==='' || $v==='0000-00-00' || $v==='0000-00-00 00:00:00') return null; return substr($v,0,10);
}
function ao_bridge_source_user_id($row) { return ao_bridge_first($row,['userid','clientid','user_id','customer_id'],0); }

function ao_bridge_create_run($connectionId,$runType) {
    ao_bridge_ensure_schema();
    $q=db()->prepare('INSERT INTO bridge_runs(connection_id,run_type,status,started_at,created_by) VALUES(?,?,"running",NOW(),?)'); $q->execute([(int)$connectionId,$runType,(int)($_SESSION['admin_id']??0)]); return (int)db()->lastInsertId();
}
function ao_bridge_finish_run($runId,$status,$summary=[],$error='') {
    ao_bridge_ensure_schema();
    $q=db()->prepare('UPDATE bridge_runs SET status=?, finished_at=NOW(), summary_json=?, error_message=? WHERE id=?');
    $q->execute([$status,json_encode($summary, JSON_UNESCAPED_UNICODE),$error,(int)$runId]);
}
function ao_bridge_run_source($connectionId,$mode='dry_run') {
    ao_bridge_ensure_schema(); ao_bridge_ensure_selector_schema(); ao_bridge_ensure_target_schema();
    $conn=ao_bridge_get_connection($connectionId); if(!$conn) throw new Exception('Bridge bağlantısı bulunamadı.');
    $pdo=ao_bridge_connect($conn); $runId=ao_bridge_create_run($connectionId,$mode); $summary=[];
    $entities = ao_bridge_required_source_entities($conn);
    foreach($entities as $entity=>$cfg){
        $res=ao_bridge_safe_count($pdo,$cfg['table']); $summary[$entity]=$res;
        if(!$res['exists']) { ao_bridge_log_item($runId,$entity,'','',null,null,'preview',$cfg['required']?'error':'warning',$cfg['table'].' tablosu bulunamadı.',$res); continue; }
        foreach(ao_bridge_source_sample($pdo,$cfg['table'],$cfg['label']) as $sample) ao_bridge_log_item($runId,$entity,$sample['id'],$sample['label'],null,null,'preview','ok','Ön izleme kaydı bulundu.',$sample);
    }
    if($mode==='dry_run'){ ao_bridge_finish_run($runId,'completed',$summary); return $runId; }
    foreach(ao_bridge_dependency_order() as $entity){
        if(empty($entities[$entity])) continue; $tbl=$entities[$entity]['table'];
        if(!ao_bridge_table_exists($pdo,$tbl)){ ao_bridge_log_item($runId,$entity,'','',null,null,'import','warning',$tbl.' tablosu bulunamadı.'); continue; }
        try{
            foreach($pdo->query('SELECT * FROM `'.$tbl.'` ORDER BY id ASC') as $r){ ao_bridge_import_row_from_payload($connectionId,$runId,$entity,$r); }
        }catch(Throwable $e){ ao_bridge_log_item($runId,$entity,'','',null,null,'import','error',$e->getMessage()); }
    }
    ao_bridge_finish_run($runId,'completed',$summary); ao_log('bridge','source.import','Aktarım tamamlandı. Run: '.$runId); return $runId;
}

// Migration Bridge SQL upload and selectable import helpers.
function ao_bridge_ensure_selector_schema() { static $done=false; if($done) return; $done=true;
    ao_bridge_ensure_schema();
    try { db()->exec("ALTER TABLE bridge_connections ADD COLUMN source_mode VARCHAR(40) DEFAULT 'database' AFTER source_type"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE bridge_connections ADD COLUMN source_port INT NULL AFTER source_host"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE bridge_connections ADD COLUMN source_ssl TINYINT(1) DEFAULT 0 AFTER source_port"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE bridge_connections ADD COLUMN source_sql_path VARCHAR(255) NULL AFTER source_password"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS bridge_sql_uploads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        connection_id INT NULL,
        source_type VARCHAR(40) DEFAULT 'source',
        original_name VARCHAR(255) NULL,
        stored_path VARCHAR(255) NOT NULL,
        sql_file_name VARCHAR(255) NULL,
        status VARCHAR(40) DEFAULT 'uploaded',
        message TEXT NULL,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY connection_id(connection_id), KEY status(status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS bridge_import_selections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        connection_id INT NOT NULL,
        entity_type VARCHAR(80) NOT NULL,
        source_id VARCHAR(80) NOT NULL,
        source_label VARCHAR(255) NULL,
        selected TINYINT(1) DEFAULT 1,
        payload_json LONGTEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_bridge_selection(connection_id,entity_type,source_id),
        KEY entity_lookup(entity_type,selected)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_bridge_upload_dir() {
    $dir=__DIR__.'/storage/migration_uploads';
    if(!is_dir($dir)) @mkdir($dir,0775,true);
    return $dir;
}
function ao_bridge_sql_unquote($v) {
    $v=trim((string)$v);
    if(strcasecmp($v,'NULL')===0) return null;
    if(strlen($v)>=2 && (($v[0]==="'" && substr($v,-1)==="'") || ($v[0]=='"' && substr($v,-1)=='"'))) {
        $v=substr($v,1,-1);
        $v=str_replace(["\\'",'\\"','\\n','\\r','\\t','\\\\'],["'",'"',"\n","\r","\t",'\\'],$v);
    }
    return $v;
}
function ao_bridge_parse_tuple_values($tuple) {
    $values=[]; $cur=''; $quote=null; $esc=false; $len=strlen($tuple);
    for($i=0;$i<$len;$i++){
        $ch=$tuple[$i];
        if($esc){ $cur.=$ch; $esc=false; continue; }
        if($ch==='\\'){ $cur.=$ch; $esc=true; continue; }
        if($quote){ if($ch===$quote){ $quote=null; } $cur.=$ch; continue; }
        if($ch==="'" || $ch==='"'){ $quote=$ch; $cur.=$ch; continue; }
        if($ch===','){ $values[]=ao_bridge_sql_unquote($cur); $cur=''; continue; }
        $cur.=$ch;
    }
    $values[]=ao_bridge_sql_unquote($cur);
    return $values;
}
function ao_bridge_find_insert_tuples($valuesSql) {
    $tuples=[]; $depth=0; $cur=''; $quote=null; $esc=false; $len=strlen($valuesSql);
    for($i=0;$i<$len;$i++){
        $ch=$valuesSql[$i];
        if($esc){ if($depth>0) $cur.=$ch; $esc=false; continue; }
        if($ch==='\\'){ if($depth>0) $cur.=$ch; $esc=true; continue; }
        if($quote){ if($ch===$quote){ $quote=null; } if($depth>0) $cur.=$ch; continue; }
        if($ch==="'" || $ch==='"'){ $quote=$ch; if($depth>0) $cur.=$ch; continue; }
        if($ch==='('){ if($depth>0) $cur.=$ch; $depth++; continue; }
        if($ch===')'){ $depth--; if($depth===0){ $tuples[]=$cur; $cur=''; } else if($depth>0) $cur.=$ch; continue; }
        if($depth>0) $cur.=$ch;
    }
    return $tuples;
}
function ao_bridge_sql_tables_from_file($path) {
    $txt=@file_get_contents($path); if($txt===false) return [];
    preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i',$txt,$m);
    $tables=array_values(array_unique($m[1] ?? []));
    preg_match_all('/INSERT\s+INTO\s+`?([a-zA-Z0-9_]+)`?/i',$txt,$mi);
    foreach(($mi[1]??[]) as $t) if(!in_array($t,$tables,true)) $tables[]=$t;
    return $tables;
}
function ao_bridge_sql_columns_for_table($path,$table) {
    $txt=@file_get_contents($path); if($txt===false) return [];
    $qt=preg_quote($table,'/');
    if(preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?'.$qt.'`?\s*\((.*?)\)\s*ENGINE/is',$txt,$m)){
        $cols=[]; foreach(preg_split('/\n|\r/',$m[1]) as $line){ $line=trim($line); if(preg_match('/^`([^`]+)`\s+/',$line,$cm)) $cols[]=$cm[1]; }
        return $cols;
    }
    return [];
}
function ao_bridge_sql_rows_for_table($path,$table,$limit=200) {
    $txt=@file_get_contents($path); if($txt===false) return [];
    $qt=preg_quote($table,'/'); $rows=[]; $defaultCols=ao_bridge_sql_columns_for_table($path,$table);
    $re='/INSERT\s+INTO\s+`?'.$qt.'`?\s*(?:\((.*?)\))?\s*VALUES\s*(.*?);/is';
    if(preg_match_all($re,$txt,$matches,PREG_SET_ORDER)){
        foreach($matches as $m){
            $cols=[]; if(!empty($m[1])){ foreach(explode(',',$m[1]) as $c) $cols[]=trim($c," `\t\n\r\0\x0B"); } else $cols=$defaultCols;
            foreach(ao_bridge_find_insert_tuples($m[2]) as $tuple){
                $vals=ao_bridge_parse_tuple_values($tuple); $row=[];
                foreach($vals as $i=>$v){ $key=$cols[$i] ?? ('col_'.$i); $row[$key]=$v; }
                $rows[]=$row; if(count($rows)>=$limit) return $rows;
            }
        }
    }
    return $rows;
}
function ao_bridge_entity_table_map($conn) {
    if(($conn['source_type']??'source')==='source') return ao_bridge_required_source_entities($conn);
    return ao_bridge_required_source_entities($conn);
}
function ao_bridge_row_label($entity,$row) {
    if($entity==='customers') return trim(($row['firstname']??$row['first_name']??'').' '.($row['lastname']??$row['last_name']??'')).' <'.($row['email']??''). '>';
    if($entity==='products' || $entity==='product_groups') return (string)($row['name']??$row['title']??('ID '.$row['id']));
    if($entity==='product_pricing') return 'Pricing '.($row['type']??'').' #'.($row['relid']??$row['id']??'').' | M:'.($row['monthly']??'-').' Y:'.($row['annually']??'-');
    if($entity==='domain_pricing') return '.'.ltrim((string)($row['extension']??$row['tld']??('ID '.$row['id'])),'.');
    if($entity==='domains') return (string)($row['domain']??$row['domain_name']??('ID '.$row['id']));
    if($entity==='services') return (string)($row['domain']??($row['username']??('ID '.$row['id'])));
    if($entity==='invoices') return 'Invoice #'.($row['id']??'').' '.($row['status']??'').' '.($row['total']??'');
    if($entity==='tickets') return (string)($row['title']??$row['subject']??('Ticket #'.$row['id']));
    if($entity==='servers') return (string)($row['name']??$row['hostname']??('Server #'.$row['id']));
    if($entity==='registrars') return (string)($row['registrar']??$row['name']??('Registrar #'.$row['id']));
    return (string)($row['id']??'');
}
function ao_bridge_sql_preview($conn,$maxRows=30) {
    $path=$conn['source_sql_path'] ?? ''; if(!$path || !is_file($path)) return ['ok'=>false,'message'=>'SQL dosyası bulunamadı.','entities'=>[]];
    $entities=ao_bridge_entity_table_map($conn); $out=[];
    foreach($entities as $entity=>$cfg){
        $table=$cfg['table']; $rows=ao_bridge_sql_rows_for_table($path,$table,$maxRows); $count=count(ao_bridge_sql_rows_for_table($path,$table,1000000));
        $sample=[]; foreach($rows as $r){ $sample[]=['id'=>(string)($r['id']??''),'label'=>ao_bridge_row_label($entity,$r),'payload'=>$r]; }
        $out[$entity]=['table'=>$table,'exists'=>$count>0 || in_array($table,ao_bridge_sql_tables_from_file($path),true),'count'=>$count,'sample'=>$sample];
    }
    return ['ok'=>true,'message'=>'SQL yedeği analiz edildi.','entities'=>$out];
}
function ao_bridge_store_selection($connectionId,$preview) {
    ao_bridge_ensure_selector_schema();
    foreach(($preview['entities']??[]) as $entity=>$info){
        foreach(($info['sample']??[]) as $row){
            $sid=$row['id'] ?: md5($row['label'].json_encode($row['payload']));
            $q=db()->prepare('INSERT INTO bridge_import_selections(connection_id,entity_type,source_id,source_label,selected,payload_json) VALUES(?,?,?,?,1,?) ON DUPLICATE KEY UPDATE source_label=VALUES(source_label), payload_json=VALUES(payload_json)');
            $q->execute([(int)$connectionId,$entity,$sid,$row['label'],json_encode($row['payload'],JSON_UNESCAPED_UNICODE)]);
        }
    }
}

function ao_bridge_currency_from_source($currencyId) {
    // Kaynak fiyatlandırma currency alanı genelde para birimi ID'sidir. Kaynak currency tablosu ayrıca eşlenmemişse TRY güvenli varsayılandır.
    $v = trim((string)$currencyId);
    if (in_array(strtoupper($v), ['TRY','USD','EUR','GBP'], true)) return strtoupper($v);
    return 'TRY';
}
function ao_bridge_upsert_product_price($productId,$cycle,$price,$setupFee=0,$currency='TRY') {
    $productId=(int)$productId; if($productId<=0) return;
    $price=(float)$price; $setupFee=(float)$setupFee; if($price < 0) return;
    try {
        db()->prepare('INSERT INTO product_pricing(product_id,cycle,price,setup_fee,currency) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE price=VALUES(price), setup_fee=VALUES(setup_fee), currency=VALUES(currency)')
            ->execute([$productId,$cycle,$price,$setupFee,$currency]);
        if($cycle==='monthly' || $cycle==='onetime' || $cycle==='one_time') db()->prepare('UPDATE products SET price=?, currency=?, billing_cycle=? WHERE id=?')->execute([$price,$currency,($cycle==='onetime'?'one_time':$cycle),$productId]);
    } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_bridge_upsert_tld_price($tld,$action,$price,$currency='TRY',$registrarSlug='ahost-import') {
    $tld = ltrim(strtolower(trim((string)$tld)),'.'); if($tld==='') return;
    $price = (float)$price; if($price < 0) return;
    try { db()->exec('CREATE TABLE IF NOT EXISTS tld_pricing (id INT AUTO_INCREMENT PRIMARY KEY, tld VARCHAR(40) NOT NULL, registrar_slug VARCHAR(120) DEFAULT NULL, register_price DECIMAL(14,2) DEFAULT 0.00, renew_price DECIMAL(14,2) DEFAULT 0.00, transfer_price DECIMAL(14,2) DEFAULT 0.00, currency VARCHAR(10) DEFAULT \'USD\', is_active TINYINT(1) DEFAULT 1, UNIQUE KEY uniq_tld_reg(tld,registrar_slug)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try {
        $q=db()->prepare('SELECT id FROM tld_pricing WHERE tld=? AND registrar_slug=? LIMIT 1'); $q->execute([$tld,$registrarSlug]); $id=(int)$q->fetchColumn();
        if(!$id){ db()->prepare('INSERT INTO tld_pricing(tld,registrar_slug,currency,is_active) VALUES(?,?,?,1)')->execute([$tld,$registrarSlug,$currency]); $id=(int)db()->lastInsertId(); }
        $col = $action==='renew' ? 'renew_price' : ($action==='transfer' ? 'transfer_price' : 'register_price');
        db()->prepare('UPDATE tld_pricing SET `'.$col.'`=?, currency=?, is_active=1 WHERE id=?')->execute([$price,$currency,$id]);
    } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_bridge_source_price_cycles($row) {
    return [
        'monthly'=>['price'=>$row['monthly']??null,'setup'=>$row['msetupfee']??0],
        'quarterly'=>['price'=>$row['quarterly']??null,'setup'=>$row['qsetupfee']??0],
        'semiannually'=>['price'=>$row['semiannually']??null,'setup'=>$row['ssetupfee']??0],
        'annually'=>['price'=>$row['annually']??null,'setup'=>$row['asetupfee']??0],
        'biennially'=>['price'=>$row['biennially']??null,'setup'=>$row['bsetupfee']??0],
        'triennially'=>['price'=>$row['triennially']??null,'setup'=>$row['tsetupfee']??0],
    ];
}
function ao_bridge_live_preview($conn,$maxRows=200) {
    $pdo = ao_bridge_connect($conn); $entities = ao_bridge_entity_table_map($conn); $out=[];
    foreach($entities as $entity=>$cfg){
        $table=$cfg['table']; $exists=ao_bridge_table_exists($pdo,$table); $count=0; $sample=[];
        if($exists){
            try { $count=(int)$pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn(); } catch(Throwable $e) { $count=0; }
            try {
                if($entity==='product_pricing'){
                    $dp = ao_bridge_table($conn,'domainpricing');
                    $sql = ao_bridge_table_exists($pdo,$dp)
                        ? 'SELECT p.*, dp.extension FROM `'.$table.'` p LEFT JOIN `'.$dp.'` dp ON dp.id=p.relid ORDER BY p.id ASC LIMIT '.(int)$maxRows
                        : 'SELECT * FROM `'.$table.'` ORDER BY id ASC LIMIT '.(int)$maxRows;
                    foreach($pdo->query($sql) as $r){ $sample[]=['id'=>(string)($r['id']??md5(json_encode($r))),'label'=>ao_bridge_row_label($entity,$r),'payload'=>$r]; }
                } else {
                    foreach($pdo->query('SELECT * FROM `'.$table.'` ORDER BY id ASC LIMIT '.(int)$maxRows) as $r){ $sample[]=['id'=>(string)($r['id']??md5(json_encode($r))),'label'=>ao_bridge_row_label($entity,$r),'payload'=>$r]; }
                }
            } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        }
        $out[$entity]=['table'=>$table,'exists'=>$exists,'count'=>$count,'sample'=>$sample];
    }
    return ['ok'=>true,'message'=>'Canlı veritabanı tarandı. Aktarılacak kayıtları seçebilirsin.','entities'=>$out];
}

function ao_bridge_import_row_from_payload($connectionId,$runId,$entity,$row) {
    ao_bridge_ensure_target_schema(); $sid=(string)($row['id']??md5(json_encode($row)));
    try{
        if($entity==='customers'){
            $email=trim((string)($row['email']??'')); if($email===''){ ao_bridge_log_item($runId,'customer',$sid,ao_bridge_row_label('customers',$row),'customers',null,'import','warning','E-posta boş olduğu için müşteri atlandı.',$row); return; }
            $target=ao_bridge_map_target($connectionId,'customer',$sid,'customers') ?: ao_bridge_find_by('customers',['email'=>$email]);
            $data=['first_name'=>$row['firstname']??$row['first_name']??'', 'last_name'=>$row['lastname']??$row['last_name']??'', 'company_name'=>$row['companyname']??'', 'email'=>$email, 'password_hash'=>password_hash('ChangeMe123!',PASSWORD_DEFAULT), 'phone'=>$row['phonenumber']??$row['phone']??'', 'address1'=>$row['address1']??null, 'address2'=>$row['address2']??null, 'city'=>$row['city']??null, 'state'=>$row['state']??null, 'postcode'=>$row['postcode']??null, 'country'=>$row['country']??null, 'status'=>strtolower($row['status']??'active')==='active'?'active':'inactive', 'notes'=>'İçe aktarma kaydı. Geçici şifre: ChangeMe123!'];
            if(!$target) $target=ao_bridge_insert_dynamic('customers',$data); else ao_bridge_update_dynamic('customers',$target,$data);
            ao_bridge_save_map($connectionId,'customer',$sid,'customers',$target); ao_bridge_log_item($runId,'customer',$sid,ao_bridge_row_label('customers',$row),'customers',$target,'import','ok','Müşteri aktarıldı/eşlendi.',$row); return;
        }
        if($entity==='product_groups'){
            $slug='source-'.ao_bridge_slug($row['name']??('group-'.$sid),'group-'.$sid); $target=ao_bridge_map_target($connectionId,'product_group',$sid,'product_groups') ?: ao_bridge_find_by('product_groups',['slug'=>$slug]);
            $data=['name'=>$row['name']??('Ürün Grubu '.$sid),'slug'=>$slug,'type'=>'hosting','description'=>$row['headline']??'İçe aktarma kaydı','is_active'=>1];
            if(!$target) $target=ao_bridge_insert_dynamic('product_groups',$data); else ao_bridge_update_dynamic('product_groups',$target,$data);
            ao_bridge_save_map($connectionId,'product_group',$sid,'product_groups',$target); ao_bridge_log_item($runId,'product_group',$sid,ao_bridge_row_label('product_groups',$row),'product_groups',$target,'import','ok','Ürün grubu aktarıldı/eşlendi.',$row); return;
        }
        if($entity==='products'){
            $groupId=ao_bridge_map_target($connectionId,'product_group',$row['gid']??0,'product_groups') ?: null;
            $slug='source-product-'.$sid.'-'.ao_bridge_slug($row['name']??'product','product'); $target=ao_bridge_map_target($connectionId,'product',$sid,'products') ?: ao_bridge_find_by('products',['slug'=>$slug]);
            $type=strtolower((string)($row['type']??'')); $ptype=str_contains($type,'hosting')?'hosting':'service';
            $data=['group_id'=>$groupId,'name'=>$row['name']??('Ürün '.$sid),'slug'=>$slug,'type'=>$ptype,'description'=>$row['description']??'İçe aktarma kaydı','module_name'=>$row['servertype']??($row['module']??'manual'),'whm_package'=>$row['configoption1']??null,'auto_setup'=>'pending','is_active'=>1];
            if(!$target) $target=ao_bridge_insert_dynamic('products',$data); else ao_bridge_update_dynamic('products',$target,$data);
            ao_bridge_save_map($connectionId,'product',$sid,'products',$target); ao_bridge_log_item($runId,'product',$sid,ao_bridge_row_label('products',$row),'products',$target,'import','ok','Ürün aktarıldı/eşlendi.',$row); return;
        }
        if($entity==='product_pricing'){
            $type=strtolower((string)($row['type']??'')); $relid=(string)($row['relid']??''); $currency=ao_bridge_currency_from_source($row['currency']??'TRY');
            if(str_starts_with($type,'domain')){
                $tld=$row['extension']??$row['tld']??'';
                if($tld===''){ ao_bridge_log_item($runId,'domain_pricing',$sid,ao_bridge_row_label('product_pricing',$row),'tld_pricing',null,'import','warning','Domain fiyatı için TLD eşleşmedi. Domain Pricing sekmesini de seçin.',$row); return; }
                $action=str_contains($type,'renew')?'renew':(str_contains($type,'transfer')?'transfer':'register');
                $price=(float)($row['annually']??$row['monthly']??0); if($price<=0) $price=(float)($row['msetupfee']??0);
                ao_bridge_upsert_tld_price($tld,$action,$price,$currency,'ahost-import');
                ao_bridge_log_item($runId,'domain_pricing',$sid,'.'.ltrim($tld,'.').' '.$action,'tld_pricing',null,'import','ok','Domain uzantı fiyatı aktarıldı.',$row); return;
            }
            $productId=ao_bridge_map_target($connectionId,'product',$relid,'products');
            if(!$productId){ ao_bridge_log_item($runId,'product_pricing',$sid,ao_bridge_row_label('product_pricing',$row),'product_pricing',null,'import','warning','Ürün eşleşmediği için fiyat atlandı. Önce ürünleri seçin.',$row); return; }
            foreach(ao_bridge_source_price_cycles($row) as $cycle=>$pv){ if($pv['price']!==null && (float)$pv['price']>=0) ao_bridge_upsert_product_price($productId,$cycle,$pv['price'],$pv['setup'],$currency); }
            ao_bridge_log_item($runId,'product_pricing',$sid,ao_bridge_row_label('product_pricing',$row),'product_pricing',$productId,'import','ok','Ürün fiyatları aktarıldı.',$row); return;
        }
        if($entity==='domain_pricing'){
            $tld=ltrim((string)($row['extension']??$row['tld']??''),'.');
            if($tld===''){ ao_bridge_log_item($runId,'domain_pricing',$sid,ao_bridge_row_label('domain_pricing',$row),'tld_pricing',null,'import','warning','TLD boş olduğu için atlandı.',$row); return; }
            ao_bridge_upsert_tld_price($tld,'register',(float)($row['register_price']??0),'TRY','ahost-import');
            ao_bridge_log_item($runId,'domain_pricing',$sid,'.'.$tld,'tld_pricing',null,'import','ok','Domain uzantısı aktarıldı; fiyatlar için tblpricing domain satırlarını seçin.',$row); return;
        }
        if($entity==='servers'){
            $host=$row['hostname']??($row['ipaddress']??''); $panel=strtolower($row['type']??'whm'); $target=ao_bridge_map_target($connectionId,'server',$sid,'server_nodes') ?: ao_bridge_find_by('server_nodes',['hostname'=>$host]);
            $data=['name'=>$row['name']??('Kaynak Sistem Server '.$sid),'panel_type'=>$panel?:'whm','hostname'=>$host,'ip_address'=>$row['ipaddress']??'','username'=>$row['username']??'','api_token'=>$row['accesshash']??($row['password']??''),'status'=>'active','test_mode'=>0];
            if(!$target) $target=ao_bridge_insert_dynamic('server_nodes',$data); else ao_bridge_update_dynamic('server_nodes',$target,$data);
            ao_bridge_save_map($connectionId,'server',$sid,'server_nodes',$target); ao_bridge_log_item($runId,'server',$sid,ao_bridge_row_label('servers',$row),'server_nodes',$target,'import','ok','Sunucu aktarıldı/eşlendi.',$row); return;
        }
        if($entity==='registrars'){
            $name=$row['registrar']??($row['name']??('registrar-'.$sid)); $slug=ao_bridge_slug($name,'registrar-'.$sid); $target=ao_bridge_map_target($connectionId,'registrar',$sid,'domain_registrars') ?: ao_bridge_find_by('domain_registrars',['slug'=>$slug]);
            $data=['name'=>$name,'slug'=>$slug,'module_name'=>$name,'status'=>'active','test_mode'=>0]; if(!$target) $target=ao_bridge_insert_dynamic('domain_registrars',$data); else ao_bridge_update_dynamic('domain_registrars',$target,$data);
            ao_bridge_save_map($connectionId,'registrar',$sid,'domain_registrars',$target); ao_bridge_log_item($runId,'registrar',$sid,ao_bridge_row_label('registrars',$row),'domain_registrars',$target,'import','ok','Registrar aktarıldı/eşlendi.',$row); return;
        }
        if($entity==='services'){
            $cust=ao_bridge_map_target($connectionId,'customer',ao_bridge_source_user_id($row),'customers'); if(!$cust){ ao_bridge_log_item($runId,'service',$sid,ao_bridge_row_label('services',$row),'services',null,'import','warning','Müşteri eşleşmediği için hosting atlandı.',$row); return; }
            $prod=ao_bridge_map_target($connectionId,'product',$row['packageid']??0,'products') ?: null; $target=ao_bridge_map_target($connectionId,'service',$sid,'services');
            $data=['customer_id'=>$cust,'product_id'=>$prod,'domain'=>$row['domain']??'','status'=>ao_bridge_status_from_source($row['domainstatus']??'active'),'billing_cycle'=>$row['billingcycle']??'monthly','next_due_date'=>ao_bridge_normalize_date($row['nextduedate']??null),'auto_renew'=>1];
            if(!$target) $target=ao_bridge_insert_dynamic('services',$data); else ao_bridge_update_dynamic('services',$target,$data);
            ao_bridge_save_map($connectionId,'service',$sid,'services',$target);
            if(!empty($row['username'])){
                $serverId=ao_bridge_map_target($connectionId,'server',$row['server']??0,'server_nodes') ?: null; $serverName='İçe Aktarılan Sunucu'; $host='';
                if($serverId){ $sq=db()->prepare('SELECT * FROM server_nodes WHERE id=?'); $sq->execute([$serverId]); if($sv=$sq->fetch()){ $serverName=$sv['name']??$serverName; $host=ao_host_from_server_row($sv); } }
                if(!ao_bridge_find_by('hosting_accounts',['service_id'=>$target])) ao_bridge_insert_dynamic('hosting_accounts',['service_id'=>$target,'server_id'=>$serverId,'server_name'=>$serverName,'username'=>$row['username'],'whm_username'=>$row['username'],'panel_password'=>$row['password']??'','package_name'=>'imported','cpanel_url'=>ao_panel_url_from_host($host?:$serverName,'cpanel'),'webmail_url'=>ao_panel_url_from_host($host?:$serverName,'webmail'),'whm_url'=>ao_panel_url_from_host($host?:$serverName,'whm'),'directadmin_url'=>ao_panel_url_from_host($host?:$serverName,'directadmin'),'vps_panel_url'=>ao_panel_url_from_host($host?:$serverName,'vps')]);
            }
            ao_bridge_log_item($runId,'service',$sid,ao_bridge_row_label('services',$row),'services',$target,'import','ok','Hosting hizmeti aktarıldı/eşlendi.',$row); return;
        }
        if($entity==='domains'){
            $cust=ao_bridge_map_target($connectionId,'customer',ao_bridge_source_user_id($row),'customers'); if(!$cust){ ao_bridge_log_item($runId,'domain',$sid,ao_bridge_row_label('domains',$row),'domains',null,'import','warning','Müşteri eşleşmediği için domain atlandı.',$row); return; }
            $domain=$row['domain']??''; if($domain===''){ ao_bridge_log_item($runId,'domain',$sid,'','domains',null,'import','warning','Domain adı boş olduğu için atlandı.',$row); return; }
            $registrarId=null; if(!empty($row['registrar'])){ $slug=ao_bridge_slug($row['registrar'],'registrar'); $registrarId=ao_bridge_find_by('domain_registrars',['slug'=>$slug]); if(!$registrarId){ $registrarId=ao_bridge_insert_dynamic('domain_registrars',['name'=>$row['registrar'],'slug'=>$slug,'module_name'=>$row['registrar'],'status'=>'active']); } }
            $target=ao_bridge_map_target($connectionId,'domain',$sid,'domains') ?: ao_bridge_find_by('domains',['domain_name'=>$domain]);
            $data=['customer_id'=>$cust,'domain_name'=>$domain,'registrar'=>$row['registrar']??null,'registrar_id'=>$registrarId,'status'=>ao_bridge_status_from_source($row['status']??'active'),'registration_date'=>ao_bridge_normalize_date($row['registrationdate']??null),'expiry_date'=>ao_bridge_normalize_date($row['expirydate']??null),'next_due_date'=>ao_bridge_normalize_date($row['nextduedate']??($row['expirydate']??null)),'auto_renew'=>empty($row['donotrenew'])?1:0,'lock_status'=>1,'epp_code'=>$row['eppcode']??null,'auth_code'=>$row['eppcode']??null];
            if(!$target) $target=ao_bridge_insert_dynamic('domains',$data); else ao_bridge_update_dynamic('domains',$target,$data);
            ao_bridge_save_map($connectionId,'domain',$sid,'domains',$target); ao_bridge_log_item($runId,'domain',$sid,ao_bridge_row_label('domains',$row),'domains',$target,'import','ok','Domain aktarıldı/eşlendi.',$row); return;
        }
        if($entity==='orders'){
            $cust=ao_bridge_map_target($connectionId,'customer',ao_bridge_source_user_id($row),'customers'); if(!$cust){ ao_bridge_log_item($runId,'order',$sid,ao_bridge_row_label('orders',$row),'orders',null,'import','warning','Müşteri eşleşmediği için sipariş atlandı.',$row); return; }
            $no='AHOST-ORDER-'.$sid; $target=ao_bridge_map_target($connectionId,'order',$sid,'orders') ?: ao_bridge_find_by('orders',['order_number'=>$no]);
            $data=['customer_id'=>$cust,'order_number'=>$no,'status'=>strtolower($row['status']??'pending'),'total'=>(float)($row['amount']??0),'currency'=>'TRY','payment_method'=>'source','created_at'=>$row['date']??date('Y-m-d H:i:s')];
            if(!$target) $target=ao_bridge_insert_dynamic('orders',$data); else ao_bridge_update_dynamic('orders',$target,$data);
            ao_bridge_save_map($connectionId,'order',$sid,'orders',$target); ao_bridge_log_item($runId,'order',$sid,'Sipariş #'.$sid,'orders',$target,'import','ok','Sipariş aktarıldı/eşlendi.',$row); return;
        }
        if($entity==='invoices'){
            $cust=ao_bridge_map_target($connectionId,'customer',ao_bridge_source_user_id($row),'customers'); if(!$cust){ ao_bridge_log_item($runId,'invoice',$sid,ao_bridge_row_label('invoices',$row),'invoices',null,'import','warning','Müşteri eşleşmediği için fatura atlandı.',$row); return; }
            $no=($row['invoicenum']??'') ?: ('AHOST-'.$sid); $target=ao_bridge_map_target($connectionId,'invoice',$sid,'invoices') ?: ao_bridge_find_by('invoices',['invoice_number'=>$no]);
            $data=['customer_id'=>$cust,'invoice_number'=>$no,'status'=>strtolower($row['status']??'unpaid'),'subtotal'=>(float)($row['subtotal']??$row['total']??0),'tax'=>(float)($row['tax']??0),'total'=>(float)($row['total']??0),'currency'=>'TRY','due_date'=>ao_bridge_normalize_date($row['duedate']??null),'paid_at'=>($row['datepaid']??null)];
            if(!$target) $target=ao_bridge_insert_dynamic('invoices',$data); else ao_bridge_update_dynamic('invoices',$target,$data);
            ao_bridge_save_map($connectionId,'invoice',$sid,'invoices',$target); ao_bridge_log_item($runId,'invoice',$sid,$no,'invoices',$target,'import','ok','Fatura aktarıldı/eşlendi.',$row); return;
        }
        if($entity==='invoice_items'){
            $invoice=ao_bridge_map_target($connectionId,'invoice',$row['invoiceid']??0,'invoices'); if(!$invoice){ ao_bridge_log_item($runId,'invoice_item',$sid,ao_bridge_row_label('invoice_items',$row),'invoice_items',null,'import','warning','Fatura eşleşmediği için fatura kalemi atlandı.',$row); return; }
            $target=ao_bridge_map_target($connectionId,'invoice_item',$sid,'invoice_items');
            $data=['invoice_id'=>$invoice,'description'=>$row['description']??('Fatura Kalemi '.$sid),'amount'=>(float)($row['amount']??0),'quantity'=>1];
            if(!$target) $target=ao_bridge_insert_dynamic('invoice_items',$data); else ao_bridge_update_dynamic('invoice_items',$target,$data);
            ao_bridge_save_map($connectionId,'invoice_item',$sid,'invoice_items',$target); ao_bridge_log_item($runId,'invoice_item',$sid,$data['description'],'invoice_items',$target,'import','ok','Fatura kalemi aktarıldı/eşlendi.',$row); return;
        }
        if($entity==='tickets'){
            $cust=ao_bridge_map_target($connectionId,'customer',ao_bridge_source_user_id($row),'customers'); if(!$cust){ ao_bridge_log_item($runId,'ticket',$sid,ao_bridge_row_label('tickets',$row),'tickets',null,'import','warning','Müşteri eşleşmediği için ticket atlandı.',$row); return; }
            $target=ao_bridge_map_target($connectionId,'ticket',$sid,'tickets');
            $data=['customer_id'=>$cust,'subject'=>$row['title']??($row['subject']??('Destek Talebi '.$sid)),'department'=>'İçe Aktarım','priority'=>$row['urgency']??'medium','status'=>strtolower($row['status']??'open')];
            if(!$target) $target=ao_bridge_insert_dynamic('tickets',$data); else ao_bridge_update_dynamic('tickets',$target,$data);
            ao_bridge_save_map($connectionId,'ticket',$sid,'tickets',$target); ao_bridge_log_item($runId,'ticket',$sid,ao_bridge_row_label('tickets',$row),'tickets',$target,'import','ok','Ticket aktarıldı/eşlendi.',$row); return;
        }
        ao_bridge_log_item($runId,$entity,$sid,ao_bridge_row_label($entity,$row),null,null,'import','warning','Bu varlık için import eşlemesi tanımlı değil.',$row);
    }catch(Throwable $e){ ao_bridge_log_item($runId,$entity,$sid,ao_bridge_row_label($entity,$row),null,null,'import','error',$e->getMessage(),$row); }
}
function ao_bridge_import_selected_sql($connectionId,$selected) {
    ao_bridge_ensure_selector_schema(); ao_bridge_ensure_target_schema();
    $conn=ao_bridge_get_connection($connectionId); if(!$conn) throw new Exception('Bridge bağlantısı bulunamadı.');
    $runId=ao_bridge_create_run($connectionId,'selected_import'); $summary=[];
    foreach(ao_bridge_dependency_order() as $entity){
        $ids=array_values((array)($selected[$entity]??[])); $summary[$entity]=count($ids); if(!$ids) continue;
        $in=implode(',',array_fill(0,count($ids),'?'));
        $q=db()->prepare("SELECT * FROM bridge_import_selections WHERE connection_id=? AND entity_type=? AND source_id IN ($in) ORDER BY CAST(source_id AS UNSIGNED), id ASC");
        $q->execute(array_merge([(int)$connectionId,$entity],$ids));
        foreach($q->fetchAll() as $sel){ $row=json_decode($sel['payload_json']??'[]',true) ?: []; ao_bridge_import_row_from_payload($connectionId,$runId,$entity,$row); }
    }
    ao_bridge_finish_run($runId,'completed',$summary); return $runId;
}


if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/migration-bridge/upload-sql') {
    ao_bridge_ensure_selector_schema(); verify_csrf();
    try{
        $id=(int)($_POST['connection_id']??0);
        $name=trim($_POST['name']??'SQL Yedeği Import'); $type=trim($_POST['source_type']??'source'); $prefix=trim($_POST['table_prefix']??'tbl'); $charset=trim($_POST['source_charset']??'utf8mb4');
        if(empty($_FILES['sql_backup']['tmp_name'])) throw new Exception('Ziplenmiş SQL veya .sql dosyası seçilmedi.');
        $orig=$_FILES['sql_backup']['name']; $ext=strtolower(pathinfo($orig,PATHINFO_EXTENSION)); $dir=ao_bridge_upload_dir(); $base='bridge_'.date('Ymd_His').'_'.bin2hex(random_bytes(3));
        $stored=$dir.'/'.$base.'.'.$ext; if(!move_uploaded_file($_FILES['sql_backup']['tmp_name'],$stored)) throw new Exception('Dosya yüklenemedi.');
        $sqlPath=$stored; $sqlName=$orig;
        if($ext==='zip'){
            if(!class_exists('ZipArchive')) throw new Exception('ZipArchive PHP eklentisi aktif değil.');
            $zip=new ZipArchive(); if($zip->open($stored)!==true) throw new Exception('ZIP açılamadı.');
            $extractDir=$dir.'/'.$base; @mkdir($extractDir,0775,true); $zip->extractTo($extractDir); $zip->close();
            $files=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($extractDir)); $found='';
            foreach($files as $f){ if($f->isFile() && strtolower($f->getExtension())==='sql'){ $found=$f->getPathname(); break; } }
            if(!$found) throw new Exception('ZIP içinde .sql dosyası bulunamadı.');
            $sqlPath=$found; $sqlName=basename($found);
        } elseif($ext!=='sql') throw new Exception('Sadece .zip veya .sql yüklenebilir.');
        if($id>0){
            $q=db()->prepare('UPDATE bridge_connections SET name=?,source_type=?,source_mode="sql_file",source_sql_path=?,source_charset=?,table_prefix=?,status="uploaded" WHERE id=?');
            $q->execute([$name,$type,$sqlPath,$charset,$prefix,$id]);
        } else {
            $q=db()->prepare('INSERT INTO bridge_connections(name,source_type,source_mode,source_host,source_database,source_username,source_password,source_sql_path,source_charset,table_prefix,status) VALUES(?,?,"sql_file","sql-upload","sql-backup","file","",?,?,?,"uploaded")');
            $q->execute([$name,$type,$sqlPath,$charset,$prefix]); $id=(int)db()->lastInsertId();
        }
        db()->prepare('INSERT INTO bridge_sql_uploads(connection_id,source_type,original_name,stored_path,sql_file_name,status,created_by) VALUES(?,?,?,?,?,"uploaded",?)')->execute([$id,$type,$orig,$stored,$sqlName,(int)($_SESSION['admin_id']??0)]);
        $conn=ao_bridge_get_connection($id); $preview=ao_bridge_sql_preview($conn,100000); ao_bridge_store_selection($id,$preview);
        flash('success','SQL yedeği yüklendi ve analiz edildi. Seçim listesinden aktarılacak kayıtları işaretleyebilirsiniz.');
        redirect_to('admin/migration-bridge?edit='.$id);
    }catch(Throwable $e){ flash('error','SQL yedeği yüklenemedi: '.$e->getMessage()); redirect_to('admin/migration-bridge'); }
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/migration-bridge/import-selected') {
    ao_bridge_ensure_selector_schema(); verify_csrf();
    $id=(int)($_POST['connection_id']??0);
    try{
        $selected=[];
        foreach(($_POST['selected']??[]) as $entity=>$ids){ $selected[$entity]=array_map('strval',(array)$ids); }
        if(!$selected) throw new Exception('Aktarılacak kayıt seçilmedi.');
        $runId=ao_bridge_import_selected_sql($id,$selected);
        flash('success','Seçilen kayıtlar aktarıldı. Run ID: '.$runId);
    }catch(Throwable $e){ flash('error','Seçili import başarısız: '.$e->getMessage()); }
    redirect_to('admin/migration-bridge?edit='.$id);
}
if ($route==='admin/migration-bridge/analyze-sql') {
    ao_bridge_ensure_selector_schema(); $id=(int)($_GET['id']??0);
    try{ $conn=ao_bridge_get_connection($id); if(!$conn) throw new Exception('Bağlantı bulunamadı.'); $preview=ao_bridge_sql_preview($conn,100000); ao_bridge_store_selection($id,$preview); flash('success','SQL yedeği yeniden analiz edildi.'); }
    catch(Throwable $e){ flash('error','Analiz başarısız: '.$e->getMessage()); }
    redirect_to('admin/migration-bridge?edit='.$id);
}

if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/migration-bridge/save') {
    ao_bridge_ensure_schema(); ao_bridge_ensure_target_schema(); ao_bridge_ensure_selector_schema();
    verify_csrf();
    try{
        $id=(int)($_POST['id']??0); $name=trim($_POST['name']??'Migration Bridge'); $type=trim($_POST['source_type']??'source'); $host=trim($_POST['source_host']??''); $port=(int)($_POST['source_port']??0); $ssl=!empty($_POST['source_ssl'])?1:0; $dbn=trim($_POST['source_database']??''); $user=trim($_POST['source_username']??''); $pass=(string)($_POST['source_password']??''); $prefix=trim($_POST['table_prefix']??'tbl'); $charset=trim($_POST['source_charset']??'utf8mb4');
        if(!$name || !$host || !$dbn || !$user) throw new Exception('Ad, host, veritabanı ve kullanıcı zorunludur.');
        if($id>0){
            if($pass===''){ $q=db()->prepare('UPDATE bridge_connections SET name=?,source_type=?,source_mode="database",source_host=?,source_port=?,source_ssl=?,source_database=?,source_username=?,source_charset=?,table_prefix=?,status="ready" WHERE id=?'); $q->execute([$name,$type,$host,$port?:null,$ssl,$dbn,$user,$charset,$prefix,$id]); }
            else { $q=db()->prepare('UPDATE bridge_connections SET name=?,source_type=?,source_mode="database",source_host=?,source_port=?,source_ssl=?,source_database=?,source_username=?,source_password=?,source_charset=?,table_prefix=?,status="ready" WHERE id=?'); $q->execute([$name,$type,$host,$port?:null,$ssl,$dbn,$user,$pass,$charset,$prefix,$id]); }
        } else { $q=db()->prepare('INSERT INTO bridge_connections(name,source_type,source_mode,source_host,source_port,source_ssl,source_database,source_username,source_password,source_charset,table_prefix,status) VALUES(?,?,"database",?,?,?,?,?,?,?,?,"ready")'); $q->execute([$name,$type,$host,$port?:null,$ssl,$dbn,$user,$pass,$charset,$prefix]); }
        flash('success','Bridge bağlantısı kaydedildi.');
    }catch(Throwable $e){ flash('error','Bridge kaydedilemedi: '.$e->getMessage()); }
    redirect_to('admin/migration-bridge');
}
if ($route==='admin/migration-bridge/test') {
    ao_bridge_ensure_schema(); ao_bridge_ensure_target_schema(); ao_bridge_ensure_selector_schema();
    $id=(int)($_GET['id']??0);
    try{ $conn=ao_bridge_get_connection($id); if(!$conn) throw new Exception('Bağlantı bulunamadı.'); $test=ao_bridge_test_connection_full($conn); $msg=$test['message'].' '.json_encode($test['summary'], JSON_UNESCAPED_UNICODE); db()->prepare('UPDATE bridge_connections SET last_test_status=?,last_test_message=?,last_test_at=NOW(),status=? WHERE id=?')->execute([$test['ok']?'success':'warning',$msg,$test['ok']?'verified':'warning',$id]); flash($test['ok']?'success':'error',$msg); }
    catch(Throwable $e){ try{db()->prepare('UPDATE bridge_connections SET last_test_status="error",last_test_message=?,last_test_at=NOW() WHERE id=?')->execute([$e->getMessage(),$id]);}catch(Throwable $x){} flash('error','Bridge testi başarısız: '.$e->getMessage()); }
    redirect_to('admin/migration-bridge');
}
if ($route==='admin/migration-bridge/dry-run' || $route==='admin/migration-bridge/import') {
    ao_bridge_ensure_schema(); ao_bridge_ensure_target_schema(); ao_bridge_ensure_selector_schema();
    $id=(int)($_GET['id']??0); $mode=$route==='admin/migration-bridge/import'?'import':'dry_run';
    try{
        if($mode==='dry_run'){
            $conn=ao_bridge_get_connection($id);
            if($conn){ $preview=ao_bridge_live_preview($conn,200); ao_bridge_store_selection($id,$preview); }
        }
        $runId=ao_bridge_run_source($id,$mode);
        flash('success',($mode==='import'?'Aktarım':'Dry-run').' tamamlandı. Run ID: '.$runId.'. Seçimli import ekranı hazırlandı.');
        redirect_to('admin/migration-bridge?edit='.$id);
    }
    catch(Throwable $e){ flash('error','Bridge çalıştırılamadı: '.$e->getMessage()); redirect_to('admin/migration-bridge'); }
}




