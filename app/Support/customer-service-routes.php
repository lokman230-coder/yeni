<?php
// Customer, hosting and domain operation routes.
function ao_panel_url_from_host($host, $panel='cpanel') {
    $host = trim((string)$host);
    if ($host === '') return '#';
    if (!preg_match('#^https?://#i', $host)) $host = 'https://' . $host;
    $ports = ['cpanel'=>2083,'webmail'=>2096,'whm'=>2087,'directadmin'=>2222,'plesk'=>8443,'vps'=>0];
    $port = $ports[$panel] ?? 0;
    if ($port && !preg_match('#:[0-9]+(/|$)#', $host)) $host .= ':' . $port;
    return $host;
}
function ao_host_from_server_row($row) {
    return trim($row['hostname'] ?? '') ?: trim($row['ip_address'] ?? '') ?: trim($row['server_ip'] ?? '') ?: trim($row['server_name'] ?? '');
}
function ao_log_simple($provider,$action,$status,$message,$payload='{}') {
    try { db()->prepare('INSERT INTO api_logs(provider,action,status,message,payload) VALUES(?,?,?,?,?)')->execute([$provider,$action,$status,$message,$payload]); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}

function ao_whm_create_user_session($server, $loginUser, $service='cpaneld') {
    $host = ao_host_from_server_row($server);
    $token = trim((string)($server['api_token'] ?? ''));
    $apiUser = trim((string)($server['username'] ?? 'root')) ?: 'root';
    $loginUser = trim((string)$loginUser);
    $service = in_array($service, ['cpaneld','webmaild','whostmgrd'], true) ? $service : 'cpaneld';
    if ($host === '' || $token === '' || $loginUser === '') return ['ok'=>false,'url'=>'','message'=>'WHM SSO için hostname, API token ve kullanıcı adı zorunlu.'];
    if (!preg_match('#^https?://#i', $host)) $host = 'https://' . $host;
    if (!preg_match('#:[0-9]+(/|$)#', $host)) $host .= ':2087';
    $url = rtrim($host,'/') . '/json-api/create_user_session?api.version=1&user=' . rawurlencode($loginUser) . '&service=' . rawurlencode($service);
    $ctx = stream_context_create(['http'=>['method'=>'GET','timeout'=>18,'ignore_errors'=>true,'header'=>'Authorization: whm '.$apiUser.':'.$token."\r\nAccept: application/json\r\n"]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) return ['ok'=>false,'url'=>'','message'=>'WHM API yanıt vermedi. Host/port/firewall/API token kontrol edilmeli.'];
    $arr = json_decode($body, true);
    $sessionUrl = $arr['data']['url'] ?? $arr['data']['session_url'] ?? '';
    $ok = !empty($sessionUrl);
    $msg = $ok ? 'WHM/cPanel güvenli oturum URL üretildi.' : ('WHM API session URL üretmedi: '.mb_substr($body,0,240));
    return ['ok'=>$ok,'url'=>$sessionUrl,'message'=>$msg,'raw'=>$body];
}
function ao_panel_service_name($panel) {
    return ['cpanel'=>'cpaneld','webmail'=>'webmaild','whm'=>'whostmgrd'][$panel] ?? 'cpaneld';
}
function ao_runtime_schema_repair() {
    static $done=false; if($done) return; $done=true;
    $customerAdds = [
        'balance'=>"ALTER TABLE customers ADD COLUMN balance DECIMAL(14,2) DEFAULT 0.00 AFTER credit_balance",
        'tc_identity_no'=>"ALTER TABLE customers ADD COLUMN tc_identity_no VARCHAR(11) NULL AFTER phone",
        'identity_verified_at'=>"ALTER TABLE customers ADD COLUMN identity_verified_at DATETIME NULL AFTER identity_verified",
        'address1'=>"ALTER TABLE customers ADD COLUMN address1 VARCHAR(255) NULL AFTER phone",
        'address2'=>"ALTER TABLE customers ADD COLUMN address2 VARCHAR(255) NULL AFTER address1",
        'city'=>"ALTER TABLE customers ADD COLUMN city VARCHAR(120) NULL AFTER address2",
        'state'=>"ALTER TABLE customers ADD COLUMN state VARCHAR(120) NULL AFTER city",
        'postcode'=>"ALTER TABLE customers ADD COLUMN postcode VARCHAR(40) NULL AFTER state",
        'country'=>"ALTER TABLE customers ADD COLUMN country VARCHAR(80) NULL AFTER postcode",
        'tax_number'=>"ALTER TABLE customers ADD COLUMN tax_number VARCHAR(80) NULL AFTER country",
        'language'=>"ALTER TABLE customers ADD COLUMN language VARCHAR(20) DEFAULT 'tr' AFTER currency",
        'notes'=>"ALTER TABLE customers ADD COLUMN notes TEXT NULL AFTER language",
        'last_login_at'=>"ALTER TABLE customers ADD COLUMN last_login_at DATETIME NULL AFTER restored_at",
    ];
    try{ $cols=[]; foreach(db()->query('SHOW COLUMNS FROM customers')->fetchAll() as $c) $cols[$c['Field']]=true; foreach($customerAdds as $col=>$sql){ if(empty($cols[$col])) db()->exec($sql); } db()->exec('UPDATE customers SET balance=COALESCE(NULLIF(balance,0), credit_balance, 0) WHERE balance IS NULL OR balance=0'); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $hostingAdds = [
        'server_ip'=>"ALTER TABLE hosting_accounts ADD COLUMN server_ip VARCHAR(80) NULL AFTER server_name",
        'whm_username'=>"ALTER TABLE hosting_accounts ADD COLUMN whm_username VARCHAR(120) NULL AFTER username",
        'panel_password'=>"ALTER TABLE hosting_accounts ADD COLUMN panel_password TEXT NULL AFTER whm_username",
        'disk_mb'=>"ALTER TABLE hosting_accounts ADD COLUMN disk_mb INT DEFAULT 0 AFTER package_name",
        'disk_used_mb'=>"ALTER TABLE hosting_accounts ADD COLUMN disk_used_mb INT DEFAULT 0 AFTER disk_mb",
        'bandwidth_mb'=>"ALTER TABLE hosting_accounts ADD COLUMN bandwidth_mb INT DEFAULT 0 AFTER disk_used_mb",
        'bandwidth_used_mb'=>"ALTER TABLE hosting_accounts ADD COLUMN bandwidth_used_mb INT DEFAULT 0 AFTER bandwidth_mb",
        'mail_limit'=>"ALTER TABLE hosting_accounts ADD COLUMN mail_limit INT DEFAULT 0 AFTER bandwidth_used_mb",
        'mail_used'=>"ALTER TABLE hosting_accounts ADD COLUMN mail_used INT DEFAULT 0 AFTER mail_limit",
        'mysql_limit'=>"ALTER TABLE hosting_accounts ADD COLUMN mysql_limit INT DEFAULT 0 AFTER mail_used",
        'mysql_used'=>"ALTER TABLE hosting_accounts ADD COLUMN mysql_used INT DEFAULT 0 AFTER mysql_limit",
        'ns1'=>"ALTER TABLE hosting_accounts ADD COLUMN ns1 VARCHAR(190) NULL AFTER vps_panel_url",
        'ns2'=>"ALTER TABLE hosting_accounts ADD COLUMN ns2 VARCHAR(190) NULL AFTER ns1",
        'panel_type'=>"ALTER TABLE hosting_accounts ADD COLUMN panel_type VARCHAR(80) NULL AFTER ns2",
    ];
    try{ $cols=[]; foreach(db()->query('SHOW COLUMNS FROM hosting_accounts')->fetchAll() as $c) $cols[$c['Field']]=true; foreach($hostingAdds as $col=>$sql){ if(empty($cols[$col])) db()->exec($sql); } db()->exec('UPDATE hosting_accounts SET whm_username=COALESCE(NULLIF(whm_username,""), username), disk_mb=COALESCE(NULLIF(disk_mb,0), disk_limit,0), disk_used_mb=COALESCE(NULLIF(disk_used_mb,0), disk_used,0), bandwidth_mb=COALESCE(NULLIF(bandwidth_mb,0), bandwidth_limit,0), bandwidth_used_mb=COALESCE(NULLIF(bandwidth_used_mb,0), bandwidth_used,0)'); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
ao_runtime_schema_repair();

function ao_schema_ensure_v780() {
    static $done=false; if($done) return; $done=true;
    try {
        $cols=[]; $q=db()->query('SHOW COLUMNS FROM customers'); foreach($q->fetchAll() as $c){ $cols[$c['Field']]=true; }
        if(empty($cols['deleted_at'])) db()->exec('ALTER TABLE customers ADD COLUMN deleted_at datetime DEFAULT NULL AFTER status');
        if(empty($cols['restored_at'])) db()->exec('ALTER TABLE customers ADD COLUMN restored_at datetime DEFAULT NULL AFTER deleted_at');
    } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec('CREATE TABLE IF NOT EXISTS customer_activity_logs (id int(11) NOT NULL AUTO_INCREMENT, customer_id int(11) DEFAULT NULL, admin_id int(11) DEFAULT NULL, action varchar(120) NOT NULL, description text DEFAULT NULL, ip_address varchar(80) DEFAULT NULL, created_at timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY(id), KEY customer_id(customer_id), KEY action(action)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_customer_log($customerId,$action,$description='') {
    try { ao_schema_ensure_v780(); $admin=current_admin(); db()->prepare('INSERT INTO customer_activity_logs(customer_id,admin_id,action,description,ip_address) VALUES(?,?,?,?,?)')->execute([(int)$customerId,$admin['id']??null,$action,$description,$_SERVER['REMOTE_ADDR']??'']); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
ao_schema_ensure_v780();




// v8.1.0 Smart Commerce & Domain Pricing Engine
function ao_schema_ensure_v810() {
    static $done=false; if($done) return; $done=true;
    try { db()->exec("CREATE TABLE IF NOT EXISTS domain_pricing_rules (id int(11) NOT NULL AUTO_INCREMENT, tld varchar(40) NOT NULL, mode varchar(30) DEFAULT 'percent', markup_percent decimal(8,2) DEFAULT 30.00, markup_fixed decimal(12,2) DEFAULT 0.00, min_profit decimal(12,2) DEFAULT 0.00, currency varchar(10) DEFAULT 'USD', registrar_override varchar(140) DEFAULT NULL, is_active tinyint(1) DEFAULT 1, created_at timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY(id), UNIQUE KEY uniq_tld(tld)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS registrar_price_cache (id int(11) NOT NULL AUTO_INCREMENT, registrar_slug varchar(140) NOT NULL, tld varchar(40) NOT NULL, action varchar(40) DEFAULT 'register', cost decimal(12,4) DEFAULT 0.0000, currency varchar(10) DEFAULT 'USD', source varchar(40) DEFAULT 'manual', raw_response longtext DEFAULT NULL, last_checked_at datetime DEFAULT NULL, created_at timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY(id), UNIQUE KEY uniq_reg_tld_action(registrar_slug,tld,action)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS payment_fee_rules (id int(11) NOT NULL AUTO_INCREMENT, gateway varchar(120) NOT NULL, label varchar(160) NOT NULL, fee_percent decimal(8,3) DEFAULT 0.000, fee_fixed decimal(12,4) DEFAULT 0.0000, currency varchar(10) DEFAULT 'TRY', payer_mode varchar(30) DEFAULT 'customer', is_active tinyint(1) DEFAULT 1, created_at timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY(id), UNIQUE KEY uniq_gateway(gateway)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS domain_order_routes (id int(11) NOT NULL AUTO_INCREMENT, order_id int(11) DEFAULT NULL, domain varchar(190) NOT NULL, tld varchar(40) NOT NULL, selected_registrar varchar(140) NOT NULL, registrar_cost decimal(12,4) DEFAULT 0.0000, sale_price decimal(12,4) DEFAULT 0.0000, currency varchar(10) DEFAULT 'USD', reason text DEFAULT NULL, created_at timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY(id), KEY domain(domain), KEY order_id(order_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { $c=(int)db()->query("SELECT COUNT(*) FROM domain_pricing_rules")->fetchColumn(); if($c===0){ db()->exec("INSERT INTO domain_pricing_rules(tld,mode,markup_percent,markup_fixed,min_profit,currency,registrar_override,is_active) VALUES ('.com','percent',30,0,3,'USD',NULL,1),('.net','percent',30,0,3,'USD',NULL,1),('.org','percent',30,0,3,'USD',NULL,1),('.com.tr','percent',35,0,2,'USD',NULL,1)"); } } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { $c=(int)db()->query("SELECT COUNT(*) FROM payment_fee_rules")->fetchColumn(); if($c===0){ db()->exec("INSERT INTO payment_fee_rules(gateway,label,fee_percent,fee_fixed,currency,payer_mode,is_active) VALUES ('paytr','PayTR Kredi Kartı',2.990,0,'TRY','customer',1),('iyzico','İyzico Kredi Kartı',3.250,0,'TRY','customer',1),('stripe','Stripe',3.490,0.49,'USD','customer',1),('manual','Havale/EFT',0,0,'TRY','company',1)"); } } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
ao_schema_ensure_v810();
function ao_money_round($v){ return round((float)$v, 2); }
function ao_pricing_rule_for_tld($tld){
    ao_schema_ensure_v810(); $tld='.'.ltrim(strtolower((string)$tld),'.');
    try { $q=db()->prepare('SELECT * FROM domain_pricing_rules WHERE tld=? AND is_active=1 LIMIT 1'); $q->execute([$tld]); $r=$q->fetch(); if($r) return $r; } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return ['tld'=>$tld,'mode'=>'percent','markup_percent'=>30,'markup_fixed'=>0,'min_profit'=>3,'currency'=>'USD','registrar_override'=>null];
}
function ao_cached_registrar_cost($registrar,$tld,$action='register'){
    ao_schema_ensure_v810(); $tld='.'.ltrim(strtolower((string)$tld),'.');
    try { $q=db()->prepare('SELECT * FROM registrar_price_cache WHERE registrar_slug=? AND tld=? AND action=? LIMIT 1'); $q->execute([$registrar,$tld,$action]); if($r=$q->fetch()) return $r; } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { $q=db()->prepare('SELECT * FROM tld_pricing WHERE registrar_slug=? AND tld=? AND is_active=1 LIMIT 1'); $q->execute([$registrar,$tld]); if($p=$q->fetch()){ $cost=(float)($action==='renew'?$p['renew_price']:($action==='transfer'?$p['transfer_price']:$p['register_price'])); return ['registrar_slug'=>$registrar,'tld'=>$tld,'action'=>$action,'cost'=>$cost,'currency'=>$p['currency'] ?: 'TRY','source'=>'tld_pricing']; } } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return null;
}
function ao_extract_price_from_registrar_response($body){
    $arr = json_decode((string)$body,true); if(!is_array($arr)) $arr = ao_json_xml_to_array((string)$body);
    $v = ao_find_deep($arr, ['Price','price','Cost','cost','Amount','amount','RegisterPrice','registerPrice']);
    if($v!==null && is_numeric($v)) return (float)$v;
    return 0.0;
}
function ao_registrar_cost_live_or_cache($bundle,$domain,$action='register'){
    $reg=$bundle['registrar']['slug'] ?? ''; $tld=ao_domain_tld($domain);
    $cached=ao_cached_registrar_cost($reg,$tld,$action);
    $cost=$cached ? (float)$cached['cost'] : 0.0; $currency=$cached['currency'] ?? 'USD'; $source=$cached['source'] ?? 'cache';
    // Try live check. Many registrars include price in availability response; if not, cache/manual stays in use.
    try { $api=ao_registrar_api_call($bundle, $action==='register'?'check':$action, $domain); if(!empty($api['ok'])){ $p=ao_extract_price_from_registrar_response($api['body'] ?? ''); if($p>0){ $cost=$p; $source='registrar_api'; try{ db()->prepare('INSERT INTO registrar_price_cache(registrar_slug,tld,action,cost,currency,source,raw_response,last_checked_at) VALUES(?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE cost=VALUES(cost),currency=VALUES(currency),source=VALUES(source),raw_response=VALUES(raw_response),last_checked_at=NOW()')->execute([$reg,$tld,$action,$cost,$currency,$source,substr((string)($api['body']??''),0,4000)]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } } } } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return ['registrar_slug'=>$reg,'tld'=>$tld,'action'=>$action,'cost'=>$cost,'currency'=>$currency,'source'=>$source];
}
function ao_smart_domain_quote($domain,$action='register'){
    ao_schema_ensure_v810(); $domain=ahost_domain_clean($domain); $tld=ao_domain_tld($domain); $rule=ao_pricing_rule_for_tld($tld); $quotes=[];
    $override=trim((string)($rule['registrar_override'] ?? ''));
    try { $sql='SELECT * FROM domain_registrars WHERE status="active"'; $params=[]; if($override!==''){ $sql.=' AND slug=?'; $params[]=$override; } $sql.=' ORDER BY priority ASC, name ASC'; $q=db()->prepare($sql); $q->execute($params); $regs=$q->fetchAll(); } catch(Throwable $e) { $regs=[]; }
    foreach($regs as $r){ $bundle=ao_registrar_bundle_by_id((int)$r['id']); if(!$bundle) continue; $c=ao_registrar_cost_live_or_cache($bundle,$domain,$action); if(($c['cost'] ?? 0)>0) $quotes[]=$c; }
    if(!$quotes){ $fallback=ao_tld_renew_price($domain,'domainnameapi'); if($fallback<=0) $fallback=15; $quotes[]=['registrar_slug'=>'domainnameapi','tld'=>$tld,'action'=>$action,'cost'=>$fallback,'currency'=>$rule['currency'] ?? 'USD','source'=>'fallback']; }
    usort($quotes, fn($a,$b)=>($a['cost']<=>$b['cost'])); $best=$quotes[0]; $cost=(float)$best['cost'];
    $percent=(float)($rule['markup_percent'] ?? 0); $fixed=(float)($rule['markup_fixed'] ?? 0); $min=(float)($rule['min_profit'] ?? 0);
    $sale = $cost + $fixed + ($cost*$percent/100); if(($sale-$cost)<$min) $sale=$cost+$min;
    return ['domain'=>$domain,'tld'=>$tld,'action'=>$action,'selected_registrar'=>$best['registrar_slug'],'registrar_cost'=>ao_money_round($cost),'sale_price'=>ao_money_round($sale),'profit'=>ao_money_round($sale-$cost),'currency'=>$best['currency'] ?: ($rule['currency'] ?? 'USD'),'source'=>$best['source'],'all_quotes'=>$quotes,'rule'=>$rule];
}
// v9.3.0 Dynamic Payment Commission Engine
function ao_schema_ensure_v900() {
    static $done=false; if($done) return; $done=true; ao_schema_ensure_v810();
    $cols=[]; try{ $q=db()->query('SHOW COLUMNS FROM payment_fee_rules'); foreach($q->fetchAll() as $c){ $cols[$c['Field']]=true; } }catch(Throwable $e){ $cols=[]; }
    $adds=[
        'rate_source'=>"ALTER TABLE payment_fee_rules ADD COLUMN rate_source varchar(30) DEFAULT 'manual' AFTER currency",
        'api_enabled'=>"ALTER TABLE payment_fee_rules ADD COLUMN api_enabled tinyint(1) DEFAULT 0 AFTER rate_source",
        'api_endpoint'=>"ALTER TABLE payment_fee_rules ADD COLUMN api_endpoint varchar(255) DEFAULT NULL AFTER api_enabled",
        'api_auth_json'=>"ALTER TABLE payment_fee_rules ADD COLUMN api_auth_json longtext DEFAULT NULL AFTER api_endpoint",
        'last_known_fee_percent'=>"ALTER TABLE payment_fee_rules ADD COLUMN last_known_fee_percent decimal(8,3) DEFAULT 0.000 AFTER fee_percent",
        'last_known_fee_fixed'=>"ALTER TABLE payment_fee_rules ADD COLUMN last_known_fee_fixed decimal(12,4) DEFAULT 0.0000 AFTER fee_fixed",
        'invoice_line_label'=>"ALTER TABLE payment_fee_rules ADD COLUMN invoice_line_label varchar(160) DEFAULT 'Kart İşlem Komisyonu' AFTER label",
        'last_synced_at'=>"ALTER TABLE payment_fee_rules ADD COLUMN last_synced_at datetime DEFAULT NULL AFTER api_auth_json",
        'last_sync_status'=>"ALTER TABLE payment_fee_rules ADD COLUMN last_sync_status varchar(40) DEFAULT NULL AFTER last_synced_at",
        'last_sync_message'=>"ALTER TABLE payment_fee_rules ADD COLUMN last_sync_message text DEFAULT NULL AFTER last_sync_status",
    ];
    foreach($adds as $col=>$sql){ if(empty($cols[$col])){ try{ db()->exec($sql); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } } }
    try{ db()->exec("CREATE TABLE IF NOT EXISTS payment_fee_sync_logs (id int(11) NOT NULL AUTO_INCREMENT, gateway varchar(120) NOT NULL, status varchar(40) NOT NULL, message text DEFAULT NULL, old_percent decimal(8,3) DEFAULT NULL, new_percent decimal(8,3) DEFAULT NULL, old_fixed decimal(12,4) DEFAULT NULL, new_fixed decimal(12,4) DEFAULT NULL, raw_response longtext DEFAULT NULL, created_at timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY(id), KEY gateway(gateway), KEY status(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("UPDATE payment_fee_rules SET payer_mode='customer', invoice_line_label=COALESCE(NULLIF(invoice_line_label,''),'Kart İşlem Komisyonu'), last_known_fee_percent=IFNULL(NULLIF(last_known_fee_percent,0),fee_percent), last_known_fee_fixed=IFNULL(last_known_fee_fixed,fee_fixed) WHERE 1"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
ao_schema_ensure_v900();
function ao_payment_gateway_rule($gateway='paytr'){
    ao_schema_ensure_v900(); $gateway=trim((string)$gateway) ?: 'paytr';
    try{ $q=db()->prepare('SELECT * FROM payment_fee_rules WHERE gateway=? AND is_active=1 LIMIT 1'); $q->execute([$gateway]); if($r=$q->fetch()) return $r; }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return ['gateway'=>$gateway,'label'=>$gateway,'invoice_line_label'=>'Kart İşlem Komisyonu','fee_percent'=>0,'fee_fixed'=>0,'last_known_fee_percent'=>0,'last_known_fee_fixed'=>0,'currency'=>'TRY','rate_source'=>'manual','api_enabled'=>0];
}
function ao_extract_payment_rate_from_response($body){
    $arr=json_decode((string)$body,true); if(!is_array($arr)) $arr=ao_json_xml_to_array((string)$body); if(!is_array($arr)) return null;
    $percent=ao_find_deep($arr,['fee_percent','commission_percent','commissionRate','rate','percent','cardCommissionPercent']);
    $fixed=ao_find_deep($arr,['fee_fixed','fixed_fee','fixed','commissionFixed','cardCommissionFixed']);
    $currency=ao_find_deep($arr,['currency','Currency']);
    if($percent===null && $fixed===null) return null;
    return ['fee_percent'=>is_numeric($percent)?(float)$percent:0.0,'fee_fixed'=>is_numeric($fixed)?(float)$fixed:0.0,'currency'=>$currency ?: null];
}
function ao_payment_commission_sync($gateway,$force=false){
    ao_schema_ensure_v900(); $r=ao_payment_gateway_rule($gateway); $oldP=(float)($r['fee_percent']??0); $oldF=(float)($r['fee_fixed']??0);
    if(empty($r['api_enabled']) || trim((string)($r['api_endpoint']??''))==='') return ['ok'=>false,'message'=>'API komisyon çekme kapalı; manuel/son bilinen oran kullanılıyor.'];
    if(!$force && !empty($r['last_synced_at']) && strtotime($r['last_synced_at'])>time()-3600) return ['ok'=>true,'message'=>'Son senkronizasyon güncel.'];
    $body=''; $status='error'; $msg='';
    try{
        $headers=['Accept: application/json']; $auth=json_decode((string)($r['api_auth_json']??'{}'),true) ?: [];
        if(!empty($auth['bearer'])) $headers[]='Authorization: Bearer '.$auth['bearer'];
        $ctx=stream_context_create(['http'=>['method'=>'GET','timeout'=>15,'ignore_errors'=>true,'header'=>implode("\r\n",$headers)]]);
        $body=@file_get_contents($r['api_endpoint'],false,$ctx);
        if($body===false) throw new Exception('Komisyon API yanıtı alınamadı.');
        $rate=ao_extract_payment_rate_from_response($body);
        if(!$rate) throw new Exception('API yanıtında komisyon oranı bulunamadı.');
        $newP=(float)$rate['fee_percent']; $newF=(float)$rate['fee_fixed']; $cur=$rate['currency'] ?: ($r['currency']??'TRY');
        db()->prepare('UPDATE payment_fee_rules SET fee_percent=?, fee_fixed=?, last_known_fee_percent=?, last_known_fee_fixed=?, currency=?, rate_source="api", last_synced_at=NOW(), last_sync_status="success", last_sync_message=? WHERE gateway=?')->execute([$newP,$newF,$newP,$newF,$cur,'API oranı güncellendi.',$gateway]);
        $status='success'; $msg='API oranı güncellendi.';
    }catch(Throwable $e){
        $msg=$e->getMessage();
        try{ db()->prepare('UPDATE payment_fee_rules SET fee_percent=COALESCE(NULLIF(last_known_fee_percent,0),fee_percent), fee_fixed=COALESCE(last_known_fee_fixed,fee_fixed), last_synced_at=NOW(), last_sync_status="error", last_sync_message=? WHERE gateway=?')->execute([$msg,$gateway]); }catch(Throwable $x){}
    }
    try{ db()->prepare('INSERT INTO payment_fee_sync_logs(gateway,status,message,old_percent,new_percent,old_fixed,new_fixed,raw_response) VALUES(?,?,?,?,?,?,?,?)')->execute([$gateway,$status,$msg,$oldP,(float)(ao_payment_gateway_rule($gateway)['fee_percent']??0),$oldF,(float)(ao_payment_gateway_rule($gateway)['fee_fixed']??0),substr((string)$body,0,5000)]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return ['ok'=>$status==='success','message'=>$msg];
}
function ao_payment_fee_quote($amount,$gateway='paytr'){
    ao_schema_ensure_v900(); $amount=(float)$amount; $r=ao_payment_gateway_rule($gateway);
    if(!empty($r['api_enabled'])) ao_payment_commission_sync($gateway,false);
    $r=ao_payment_gateway_rule($gateway);
    $fee=($amount*(float)$r['fee_percent']/100)+(float)$r['fee_fixed'];
    return ['gateway'=>$gateway,'label'=>$r['label'] ?? $gateway,'line_label'=>$r['invoice_line_label'] ?? 'Kart İşlem Komisyonu','amount'=>ao_money_round($amount),'fee'=>ao_money_round($fee),'customer_total'=>ao_money_round($amount+$fee),'company_net'=>ao_money_round($amount),'payer_mode'=>'customer','currency'=>$r['currency'] ?? 'TRY','rate_source'=>$r['rate_source'] ?? 'manual'];
}

// v7.5.4 Domain Production Fix - renewal request, registrar EPP, availability search
function ao_domain_row($domainId, $customerId = null) {
    $sql = 'SELECT * FROM domains WHERE id=?'; $params = [(int)$domainId];
    if ($customerId !== null) { $sql .= ' AND customer_id=?'; $params[] = (int)$customerId; }
    $sql .= ' LIMIT 1';
    $q = db()->prepare($sql); $q->execute($params);
    return $q->fetch() ?: null;
}
function ao_domain_tld($domain) {
    $parts = explode('.', strtolower(trim((string)$domain)));
    if (count($parts) >= 3 && in_array(end($parts), ['tr'], true)) return '.' . $parts[count($parts)-2] . '.' . $parts[count($parts)-1];
    return count($parts) > 1 ? '.' . end($parts) : '';
}
function ao_tld_renew_price($domain, $registrar='domainnameapi') {
    try {
        $tld = ao_domain_tld($domain);
        $q = db()->prepare('SELECT renew_price FROM tld_pricing WHERE tld=? AND is_active=1 ORDER BY registrar_slug=? DESC LIMIT 1');
        $q->execute([$tld, $registrar]);
        $v = $q->fetchColumn();
        return $v === false ? 0.0 : (float)$v;
    } catch (Throwable $e) { return 0.0; }
}
function ao_registrar_bundle($slug) {
    $slug = strtolower(trim((string)$slug));
    $q = db()->prepare('SELECT * FROM domain_registrars WHERE slug=? OR module_name=? LIMIT 1'); $q->execute([$slug,$slug]);
    $reg = $q->fetch(); if (!$reg) return null;
    $cfg = [];
    $c = db()->prepare('SELECT config_key,config_value FROM registrar_configs WHERE registrar_id=?'); $c->execute([$reg['id']]);
    foreach ($c->fetchAll() as $row) $cfg[$row['config_key']] = $row['config_value'];
    return ['registrar'=>$reg,'config'=>$cfg];
}
function ao_domain_registrar_bundle($domainRow) {
    $slug = $domainRow['registrar'] ?? 'domainnameapi';
    $bundle = ao_registrar_bundle($slug);
    if (!$bundle && stripos($slug, 'domainnameapi') !== false) $bundle = ao_registrar_bundle('domainnameapi');
    return $bundle ?: ao_registrar_bundle('domainnameapi');
}
function ao_domain_api_post($url, $payload, $headers=[]) {
    $headers = array_merge(['Content-Type: application/json','Accept: application/json'], $headers);
    $opts = ['http'=>['method'=>'POST','timeout'=>20,'ignore_errors'=>true,'header'=>implode("\r\n", $headers),'content'=>json_encode($payload, JSON_UNESCAPED_UNICODE)]];
    $ctx = stream_context_create($opts);
    $body = @file_get_contents($url, false, $ctx);
    $code = 0;
    $responseHeaders=function_exists('http_get_last_response_headers') ? (http_get_last_response_headers() ?: []) : [];
    foreach ($responseHeaders as $h) if (preg_match('/^HTTP\/\S+\s+(\d+)/', $h, $m)) { $code=(int)$m[1]; break; }
    return ['ok'=>$body!==false && $code>=200 && $code<300, 'code'=>$code, 'body'=>$body===false?'':$body];
}

function ao_registrar_bundle_by_id($id) {
    $id = (int)$id;
    if ($id <= 0) return null;
    $q = db()->prepare('SELECT * FROM domain_registrars WHERE id=? LIMIT 1');
    $q->execute([$id]);
    $reg = $q->fetch();
    if (!$reg) return null;
    $cfg = [];
    $c = db()->prepare('SELECT config_key,config_value FROM registrar_configs WHERE registrar_id=?');
    $c->execute([$reg['id']]);
    foreach ($c->fetchAll() as $row) $cfg[$row['config_key']] = $row['config_value'];
    return ['registrar'=>$reg,'config'=>$cfg];
}
function ao_first_nonempty($array, $keys, $default='') {
    foreach ($keys as $k) if (isset($array[$k]) && $array[$k] !== '' && $array[$k] !== null) return $array[$k];
    return $default;
}
function ao_json_xml_to_array($body) {
    $body = (string)$body;
    $json = json_decode($body, true);
    if (is_array($json)) return $json;
    $xml = @simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($xml) return json_decode(json_encode($xml), true) ?: [];
    return [];
}
function ao_registrar_endpoint($bundle, $action) {
    $cfg = $bundle['config'] ?? [];
    $endpoint = trim((string)($cfg[$action.'_endpoint'] ?? ''));
    if ($endpoint !== '') return $endpoint;
    $fallbacks = [
        'test' => ['check_endpoint','whois_endpoint','api_endpoint'],
        'check' => ['check_endpoint','api_endpoint'],
        'whois' => ['whois_endpoint','api_endpoint'],
        'epp' => ['epp_endpoint','api_endpoint'],
        'renew' => ['renew_endpoint','api_endpoint'],
        'dns' => ['dns_endpoint','api_endpoint'],
        'nameserver' => ['ns_endpoint','nameserver_endpoint','api_endpoint'],
        'lock' => ['lock_endpoint','api_endpoint'],
    ];
    foreach (($fallbacks[$action] ?? ['api_endpoint']) as $k) {
        if (!empty($cfg[$k])) return trim((string)$cfg[$k]);
    }
    return '';
}
function ao_registrar_payload($bundle, $action, $domain='', $extra=[]) {
    $cfg = $bundle['config'] ?? [];
    $reg = $bundle['registrar'] ?? [];
    $domain = ahost_domain_clean($domain ?: ($cfg['test_domain'] ?? 'example.com'));
    $payload = array_merge([
        'action' => $action,
        'command' => $action,
        'domain' => $domain,
        'sld' => preg_replace('/\.[^.]+$/','',$domain),
        'tld' => ao_domain_tld($domain),
        'username' => $cfg['api_username'] ?? '',
        'userName' => $cfg['api_username'] ?? '',
        'api_username' => $cfg['api_username'] ?? '',
        'password' => $cfg['api_password'] ?? '',
        'api_password' => $cfg['api_password'] ?? '',
        'api_key' => $cfg['api_key'] ?? ($cfg['api_username'] ?? ''),
        'api_secret' => $cfg['api_secret'] ?? ($cfg['api_password'] ?? ''),
        'token' => $cfg['token'] ?? '',
        'reseller_id' => $cfg['reseller_id'] ?? '',
        'registrar' => $reg['slug'] ?? '',
        'auth_mode' => $cfg['auth_mode'] ?? 'userpass',
    ], $extra);
    return $payload;
}
function ao_registrar_api_call($bundle, $action, $domain='', $extra=[]) {
    if (!$bundle) return ['ok'=>false,'code'=>0,'body'=>'','message'=>'Registrar yapılandırması bulunamadı.'];
    if (ao_is_domainnameapi_bundle($bundle)) {
        $r=ao_dna_action_call($bundle, $action, $domain, $extra);
        if(!empty($r['ok']) && $domain && in_array($action,['renew','transfer','nameserver','lock','epp'],true)){
            try{ $q=db()->prepare('SELECT d.*,c.first_name,c.last_name,c.email,c.phone FROM domains d LEFT JOIN customers c ON c.id=d.customer_id WHERE d.domain_name=? LIMIT 1'); $q->execute([ahost_domain_clean($domain)]); if($row=$q->fetch()){ ao_notify_event($action==='renew'?'domain_renewed':'registrar_operation',(int)($row['customer_id']??0),['domain'=>$row['domain_name']??$domain,'operation'=>$action,'status'=>'success','message'=>$r['message']??'','expiry_date'=>$row['expiry_date']??'']); } }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        }
        return $r;
    }
    $reg = $bundle['registrar'];
    $cfg = $bundle['config'];
    $endpoint = ao_registrar_endpoint($bundle, $action === 'test' ? 'test' : $action);
    if (!filter_var($endpoint, FILTER_VALIDATE_URL)) return ['ok'=>false,'code'=>0,'body'=>'','message'=>'Registrar endpoint boş veya geçersiz.'];
    $payload = ao_registrar_payload($bundle, $action, $domain, $extra);
    $headers = [];
    $authMode = $cfg['auth_mode'] ?? 'userpass';
    if ($authMode === 'token' && !empty($cfg['token'])) $headers[] = 'Authorization: Bearer '.trim((string)$cfg['token']);
    if ($authMode === 'apikey' && !empty($cfg['api_key'])) $headers[] = 'X-API-Key: '.trim((string)$cfg['api_key']);
    $r = ao_domain_api_post($endpoint, $payload, $headers);
    $status = $r['ok'] ? 'success' : 'error';
    ao_log_simple($reg['slug'] ?? 'registrar', 'registrar-'.$action, $status, 'HTTP '.$r['code'].' endpoint='.$endpoint, json_encode(['domain'=>$payload['domain']??'', 'response'=>substr((string)$r['body'],0,700)], JSON_UNESCAPED_UNICODE));
    $r['message'] = $r['ok'] ? 'Registrar API yanıt verdi.' : 'Registrar API yanıtı başarısız veya endpoint ulaşılamadı.';
    if(!empty($r['ok']) && $domain && in_array($action,['renew','transfer','nameserver','lock','epp'],true)){
        try{ $q=db()->prepare('SELECT d.*,c.first_name,c.last_name,c.email,c.phone FROM domains d LEFT JOIN customers c ON c.id=d.customer_id WHERE d.domain_name=? LIMIT 1'); $q->execute([ahost_domain_clean($domain)]); if($row=$q->fetch()){ ao_notify_event($action==='renew'?'domain_renewed':'registrar_operation',(int)($row['customer_id']??0),['domain'=>$row['domain_name']??$domain,'operation'=>$action,'status'=>'success','message'=>$r['message']??'','expiry_date'=>$row['expiry_date']??'']); } }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    }
    return $r;
}
function ao_extract_availability($body) {
    $a = ao_json_xml_to_array($body);
    if (is_array($a)) {
        $v = ao_find_deep($a, ['Status','Availability','Available','available','IsAvailable']);
        if ($v !== null) {
            $sv = strtolower((string)$v);
            if (in_array($sv, ['unavailable','notavailable','not_available','registered','false','0','no','taken'], true)) return false;
            if (in_array($sv, ['available','avail','true','1','yes','free'], true)) return true;
        }
    }
    $status = strtolower((string)(ao_find_deep($a, ['Status','status','Availability','availability']) ?? ''));
    $reason = strtolower((string)(ao_find_deep($a, ['Reason','reason']) ?? ''));
    if ($status !== '') {
        if (str_contains($status, 'unavailable') || str_contains($status, 'notavailable') || str_contains($status, 'not_available') || str_contains($status, 'registered') || str_contains($status, 'taken') || $status === '0' || $status === 'false') return false;
        if (str_contains($status, 'available') || str_contains($status, 'free') || $status === '1' || $status === 'true') return true;
    }
    if ($reason && preg_match('/unavailable|notavailable|not_available|registered|not available|taken|exists|kayıtlı|kayitli/', $reason)) return false;
    if ($reason && preg_match('/available|free|müsait|musait/', $reason)) return true;
    $text = strtolower((string)$body);
    if (preg_match('/unavailable|notavailable|not_available|registered|not available|taken|domain exists|kayıtlı|kayitli/', $text)) return false;
    if (preg_match('/available|not registered|is free|müsait|musait/', $text)) return true;
    return null;
}
function ao_extract_whois_rows_from_response($body) {
    $a = ao_json_xml_to_array($body);
    if (!$a) return [];
    return [
        'Registrar' => ao_find_deep($a, ['Registrar','RegistrarName','SponsoringRegistrar','Company']) ?: '',
        'Kayıt Tarihi' => ao_find_deep($a, ['CreationDate','CreateDate','Created','RegistrationDate','CreatedDate']) ?: '',
        'Son Güncelleme' => ao_find_deep($a, ['UpdatedDate','UpdateDate','ModifiedDate','LastUpdate']) ?: '',
        'Bitiş Tarihi' => ao_find_deep($a, ['ExpirationDate','ExpiryDate','ExpireDate','Expires','Expiration']) ?: '',
        'Domain Durumu' => ao_find_deep($a, ['Status','DomainStatus','State']) ?: '',
        'Registrar Lock' => ao_find_deep($a, ['TheftProtectionLock','LockStatus','TransferLock','RegistrarLock']) ?: '',
        'Oto Yenileme' => ao_find_deep($a, ['AutoRenew','AutoRenewStatus','RenewalMode']) ?: '',
        'DNSSEC' => ao_find_deep($a, ['DnsSec','DNSSEC','Dnssec']) ?: '',
        'IANA ID' => ao_find_deep($a, ['IanaId','IANAID','RegistrarIanaId']) ?: '',
    ];
}
function ao_extract_epp_from_response($body) {
    $j = json_decode((string)$body, true);
    if (is_array($j)) {
        $v = ao_find_deep($j, ['AuthCode','authCode','EppCode','eppcode','epp_code','epp','TransferCode','transferCode','Auth']);
        if ($v !== null && $v !== '') return (string)$v;
    }
    if (preg_match('/<(?:AuthCode|authCode|EppCode|eppcode|epp_code|epp|transfer_code|Auth)>\s*([^<]+)\s*<\//i', (string)$body, $m)) return trim($m[1]);
    return '';
}
function ao_domain_generate_epp($domainRow) {
    $bundle = ao_domain_registrar_bundle($domainRow);
    $domain = $domainRow['domain_name'] ?? '';
    if (!$bundle) return ['ok'=>false,'message'=>'Registrar yapılandırması bulunamadı.'];
    $reg = $bundle['registrar']; $cfg = $bundle['config'];
    $r = ao_registrar_api_call($bundle, 'epp', $domain);
    $epp = ao_extract_epp_from_response($r['body']);
    if ($r['ok'] && $epp) { db()->prepare('UPDATE domains SET epp_code=? WHERE id=?')->execute([$epp,$domainRow['id']]); return ['ok'=>true,'epp'=>$epp,'message'=>'EPP kodu registrar API üzerinden alındı.']; }
    return ['ok'=>false,'message'=>'Registrar API EPP kodu döndürmedi. Registrar bağlantı testi, endpoint ve API loglarını kontrol edin.'];
}
function ao_domain_create_renewal_order($domainRow, $years=1, $payment='pending') {
    $years = max(1, (int)$years); $domain = $domainRow['domain_name']; $customerId = (int)$domainRow['customer_id'];
    $price = ao_tld_renew_price($domain, $domainRow['registrar'] ?: 'domainnameapi'); if ($price <= 0) $price = 499.00;
    $total = $price * $years; $orderNo = ao_generate_number(admin_setting('order_prefix','AO'), 'orders', 'order_number');
    db()->prepare('INSERT INTO orders(customer_id,order_number,status,total,payment_method,fraud_score,provision_status,notes) VALUES(?,? ,"pending", ?, ?, 0, "pending", ?)')->execute([$customerId,$orderNo,$total,$payment,'Domain yenileme talebi: '.$domain.' / '.$years.' yıl']);
    $orderId = (int)db()->lastInsertId();
    db()->prepare('INSERT INTO order_items(order_id,product_id,item_type,item_name,domain,billing_cycle,price,module_name) VALUES(?,NULL,"domain-renewal",?,?,?,?,"domainnameapi")')->execute([$orderId,'Domain Yenileme '.$years.' Yıl',$domain,'annually',$total]);
    $invoiceId = function_exists('ao_create_invoice_for_order') ? ao_create_invoice_for_order($orderId) : 0;
    ao_log_simple('domain','renewal-request','success','Domain yenileme için sipariş/fatura oluşturuldu.', json_encode(['domain'=>$domain,'order_id'=>$orderId,'invoice_id'=>$invoiceId], JSON_UNESCAPED_UNICODE));
    try { ao_notify_event('order_created',$customerId,['order_number'=>$orderNo,'domain'=>$domain,'amount'=>number_format($total,2,',','.'),'customer_name'=>'']); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return ['order_id'=>$orderId,'invoice_id'=>$invoiceId,'total'=>$total];
}
function ao_domain_availability($domain) {
    $domain = ahost_domain_clean($domain);
    if (!ahost_domain_valid($domain)) return ['ok'=>false,'domain'=>$domain,'available'=>false,'message'=>'Geçersiz domain.'];
    try { $q=db()->prepare('SELECT id,status FROM domains WHERE domain_name=? LIMIT 1'); $q->execute([$domain]); if ($r=$q->fetch()) return ['ok'=>true,'domain'=>$domain,'available'=>false,'source'=>'local','message'=>'Bu domain zaten kayıtlı görünüyor.']; } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try {
        $bundle = ao_registrar_bundle('domainnameapi');
        if ($bundle && (($bundle['registrar']['status'] ?? '') === 'active')) {
            $r = ao_registrar_api_call($bundle, 'check', $domain);
            if ($r['ok']) {
                $available = ao_extract_availability($r['body']);
                if ($available !== null) return ['ok'=>true,'domain'=>$domain,'available'=>$available,'source'=>'registrar:domainnameapi','message'=>$available?'Bu domain kayıt için uygun görünüyor.':'Bu domain zaten kayıtlı görünüyor.'];
            }
        }
    } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $hasDns = false;
    if (function_exists('dns_get_record')) { $rec = @dns_get_record($domain, DNS_A + DNS_AAAA + DNS_MX + DNS_NS); $hasDns = is_array($rec) && count($rec)>0; }
    return ['ok'=>true,'domain'=>$domain,'available'=>!$hasDns,'source'=>'dns-fallback','message'=>$hasDns?'Bu domain zaten kayıtlı görünüyor.':'Bu domain kayıt için uygun olabilir.'];
}
if ($route === 'admin/customers/close') {
    verify_csrf();
    $id=(int)($_GET['id']??0);
    if($id>0){
        try{ ao_schema_ensure_v780(); db()->prepare('UPDATE customers SET status="closed" WHERE id=?')->execute([$id]); ao_customer_log($id,'customer.closed','Müşteri kapatıldı; veriler korundu.'); flash('success','Müşteri kapalı duruma alındı. İlişkili kayıtlar korunur.'); }
        catch(Throwable $e){ flash('error','Müşteri kapatılamadı: '.$e->getMessage()); }
    }
    redirect_to('admin/customers');
}
if ($route === 'admin/customers/delete') {
    verify_csrf();
    $id=(int)($_GET['id']??0);
    if($id>0){
        try{ ao_schema_ensure_v780(); db()->prepare('UPDATE customers SET status="deleted", deleted_at=NOW() WHERE id=?')->execute([$id]); ao_customer_log($id,'customer.soft_deleted','Müşteri soft delete ile çöp kutusuna taşındı.'); flash('success','Müşteri çöp kutusuna taşındı. Kalıcı silme için Silinenler görünümünü kullan.'); }
        catch(Throwable $e){ flash('error','Müşteri silinemedi: '.$e->getMessage()); }
    }
    redirect_to('admin/customers');
}
if ($route === 'admin/customers/restore') {
    verify_csrf();
    $id=(int)($_GET['id']??0);
    if($id>0){ try{ ao_schema_ensure_v780(); db()->prepare('UPDATE customers SET status="active", restored_at=NOW(), deleted_at=NULL WHERE id=?')->execute([$id]); ao_customer_log($id,'customer.restored','Silinen müşteri geri yüklendi.'); flash('success','Müşteri geri yüklendi.'); } catch(Throwable $e){ flash('error','Müşteri geri yüklenemedi: '.$e->getMessage()); } }
    redirect_to('admin/customers?show=deleted');
}
if ($route === 'admin/customers/permanent-delete') {
    verify_csrf();
    $id=(int)($_GET['id']??0);
    if($id>0){
        try{
            $pdo=db(); $pdo->beginTransaction();
            foreach(['credit_transactions','payments','invoice_items','invoices','order_items','orders','ticket_replies','tickets','hosting_accounts','services','domain_dns_records','domain_nameservers','domains','customer_activity_logs'] as $tbl){
                try{
                    if(in_array($tbl,['hosting_accounts'],true)) $pdo->prepare('DELETE h FROM hosting_accounts h JOIN services s ON s.id=h.service_id WHERE s.customer_id=?')->execute([$id]);
                    elseif(in_array($tbl,['invoice_items'],true)) $pdo->prepare('DELETE ii FROM invoice_items ii JOIN invoices i ON i.id=ii.invoice_id WHERE i.customer_id=?')->execute([$id]);
                    elseif(in_array($tbl,['order_items'],true)) $pdo->prepare('DELETE oi FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.customer_id=?')->execute([$id]);
                    elseif(in_array($tbl,['domain_dns_records'],true)) $pdo->prepare('DELETE r FROM domain_dns_records r JOIN domains d ON d.id=r.domain_id WHERE d.customer_id=?')->execute([$id]);
                    elseif(in_array($tbl,['domain_nameservers'],true)) $pdo->prepare('DELETE ns FROM domain_nameservers ns JOIN domains d ON d.id=ns.domain_id WHERE d.customer_id=?')->execute([$id]);
                    elseif(in_array($tbl,['payments'],true)) $pdo->prepare('DELETE p FROM payments p JOIN invoices i ON i.id=p.invoice_id WHERE i.customer_id=?')->execute([$id]);
                    elseif(in_array($tbl,['ticket_replies'],true)) $pdo->prepare('DELETE tr FROM ticket_replies tr JOIN tickets t ON t.id=tr.ticket_id WHERE t.customer_id=?')->execute([$id]);
                    else $pdo->prepare('DELETE FROM '.$tbl.' WHERE customer_id=?')->execute([$id]);
                } catch(Throwable $ignore) {}
            }
            $pdo->prepare('DELETE FROM customers WHERE id=?')->execute([$id]);
            $pdo->commit(); flash('success','Müşteri ve bağlı kayıtlar kalıcı olarak silindi.');
        } catch(Throwable $e){ if(isset($pdo) && $pdo->inTransaction()) $pdo->rollBack(); flash('error','Kalıcı silme yapılamadı: '.$e->getMessage()); }
    }
    redirect_to('admin/customers?show=deleted');
}
if ($route === 'admin/hosting-server/delete') {
    verify_csrf();
    $id=(int)($_GET['id']??0);
    if($id>0){ try{ db()->prepare('DELETE FROM server_nodes WHERE id=?')->execute([$id]); flash('success','Sunucu silindi.'); } catch(Throwable $e){ flash('error','Sunucu silinemedi.'); } }
    redirect_to('admin/hosting-server/servers');
}
if ($route === 'admin/hosting-server/login') {
    require_admin();
    $id=(int)($_GET['id']??0); $target='';
    try{
        $q=db()->prepare('SELECT * FROM server_nodes WHERE id=?'); $q->execute([$id]); $srv=$q->fetch();
        if($srv){
            $panel = ($srv['panel_type']==='whm' || $srv['panel_type']==='cpanel') ? 'whm' : $srv['panel_type'];
            if(in_array($panel, ['whm','cpanel'], true) && !empty($srv['api_token'])){
                $sso = ao_whm_create_user_session($srv, $srv['username'] ?: 'root', 'whostmgrd');
                if(!empty($sso['ok'])) $target = $sso['url'];
                else ao_log_simple('server','whm-sso-error','error',$sso['message'] ?? 'WHM SSO başarısız');
            }
            if(!$target) $target=ao_panel_url_from_host(ao_host_from_server_row($srv), $panel);
        }
    }catch(Throwable $e){ ao_log_simple('server','login-redirect-error','error',$e->getMessage()); }
    if($target && $target!=='#'){ ao_log_simple('server','login-redirect','success','Sunucu panel yönlendirmesi hazırlandı.'); header('Location: '.$target); exit; }
    flash('error','Sunucu giriş URL oluşturulamadı. Host/IP ve WHM API token alanını kontrol edin.'); redirect_to('admin/hosting-server/servers');
}
if ($route === 'admin/hosting-server/test') {
    $id=(int)($_GET['id']??0);
    try{ $q=db()->prepare('SELECT * FROM server_nodes WHERE id=?'); $q->execute([$id]); $srv=$q->fetch(); if(!$srv) throw new Exception('Sunucu yok.'); $url=ao_panel_url_from_host(ao_host_from_server_row($srv), $srv['panel_type']==='whm'?'whm':$srv['panel_type']); db()->prepare('UPDATE server_nodes SET status=IF(status="inactive","ready",status) WHERE id=?')->execute([$id]); ao_log_simple($srv['panel_type'],'connection-test','success','Test URL hazır: '.$url); flash('success','Bağlantı testi hazır. Canlı API token girildiğinde gerçek API testi yapılır. URL: '.$url); }catch(Throwable $e){ flash('error','Sunucu test edilemedi: '.$e->getMessage()); }
    redirect_to('admin/hosting-server/servers');
}
if ($route === 'client/service-panel-login' || $route === 'admin/service-panel-login') {
    $serviceId=(int)($_GET['service_id']??0); $panel=trim($_GET['panel']??'cpanel'); $h=null;
    try{
        if($route === 'client/service-panel-login') { require_customer(); $c=current_customer(); $q=db()->prepare('SELECT h.* FROM hosting_accounts h JOIN services s ON s.id=h.service_id WHERE h.service_id=? AND s.customer_id=? LIMIT 1'); $q->execute([$serviceId,$c['id']]); }
        else { require_admin(); $q=db()->prepare('SELECT h.* FROM hosting_accounts h WHERE h.service_id=? LIMIT 1'); $q->execute([$serviceId]); }
        $h=$q->fetch();
        if(!$h) throw new Exception('Hosting hesabı bulunamadı.');
        $srv=null;
        if(!empty($h['server_id'])){ $sq=db()->prepare('SELECT * FROM server_nodes WHERE id=? LIMIT 1'); $sq->execute([(int)$h['server_id']]); $srv=$sq->fetch() ?: null; }
        $col=['cpanel'=>'cpanel_url','directadmin'=>'directadmin_url','webmail'=>'webmail_url','whm'=>'whm_url','vps'=>'vps_panel_url','plesk'=>'plesk_url'][$panel] ?? 'cpanel_url';
        $url='';
        if($srv && in_array($panel, ['cpanel','webmail','whm'], true) && !empty($srv['api_token'])){
            $loginUser = $panel==='whm' ? ($srv['username'] ?: 'root') : ($h['whm_username'] ?: ($h['username'] ?? ''));
            $sso = ao_whm_create_user_session($srv, $loginUser, ao_panel_service_name($panel));
            if(!empty($sso['ok'])) $url=$sso['url'];
            else ao_log_simple('hosting-panel','sso-'.$panel.'-error','error',$sso['message'] ?? 'WHM SSO başarısız');
        }
        if(!$url) $url=trim($h[$col] ?? '');
        if(!$url || $url==='#') $url=ao_panel_url_from_host(($h['server_name'] ?? '') ?: ($h['server_ip'] ?? ''), $panel);
        if(!$url || $url==='#') throw new Exception('Panel URL boş.');
        ao_log_simple('hosting-panel','login-'.$panel,'success','Panel yönlendirmesi service_id='.$serviceId);
        header('Location: '.$url); exit;
    }catch(Throwable $e){ flash('error','Panel giriş yönlendirmesi yapılamadı: '.$e->getMessage()); redirect_to($route === 'client/service-panel-login' ? 'client/services/view?id='.$serviceId : 'admin/customers'); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/support/ticket-reply') {
    verify_csrf();
    $tid=(int)($_POST['ticket_id']??0); $msg=trim($_POST['message']??''); $status=trim($_POST['status']??'answered');
    try{ if(!$tid||!$msg) throw new Exception('Ticket ve mesaj zorunlu.'); db()->prepare('INSERT INTO ticket_replies(ticket_id,sender_type,message) VALUES(?,"admin",?)')->execute([$tid,$msg]); db()->prepare('UPDATE tickets SET status=? WHERE id=?')->execute([$status,$tid]); flash('success','Ticket yanıtlandı.'); }catch(Throwable $e){ flash('error','Ticket yanıtlanamadı: '.$e->getMessage()); }
    redirect_to('admin/support/tickets');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/support/department-save') {
    verify_csrf();
    $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $sla=(int)($_POST['sla_hours']??24);
    try{ if(!$name) throw new Exception('Departman adı zorunlu.'); db()->prepare('INSERT INTO support_departments(name,email,sla_hours,is_active) VALUES(?,?,?,1)')->execute([$name,$email,$sla]); flash('success','Destek departmanı kaydedildi.'); }catch(Throwable $e){ flash('error','Departman kaydedilemedi: '.$e->getMessage()); }
    redirect_to('admin/support/departments');
}

if (!function_exists('ao_social_auth_ensure_schema')) {
    function ao_social_auth_ensure_schema(): void {
        try {
            db()->exec("CREATE TABLE IF NOT EXISTS customer_social_accounts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                provider VARCHAR(40) NOT NULL,
                provider_user_id VARCHAR(190) NOT NULL,
                email VARCHAR(190) NULL,
                profile_json LONGTEXT NULL,
                linked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login_at DATETIME NULL,
                UNIQUE KEY uniq_provider_user(provider, provider_user_id),
                KEY customer_id(customer_id),
                KEY email(email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch(Throwable $e) { error_log('[social-auth] '.$e->getMessage()); }
    }
    function ao_social_auth_redirect_uri(string $provider): string {
        return url('client/oauth/'.$provider.'/callback');
    }
    function ao_social_auth_provider(string $provider): ?array {
        $providers = function_exists('ao_social_login_providers') ? ao_social_login_providers() : [];
        return $providers[$provider] ?? null;
    }
    function ao_social_auth_login_customer(array $profile, string $provider): void {
        ao_social_auth_ensure_schema();
        $providerUserId = trim((string)($profile['id'] ?? ''));
        $email = strtolower(trim((string)($profile['email'] ?? '')));
        if ($providerUserId === '' || $email === '') throw new Exception('Sosyal giriş için e-posta bilgisi alınamadı.');
        $first = trim((string)($profile['first_name'] ?? ''));
        $last = trim((string)($profile['last_name'] ?? ''));
        if ($first === '' && !empty($profile['name'])) {
            $parts = preg_split('/\s+/', trim((string)$profile['name']), 2);
            $first = $parts[0] ?? '';
            $last = $parts[1] ?? '';
        }
        $q = db()->prepare('SELECT * FROM customer_social_accounts WHERE provider=? AND provider_user_id=? LIMIT 1');
        $q->execute([$provider, $providerUserId]);
        $linked = $q->fetch();
        if ($linked) {
            $customerId = (int)$linked['customer_id'];
        } else {
            $q = db()->prepare('SELECT id FROM customers WHERE email=? AND status<>"closed" LIMIT 1');
            $q->execute([$email]);
            $customerId = (int)($q->fetchColumn() ?: 0);
            if ($customerId <= 0) {
                $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                $s = db()->prepare('INSERT INTO customers(first_name,last_name,email,password_hash,status) VALUES(?,?,?,?, "active")');
                $s->execute([$first ?: 'Müşteri', $last, $email, $hash]);
                $customerId = (int)db()->lastInsertId();
            }
            db()->prepare('INSERT INTO customer_social_accounts(customer_id,provider,provider_user_id,email,profile_json,last_login_at) VALUES(?,?,?,?,?,NOW())')->execute([$customerId,$provider,$providerUserId,$email,json_encode($profile, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        }
        db()->prepare('UPDATE customer_social_accounts SET email=?, profile_json=?, last_login_at=NOW() WHERE provider=? AND provider_user_id=?')->execute([$email,json_encode($profile, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),$provider,$providerUserId]);
        db()->prepare('UPDATE customers SET last_login_at=NOW() WHERE id=?')->execute([$customerId]);
        $_SESSION['customer_id'] = $customerId;
        if (function_exists('ao_session_mark_authenticated')) ao_session_mark_authenticated('customer');
    }
}

if (preg_match('~^client/oauth/(google|facebook)$~', $route, $m)) {
    $providerKey = $m[1];
    $provider = ao_social_auth_provider($providerKey);
    if (!$provider) { flash('error', 'Bu sosyal giriş sağlayıcısı aktif değil veya API bilgileri eksik.'); redirect_to('client/login'); }
    $state = bin2hex(random_bytes(16));
    $_SESSION['social_oauth_state_'.$providerKey] = $state;
    $_SESSION['social_oauth_return'] = $_SERVER['HTTP_REFERER'] ?? url('client');
    if ($providerKey === 'google') {
        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id'=>$provider['client_id'],
            'redirect_uri'=>ao_social_auth_redirect_uri('google'),
            'response_type'=>'code',
            'scope'=>'openid email profile',
            'state'=>$state,
            'prompt'=>'select_account',
        ]);
    } else {
        $authUrl = 'https://www.facebook.com/v19.0/dialog/oauth?'.http_build_query([
            'client_id'=>$provider['client_id'],
            'redirect_uri'=>ao_social_auth_redirect_uri('facebook'),
            'response_type'=>'code',
            'scope'=>'email,public_profile',
            'state'=>$state,
        ]);
    }
    header('Location: '.$authUrl);
    exit;
}

if (preg_match('~^client/oauth/(google|facebook)/callback$~', $route, $m)) {
    $providerKey = $m[1];
    try {
        $provider = ao_social_auth_provider($providerKey);
        if (!$provider) throw new Exception('Sosyal giriş sağlayıcısı aktif değil.');
        $state = (string)($_GET['state'] ?? '');
        if ($state === '' || !hash_equals((string)($_SESSION['social_oauth_state_'.$providerKey] ?? ''), $state)) throw new Exception('Sosyal giriş doğrulaması geçersiz.');
        $code = trim((string)($_GET['code'] ?? ''));
        if ($code === '') throw new Exception('Sosyal giriş kodu alınamadı.');
        unset($_SESSION['social_oauth_state_'.$providerKey]);
        if ($providerKey === 'google') {
            $token = ao_http_request('POST', 'https://oauth2.googleapis.com/token', ['Content-Type'=>'application/x-www-form-urlencoded'], [
                'client_id'=>$provider['client_id'],
                'client_secret'=>$provider['client_secret'],
                'redirect_uri'=>ao_social_auth_redirect_uri('google'),
                'grant_type'=>'authorization_code',
                'code'=>$code,
            ], 20);
            $tokenJson = json_decode((string)$token['body'], true) ?: [];
            if (empty($token['ok']) || empty($tokenJson['access_token'])) throw new Exception('Google token alınamadı.');
            $user = ao_http_request('GET', 'https://www.googleapis.com/oauth2/v3/userinfo', ['Authorization'=>'Bearer '.$tokenJson['access_token']], null, 20);
            $profile = json_decode((string)$user['body'], true) ?: [];
            $profile = ['id'=>$profile['sub'] ?? '', 'email'=>$profile['email'] ?? '', 'first_name'=>$profile['given_name'] ?? '', 'last_name'=>$profile['family_name'] ?? '', 'name'=>$profile['name'] ?? ''] + $profile;
        } else {
            $tokenUrl = 'https://graph.facebook.com/v19.0/oauth/access_token?'.http_build_query([
                'client_id'=>$provider['client_id'],
                'client_secret'=>$provider['client_secret'],
                'redirect_uri'=>ao_social_auth_redirect_uri('facebook'),
                'code'=>$code,
            ]);
            $token = ao_http_request('GET', $tokenUrl, [], null, 20);
            $tokenJson = json_decode((string)$token['body'], true) ?: [];
            if (empty($token['ok']) || empty($tokenJson['access_token'])) throw new Exception('Facebook token alınamadı.');
            $userUrl = 'https://graph.facebook.com/me?'.http_build_query(['fields'=>'id,name,email,first_name,last_name','access_token'=>$tokenJson['access_token']]);
            $user = ao_http_request('GET', $userUrl, [], null, 20);
            $profile = json_decode((string)$user['body'], true) ?: [];
        }
        ao_social_auth_login_customer($profile, $providerKey);
        flash('success', ucfirst($providerKey).' ile giriş yapıldı.');
        redirect_to('client');
    } catch(Throwable $e) {
        flash('error', 'Sosyal giriş tamamlanamadı: '.$e->getMessage());
        redirect_to('client/login');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'client/login-phone') {
    verify_csrf();
    $phone = preg_replace('/\D+/', '', (string)($_POST['phone'] ?? ''));
    $pass = $_POST['password'] ?? '';
    ao_mfa_ensure_schema();
    $s = db()->prepare('SELECT * FROM customers WHERE REPLACE(REPLACE(REPLACE(REPLACE(phone," ",""),"(",""),")",""),"-","") LIKE ? AND status<>"closed" LIMIT 1');
    $s->execute(['%' . $phone]);
    $c = $s->fetch();
    if ($phone !== '' && $c && !empty($c['password_hash']) && password_verify($pass, $c['password_hash'])) {
        try { db()->prepare('UPDATE customers SET last_login_at=NOW() WHERE id=?')->execute([$c['id']]); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        ao_mfa_start_challenge('customer', $c, 'client');
    }
    ao_mfa_log('customer', null, $phone, 'login', 'phone_password', 'failed', 'Müşteri telefon veya şifre hatalı.');
    flash('error','Telefon veya şifre hatalı.');
    redirect_to('client/login');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'client/login-sms/request') {
    verify_csrf();
    $phone = preg_replace('/\D+/', '', (string)($_POST['phone'] ?? ''));
    $s = db()->prepare('SELECT * FROM customers WHERE REPLACE(REPLACE(REPLACE(REPLACE(phone," ",""),"(",""),")",""),"-","") LIKE ? AND status<>"closed" LIMIT 1');
    $s->execute(['%' . $phone]);
    $c = $s->fetch();
    if ($phone === '' || !$c) {
        flash('error','Bu telefon numarasıyla kayıtlı aktif müşteri bulunamadı.');
        redirect_to('client/login');
    }
    $code = (string)random_int(100000, 999999);
    $_SESSION['ao_sms_login'] = [
        'customer_id' => (int)$c['id'],
        'phone' => $phone,
        'code_hash' => password_hash($code, PASSWORD_DEFAULT),
        'expires_at' => time() + 300,
        'attempts' => 0,
    ];
    $smsResult = ['ok' => false, 'message' => 'SMS gönderim fonksiyonu bulunamadı.'];
    if (function_exists('ao_send_sms')) {
        $smsResult = ao_send_sms($c['phone'] ?: $phone, 'Ahost One giriş kodunuz: '.$code.' Kod 5 dakika geçerlidir.', 'customer_sms_login');
    } else {
        error_log('[ao] SMS giriş kodu: '.$code.' phone='.$phone);
    }
    if (!empty($smsResult['ok'])) {
        flash('success','SMS giriş kodu gönderildi. Kod 5 dakika geçerlidir.');
    } else {
        unset($_SESSION['ao_sms_login']);
        flash('error','SMS giriş kodu gönderilemedi: '.($smsResult['message'] ?? 'SMS sağlayıcısı yanıt vermedi.'));
    }
    redirect_to('client/login');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'client/login-sms/verify') {
    verify_csrf();
    $code = trim((string)($_POST['sms_code'] ?? ''));
    $pending = $_SESSION['ao_sms_login'] ?? null;
    if (!$pending || time() > (int)($pending['expires_at'] ?? 0)) {
        unset($_SESSION['ao_sms_login']);
        flash('error','SMS kodu süresi doldu. Lütfen yeniden kod alın.');
        redirect_to('client/login');
    }
    $_SESSION['ao_sms_login']['attempts'] = (int)($pending['attempts'] ?? 0) + 1;
    if ($_SESSION['ao_sms_login']['attempts'] > 5 || !password_verify($code, (string)$pending['code_hash'])) {
        flash('error','SMS kodu hatalı.');
        redirect_to('client/login');
    }
    $q = db()->prepare('SELECT * FROM customers WHERE id=? AND status<>"closed" LIMIT 1');
    $q->execute([(int)$pending['customer_id']]);
    $c = $q->fetch();
    unset($_SESSION['ao_sms_login']);
    if (!$c) {
        flash('error','Müşteri hesabı bulunamadı.');
        redirect_to('client/login');
    }
    try { db()->prepare('UPDATE customers SET last_login_at=NOW() WHERE id=?')->execute([$c['id']]); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    ao_mfa_start_challenge('customer', $c, 'client');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'client/login') {
    $email = trim($_POST['email'] ?? ''); $pass = $_POST['password'] ?? '';
    ao_mfa_ensure_schema();
    $s = db()->prepare('SELECT * FROM customers WHERE email=? AND status<>"closed" LIMIT 1'); $s->execute([$email]); $c=$s->fetch();
    if ($c && !empty($c['password_hash']) && password_verify($pass, $c['password_hash'])) {
        try { $u=db()->prepare('UPDATE customers SET last_login_at=NOW() WHERE id=?'); $u->execute([$c['id']]); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        ao_mfa_start_challenge('customer', $c, 'client');
    }
    ao_mfa_log('customer', null, $email, 'login', 'password', 'failed', 'Müşteri e-posta veya şifre hatalı.');
    flash('error','E-posta veya şifre hatalı.'); redirect_to('client/login');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='client/security/mfa-save') {
    require_customer(); verify_csrf(); ao_mfa_ensure_schema();
    $customer=current_customer(); $uid=(int)$customer['id'];
    $enabled=(($_POST['enabled'] ?? '0')==='1') ? 1 : 0;
    $method=in_array(($_POST['preferred_method'] ?? 'mail'), ['mail','totp','sms'], true) ? $_POST['preferred_method'] : 'mail';
    $secret = $method==='totp' ? ao_mfa_get_totp_secret('customer',$uid,true) : null;
    try { db()->prepare('INSERT INTO auth_mfa_profiles(user_type,user_id,enabled,preferred_method,totp_secret,verified_at) VALUES(?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE enabled=VALUES(enabled), preferred_method=VALUES(preferred_method), totp_secret=COALESCE(VALUES(totp_secret),totp_secret), verified_at=NOW()')->execute(['customer',$uid,$enabled,$method,$secret]); flash('success','2FA ayarlarınız kaydedildi.'); }
    catch(Throwable $e){ flash('error','2FA ayarı kaydedilemedi: '.$e->getMessage()); }
    redirect_to('client/security');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'client/register') {
    verify_csrf(); ao_schema_ensure_v186();
    $first=trim($_POST['first_name']??''); $last=trim($_POST['last_name']??''); $email=trim($_POST['email']??''); $pass=$_POST['password']??''; $phone=trim($_POST['phone']??''); $tc=trim($_POST['tc_identity_no']??''); $birth=trim($_POST['birth_date']??'');
    $productSlug=preg_replace('/[^a-z0-9_-]/','',strtolower(trim($_POST['product_slug']??''))); $registerReturn='client/register'.($productSlug?'?product='.rawurlencode($productSlug):'');
    if (!$first || !$last || !$email || !$phone || !$tc || !$birth || strlen($pass)<6) { flash('error','Ad, soyad, telefon, e-posta, TC Kimlik No, doğum tarihi ve en az 6 karakter şifre zorunludur.'); redirect_to($registerReturn); }
    $verify=ao_identity_verify($first,$last,$birth,$tc);
    if(empty($verify['ok'])){ flash('error','Kayıt oluşturulmadı: '.$verify['message']); redirect_to($registerReturn); }
    try { $s=db()->prepare('INSERT INTO customers(first_name,last_name,email,phone,tc_identity_no,birth_date,identity_verified,identity_verified_at,password_hash,status) VALUES(?,?,?,?,?,?,1,NOW(),?,"active")'); $s->execute([$first,$last,$email,$phone,preg_replace('/\D/','',$tc),$birth,password_hash($pass,PASSWORD_DEFAULT)]); $_SESSION['customer_id']=db()->lastInsertId(); if($productSlug) $_SESSION['pending_product_slug']=$productSlug; flash('success',$productSlug?'Hesabınız oluşturuldu; seçtiğiniz ürün sipariş tercihinize eklendi.':'Hesabınız oluşturuldu.'); redirect_to('client'); }
    catch(Throwable $e){ flash('error','Kayıt oluşturulamadı. Bu e-posta daha önce kullanılmış olabilir.'); redirect_to($registerReturn); }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'client/forgot-password') {
    ao_schema_ensure_v188(); verify_csrf();
    $email=trim($_POST['email']??''); $channel=trim($_POST['channel']??'email');
    try{
        $q=db()->prepare('SELECT * FROM customers WHERE email=? AND status<>"closed" LIMIT 1'); $q->execute([$email]); $c=$q->fetch();
        if(!$c) throw new Exception('Bu e-posta için kayıt bulunamadı.');
        $token=bin2hex(random_bytes(32)); $hash=hash('sha256',$token); $expires=date('Y-m-d H:i:s',time()+3600);
        db()->prepare('INSERT INTO password_reset_tokens(customer_id,email,token_hash,channel,expires_at) VALUES(?,?,?,?,?)')->execute([(int)$c['id'],$email,$hash,$channel,$expires]);
        $link=url('client/reset-password?token='.$token);
        ao_log_simple('password_reset',$channel,'queued','Şifre yenileme linki oluşturuldu: '.$email,json_encode(['link'=>$link,'expires_at'=>$expires],JSON_UNESCAPED_UNICODE));
        flash('success','Şifre değiştirme linki '.$channel.' kanalına hazırlandı. Link 1 saat geçerlidir.');
    }catch(Throwable $e){ flash('error','Şifre yenileme başlatılamadı: '.$e->getMessage()); }
    redirect_to('client/forgot-password');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'client/reset-password') {
    ao_schema_ensure_v188(); verify_csrf();
    $token=trim($_POST['token']??''); $pass=$_POST['password']??'';
    try{ if(strlen($pass)<6) throw new Exception('Şifre en az 6 karakter olmalı.'); $hash=hash('sha256',$token); $q=db()->prepare('SELECT * FROM password_reset_tokens WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW() LIMIT 1'); $q->execute([$hash]); $r=$q->fetch(); if(!$r) throw new Exception('Link geçersiz veya süresi dolmuş.'); db()->prepare('UPDATE customers SET password_hash=? WHERE id=?')->execute([password_hash($pass,PASSWORD_DEFAULT),(int)$r['customer_id']]); db()->prepare('UPDATE password_reset_tokens SET used_at=NOW() WHERE id=?')->execute([(int)$r['id']]); flash('success','Şifreniz değiştirildi. Giriş yapabilirsiniz.'); redirect_to('client/login'); }catch(Throwable $e){ flash('error','Şifre değiştirilemedi: '.$e->getMessage()); redirect_to('client/reset-password?token='.urlencode($token)); }
}
if ($route === 'client/logout') { if(function_exists('ao_session_clear_user')) ao_session_clear_user('customer'); unset($_SESSION['mfa_pending']); flash('success','Çıkış yapıldı.'); redirect_to('client/login'); }



if ($route === 'admin/customers/login-as') {
    $id=(int)($_GET['id']??0);
    if ($id>0) {
        $q=db()->prepare('SELECT id FROM customers WHERE id=? AND status<>"closed" LIMIT 1');
        $q->execute([$id]);
        if ($q->fetch()) {
            $_SESSION['admin_impersonating_customer_id']=$id;
            $_SESSION['customer_id']=$id;
            if(function_exists('ao_session_mark_authenticated')) ao_session_mark_authenticated('customer');
            flash('success','Sahip olarak müşteri paneline geçildi.');
            redirect_to('client');
        }
    }
    flash('error','Müşteri oturumu başlatılamadı.');
    redirect_to('admin/customers');
}
if ($route === 'admin/customers/stop-login-as') {
    $id=(int)($_SESSION['admin_impersonating_customer_id']??0);
    unset($_SESSION['admin_impersonating_customer_id']);
    if(function_exists('ao_session_clear_user')) ao_session_clear_user('customer'); else unset($_SESSION['customer_id']);
    flash('success','Müşteri oturumu kapatıldı, admin profiline dönüldü.');
    redirect_to($id ? 'admin/customers/view?id='.$id : 'admin/customers');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/customers/add') {
    ao_schema_ensure_v186();
    $first=trim($_POST['first_name']??''); $last=trim($_POST['last_name']??''); $email=trim($_POST['email']??''); $phone=trim($_POST['phone']??''); $company=trim($_POST['company_name']??''); $status=trim($_POST['status']??'active'); $pass=$_POST['password']??''; $tc=trim($_POST['tc_identity_no']??''); $birth=trim($_POST['birth_date']??'');
    if (!$first || !$last || !$email) { flash('error','Ad, soyad ve e-posta zorunludur. TC ve doğum tarihi admin eklemede opsiyoneldir.'); redirect_to('admin/customers/add'); }
    $identityVerified = ($tc && $birth && ao_identity_verify($first,$last,$birth,$tc)['ok']) ? 1 : 0;
    $hash = $pass ? password_hash($pass, PASSWORD_DEFAULT) : null;
    try { $q=db()->prepare('INSERT INTO customers(first_name,last_name,company_name,email,phone,tc_identity_no,birth_date,identity_verified,identity_verified_at,password_hash,status) VALUES(?,?,?,?,?,?,?,?,?,?,?)'); $q->execute([$first,$last,$company,$email,$phone,$tc?preg_replace('/\D/','',$tc):null,$birth?:null,$identityVerified,$identityVerified?date('Y-m-d H:i:s'):null,$hash,$status]); flash('success','Müşteri oluşturuldu.'); redirect_to('admin/customers/view?id='.db()->lastInsertId()); }
    catch(Throwable $e){ flash('error','Müşteri eklenemedi: e-posta daha önce kullanılmış olabilir.'); redirect_to('admin/customers/add'); }
}
if (!function_exists('ao_return_tab')) {
    function ao_return_tab(string $default = ''): string {
        $tab = trim((string)($_POST['return_tab'] ?? $_GET['return_tab'] ?? $default));
        $tab = preg_replace('/[^a-z0-9_-]/i', '', $tab);
        return $tab !== '' ? $tab : $default;
    }
}
if (!function_exists('ao_tab_hash')) {
    function ao_tab_hash(string $default = ''): string {
        $tab = ao_return_tab($default);
        return $tab !== '' ? '#tab-'.$tab : '';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/customers/update') {
    ao_schema_ensure_v186();
    $id=(int)($_POST['id']??0); $first=trim($_POST['first_name']??''); $last=trim($_POST['last_name']??''); $email=trim($_POST['email']??''); $phone=trim($_POST['phone']??''); $company=trim($_POST['company_name']??''); $status=trim($_POST['status']??'active'); $balance=(float)($_POST['balance']??0); $tc=trim($_POST['tc_identity_no']??''); $birth=trim($_POST['birth_date']??'');
    $address1=trim($_POST['address1']??''); $address2=trim($_POST['address2']??''); $city=trim($_POST['city']??''); $state=trim($_POST['state']??''); $postcode=trim($_POST['postcode']??''); $country=trim($_POST['country']??'Türkiye'); $tax=trim($_POST['tax_number']??''); $currency=trim($_POST['currency']??'TRY'); $language=trim($_POST['language']??'tr'); $notes=trim($_POST['notes']??'');
    $identityVerified = ($tc && $birth && ao_identity_verify($first,$last,$birth,$tc)['ok']) ? 1 : 0;
    if ($id>0 && $first && $last && $email) { try { $q=db()->prepare('UPDATE customers SET first_name=?,last_name=?,company_name=?,email=?,phone=?,tc_identity_no=?,birth_date=?,identity_verified=?,identity_verified_at=IF(?=1,NOW(),identity_verified_at),status=?,balance=?,address1=?,address2=?,city=?,state=?,postcode=?,country=?,tax_number=?,currency=?,language=?,notes=? WHERE id=?'); $q->execute([$first,$last,$company,$email,$phone,$tc?preg_replace('/\D/','',$tc):null,$birth?:null,$identityVerified,$identityVerified,$status,$balance,$address1,$address2,$city,$state,$postcode,$country,$tax,$currency,$language,$notes,$id]); flash('success','Müşteri bilgileri güncellendi.'); } catch(Throwable $e){ flash('error','Güncelleme yapılamadı.'); } }
    redirect_to('admin/customers/view?id='.$id.ao_tab_hash('profil'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'client/invoices/pay') {
    require_customer(); verify_csrf(); $c=current_customer(); ao_schema_ensure_v990();
    $invoiceId=(int)($_POST['invoice_id']??0); $amount=(float)($_POST['amount']??0); $method=trim((string)($_POST['method']??''));
    try{
        if($invoiceId<=0) throw new Exception('Fatura bilgisi eksik.');
        if(!in_array($method, ['shopier','manual','balance'], true)) throw new Exception('Lütfen ödeme yöntemi seçin.');
        $invoiceQ=db()->prepare('SELECT * FROM invoices WHERE id=? AND customer_id=? LIMIT 1');
        $invoiceQ->execute([$invoiceId,(int)$c['id']]);
        $invoice=$invoiceQ->fetch();
        if(!$invoice) throw new Exception('Bu faturaya erişim yetkiniz yok.');
        if(in_array(strtolower((string)($invoice['status'] ?? '')), ['paid','cancelled','refunded'], true)) throw new Exception('Bu fatura için ödeme alınamaz.');
        $paidQ=db()->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id=? AND customer_id=? AND status IN ("completed","paid")');
        $paidQ->execute([$invoiceId,(int)$c['id']]);
        $due=max(0, (float)($invoice['total'] ?? 0) - (float)$paidQ->fetchColumn());
        if($due<=0) throw new Exception('Bu fatura için ödenecek kalan tutar bulunmuyor.');
        if($amount<=0) $amount=$due;
        if($amount>$due) $amount=$due;
        if($method==='balance'){
            $available=(float)($c['balance'] ?? 0);
            if($available < $amount) throw new Exception('Bakiyeniz bu tutarı karşılamıyor.');
            ao_process_invoice_payment($invoiceId,(int)$c['id'],$amount,'balance','INV-'.strtoupper(substr(md5(microtime()),0,8)),'completed','Müşteri bakiyesi ile fatura ödeme');
            flash('success','Fatura bakiyeniz ile ödendi.');
            redirect_to('client/invoices/view?id='.$invoiceId);
        }
        if($method==='manual'){
            ao_process_invoice_payment($invoiceId,(int)$c['id'],$amount,'manual','TX-'.strtoupper(substr(md5(microtime()),0,10)),'pending','Havale/EFT ile ödeme bekliyor');
            flash('success','Ödeme talebiniz kaydedildi. Havale/EFT sonrası dekontu destek hattına iletebilirsiniz.');
            redirect_to('client/invoices/view?id='.$invoiceId);
        }
        if($method==='shopier'){
            $topupId=ao_credit_topup_create($c,$amount,'shopier',$invoiceId);
            redirect_to('payment/shopier/start?topup_id='.$topupId);
        }
        throw new Exception('Seçilen ödeme yöntemi desteklenmiyor.');
    }catch(Throwable $e){ flash('error','Fatura ödeme işlemi başlatılamadı: '.$e->getMessage()); }
    redirect_to('client/invoices/view?id='.$invoiceId);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'client/credit/add') {
    require_customer(); $c=current_customer(); ao_schema_ensure_v990();
    $amount=(float)($_POST['amount']??0); $method=trim((string)($_POST['method']??'manual')) ?: 'manual';
    try{
        if($amount<=0) throw new Exception('Geçerli bir tutar girin.');
        $topupId=ao_credit_topup_create($c,$amount,$method);
        if($method==='manual'){
            flash('success','Bakiye yükleme talebiniz alındı. Havale/EFT sonrası admin onayıyla bakiyeniz güncellenecek.');
            redirect_to('client/credit');
        }
        if($method==='shopier') redirect_to('payment/shopier/start?topup_id='.$topupId);
        flash('success','Bakiye yükleme talebi oluşturuldu. Seçilen ödeme sağlayıcısı hazır olduğunda yönlendirme yapılacak.');
    }catch(Throwable $e){ flash('error','Bakiye yükleme başlatılamadı: '.$e->getMessage()); }
    redirect_to('client/credit');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/customers/credit') {
    $id=(int)($_POST['id']??0); $amount=(float)($_POST['amount']??0); $note=trim($_POST['note']??'Admin kredi işlemi');
    if($id>0 && $amount!=0){
        try{ $q=db()->prepare('UPDATE customers SET balance=COALESCE(balance,0)+?, credit_balance=COALESCE(credit_balance,0)+? WHERE id=?'); $q->execute([$amount,$amount,$id]); flash('success','Müşteri kredisi güncellendi.'); }
        catch(Throwable $e){ flash('error','Kredi işlemi başarısız.'); }
    } else { flash('error','Kredi işlemi için müşteri ve tutar gerekli.'); }
    redirect_to('admin/customers/view?id='.$id.ao_tab_hash('muhasebe'));
}




// Ahost One: müşteri profilinden hizmet/domain satırlarını WHMCS benzeri düzenlemek için güvenli kolon bazlı kayıt.
if (!function_exists('ao_admin_table_columns')) {
    function ao_admin_table_columns(string $table): array {
        static $cache = [];
        if (isset($cache[$table])) return $cache[$table];
        $cols = [];
        try {
            foreach (db()->query('SHOW COLUMNS FROM `'.$table.'`')->fetchAll() as $c) {
                if (!empty($c['Field'])) $cols[$c['Field']] = true;
            }
        } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        return $cache[$table] = $cols;
    }
}
if (!function_exists('ao_admin_update_existing_columns')) {
    function ao_admin_update_existing_columns(string $table, array $where, array $values): bool {
        $cols = ao_admin_table_columns($table);
        $set = [];
        $params = [];
        foreach ($values as $col => $val) {
            if (!isset($cols[$col])) continue;
            $set[] = '`'.$col.'`=?';
            $params[] = $val;
        }
        if (!$set) return false;
        $whereSql = [];
        foreach ($where as $col => $val) {
            if (!isset($cols[$col])) return false;
            $whereSql[] = '`'.$col.'`=?';
            $params[] = $val;
        }
        db()->prepare('UPDATE `'.$table.'` SET '.implode(', ', $set).' WHERE '.implode(' AND ', $whereSql))->execute($params);
        return true;
    }
}
if (!function_exists('ao_admin_upsert_hosting_for_service')) {
    function ao_admin_upsert_hosting_for_service(int $serviceId, array $values): void {
        if ($serviceId <= 0) return;
        $cols = ao_admin_table_columns('hosting_accounts');
        if (!$cols || !isset($cols['service_id'])) return;
        $filtered = [];
        foreach ($values as $col => $val) {
            if (isset($cols[$col])) $filtered[$col] = $val;
        }
        if (!$filtered) return;
        $exists = false;
        try {
            $st = db()->prepare('SELECT id FROM hosting_accounts WHERE service_id=? LIMIT 1');
            $st->execute([$serviceId]);
            $exists = (bool)$st->fetchColumn();
        } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        if ($exists) {
            ao_admin_update_existing_columns('hosting_accounts', ['service_id'=>$serviceId], $filtered);
        } else {
            $insertCols = ['service_id'];
            $placeholders = ['?'];
            $params = [$serviceId];
            foreach ($filtered as $col => $val) {
                $insertCols[] = '`'.$col.'`';
                $placeholders[] = '?';
                $params[] = $val;
            }
            db()->prepare('INSERT INTO hosting_accounts(`service_id`,'.implode(',', array_slice($insertCols, 1)).') VALUES('.implode(',', $placeholders).')')->execute($params);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/customers/service-save') {
    require_admin(); verify_csrf();
    $customerId=(int)($_POST['customer_id']??0);
    $serviceId=(int)($_POST['service_id']??0);
    if($customerId<=0 || $serviceId<=0){ flash('error','Hizmet veya müşteri bilgisi eksik.'); redirect_to('admin/customers'); }
    try {
        $st=db()->prepare('SELECT * FROM services WHERE id=? AND customer_id=? LIMIT 1');
        $st->execute([$serviceId,$customerId]);
        if(!$st->fetch()) throw new Exception('Müşteriye ait hizmet kaydı bulunamadı.');

        $serviceValues = [
            'product_id'=>(int)($_POST['product_id']??0),
            'domain'=>trim((string)($_POST['domain']??'')),
            'status'=>trim((string)($_POST['status']??'active')),
            'billing_cycle'=>trim((string)($_POST['billing_cycle']??'')),
            'next_due_date'=>($_POST['next_due_date']??'')!=='' ? $_POST['next_due_date'] : null,
            'termination_date'=>($_POST['termination_date']??'')!=='' ? $_POST['termination_date'] : null,
            'terminated_at'=>($_POST['termination_date']??'')!=='' ? $_POST['termination_date'] : null,
            'registration_date'=>($_POST['registration_date']??'')!=='' ? $_POST['registration_date'] : null,
            'created_at'=>($_POST['registration_date']??'')!=='' ? $_POST['registration_date'].' 00:00:00' : null,
            'quantity'=>(int)($_POST['quantity']??1),
            'first_payment_amount'=>(float)($_POST['first_payment_amount']??0),
            'setup_fee'=>(float)($_POST['first_payment_amount']??0),
            'recurring_amount'=>(float)($_POST['recurring_amount']??0),
            'amount'=>(float)($_POST['recurring_amount']??0),
            'payment_method'=>trim((string)($_POST['payment_method']??'')),
            'promo_code'=>trim((string)($_POST['promo_code']??'')),
            'auto_renew'=>isset($_POST['auto_renew']) ? 1 : (int)($_POST['auto_renew']??0),
            'admin_notes'=>trim((string)($_POST['admin_notes']??'')),
            'notes'=>trim((string)($_POST['admin_notes']??'')),
            'server_id'=>(int)($_POST['server_id']??0),
        ];
        // Boş product/server id değerleri bazı tablolarda 0 yerine null istenebilir; dinamik yapıda güvenli bırakıyoruz.
        ao_admin_update_existing_columns('services', ['id'=>$serviceId,'customer_id'=>$customerId], $serviceValues);

        if (!empty($_POST['hosting_update'])) {
            $selectedServerId = (int)($_POST['server_id'] ?? 0);
            if ($selectedServerId > 0) {
                try {
                    $sq = db()->prepare('SELECT * FROM server_nodes WHERE id=? LIMIT 1');
                    $sq->execute([$selectedServerId]);
                    $srv = $sq->fetch();
                    if ($srv) {
                        $host = function_exists('ao_host_from_server_row') ? ao_host_from_server_row($srv) : trim((string)($srv['hostname'] ?? $srv['ip_address'] ?? $srv['name'] ?? ''));
                        if (trim((string)($_POST['server_name'] ?? '')) === '') $_POST['server_name'] = $srv['hostname'] ?: ($srv['name'] ?? '');
                        if (trim((string)($_POST['server_ip'] ?? '')) === '') $_POST['server_ip'] = $srv['ip_address'] ?? '';
                        if (trim((string)($_POST['panel_type'] ?? '')) === '') $_POST['panel_type'] = $srv['panel_type'] ?? '';
                        foreach (['cpanel'=>'cpanel_url','webmail'=>'webmail_url','whm'=>'whm_url','directadmin'=>'directadmin_url','plesk'=>'plesk_url','vps'=>'vps_panel_url'] as $panel=>$field) {
                            if (trim((string)($_POST[$field] ?? '')) === '' && $host !== '') $_POST[$field] = ao_panel_url_from_host($host, $panel);
                        }
                    }
                } catch (Throwable $e) {
                    error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
                }
            }
            $oldHosting = [];
            try {
                $oh = db()->prepare('SELECT h.*, s.domain, s.customer_id FROM hosting_accounts h LEFT JOIN services s ON s.id=h.service_id WHERE h.service_id=? LIMIT 1');
                $oh->execute([$serviceId]);
                $oldHosting = $oh->fetch() ?: [];
            } catch (Throwable $e) {
                $oldHosting = [];
            }
            $newPanelPassword = trim((string)($_POST['panel_password'] ?? ''));
            $passwordChanged = $newPanelPassword !== '' && $newPanelPassword !== trim((string)($oldHosting['panel_password'] ?? ''));
            if ($passwordChanged) {
                $syncPayload = $oldHosting ?: ['service_id'=>$serviceId, 'customer_id'=>$customerId];
                $syncPayload['server_id'] = (int)($_POST['server_id'] ?? ($syncPayload['server_id'] ?? 0));
                $syncPayload['server_name'] = trim((string)($_POST['server_name'] ?? ($syncPayload['server_name'] ?? '')));
                $syncPayload['server_ip'] = trim((string)($_POST['server_ip'] ?? ($syncPayload['server_ip'] ?? '')));
                $syncPayload['username'] = trim((string)($_POST['whm_username'] ?? ($syncPayload['username'] ?? $syncPayload['whm_username'] ?? '')));
                $sync = ao_hosting_panel_change_password($syncPayload, $newPanelPassword);
                if (empty($sync['ok'])) throw new Exception($sync['message'] ?? 'Sunucu şifre değişikliğini kabul etmedi.');
            }
            $hostingValues = [
                'server_id'=>(int)($_POST['server_id']??0),
                'server_name'=>trim((string)($_POST['server_name']??'')),
                'server_ip'=>trim((string)($_POST['server_ip']??'')),
                'ip_address'=>trim((string)($_POST['server_ip']??'')),
                'dedicated_ip'=>trim((string)($_POST['server_ip']??'')),
                'username'=>trim((string)($_POST['whm_username']??'')),
                'whm_username'=>trim((string)($_POST['whm_username']??'')),
                'panel_username'=>trim((string)($_POST['whm_username']??'')),
                'panel_password'=>trim((string)($_POST['panel_password']??'')),
                'package_name'=>trim((string)($_POST['package_name']??'')),
                'package'=>trim((string)($_POST['package_name']??'')),
                'panel_type'=>trim((string)($_POST['panel_type']??'')),
                'cpanel_url'=>trim((string)($_POST['cpanel_url']??'')),
                'webmail_url'=>trim((string)($_POST['webmail_url']??'')),
                'whm_url'=>trim((string)($_POST['whm_url']??'')),
                'directadmin_url'=>trim((string)($_POST['directadmin_url']??'')),
                'plesk_url'=>trim((string)($_POST['plesk_url']??'')),
                'vps_panel_url'=>trim((string)($_POST['vps_panel_url']??'')),
                'ns1'=>trim((string)($_POST['ns1']??'')),
                'ns2'=>trim((string)($_POST['ns2']??'')),
            ];
            ao_admin_upsert_hosting_for_service($serviceId, $hostingValues);
            if ($passwordChanged) {
                try {
                    $nh = db()->prepare('SELECT h.*, s.domain, s.customer_id FROM hosting_accounts h LEFT JOIN services s ON s.id=h.service_id WHERE h.service_id=? LIMIT 1');
                    $nh->execute([$serviceId]);
                    $newHosting = $nh->fetch() ?: ($oldHosting ?: $hostingValues);
                    $newHosting['customer_id'] = $customerId;
                    $newHosting['service_id'] = $serviceId;
                    ao_hosting_notify_credentials($newHosting, $newPanelPassword, 'hosting_password_changed');
                } catch (Throwable $e) {
                    error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
                }
            }
        }

        if(function_exists('ao_customer_log')) ao_customer_log($customerId,'service.updated','Profil içinden hizmet güncellendi: #'.$serviceId);
        flash('success','Müşteriye ait hizmet kaydı güncellendi.');
    } catch (Throwable $e) {
        flash('error','Hizmet kaydı güncellenemedi: '.$e->getMessage());
    }
    redirect_to('admin/customers/view?id='.$customerId.ao_tab_hash('urunler'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/customers/domain-save') {
    require_admin(); verify_csrf();
    $customerId=(int)($_POST['customer_id']??0);
    $domainId=(int)($_POST['domain_id']??0);
    if($customerId<=0 || $domainId<=0){ flash('error','Domain veya müşteri bilgisi eksik.'); redirect_to('admin/customers'); }
    try {
        $st=db()->prepare('SELECT * FROM domains WHERE id=? AND customer_id=? LIMIT 1');
        $st->execute([$domainId,$customerId]);
        if(!$st->fetch()) throw new Exception('Müşteriye ait domain kaydı bulunamadı.');

        $domainValues = [
            'domain_name'=>trim((string)($_POST['domain_name']??'')),
            'registrar_id'=>(int)($_POST['registrar_id']??0),
            'registrar'=>trim((string)($_POST['registrar']??'')),
            'status'=>trim((string)($_POST['status']??'active')),
            'registration_period'=>(int)($_POST['registration_period']??1),
            'registration_date'=>($_POST['registration_date']??'')!=='' ? $_POST['registration_date'] : null,
            'expiry_date'=>($_POST['expiry_date']??'')!=='' ? $_POST['expiry_date'] : null,
            'next_due_date'=>($_POST['next_due_date']??'')!=='' ? $_POST['next_due_date'] : null,
            'first_payment_amount'=>(float)($_POST['first_payment_amount']??0),
            'first_payment'=>(float)($_POST['first_payment_amount']??0),
            'recurring_amount'=>(float)($_POST['recurring_amount']??0),
            'renewal_price'=>(float)($_POST['recurring_amount']??0),
            'amount'=>(float)($_POST['recurring_amount']??0),
            'payment_method'=>trim((string)($_POST['payment_method']??'')),
            'promo_code'=>trim((string)($_POST['promo_code']??'')),
            'subscription_id'=>trim((string)($_POST['subscription_id']??'')),
            'auto_renew'=>isset($_POST['auto_renew']) ? 1 : 0,
            'lock_status'=>isset($_POST['lock_status']) ? 1 : 0,
            'epp_code'=>trim((string)($_POST['epp_code']??'')),
            'auth_code'=>trim((string)($_POST['epp_code']??'')),
            'ns1'=>trim((string)($_POST['ns1']??'')),
            'ns2'=>trim((string)($_POST['ns2']??'')),
            'ns3'=>trim((string)($_POST['ns3']??'')),
            'ns4'=>trim((string)($_POST['ns4']??'')),
            'ns5'=>trim((string)($_POST['ns5']??'')),
            'notes'=>trim((string)($_POST['notes']??'')),
            'admin_notes'=>trim((string)($_POST['notes']??'')),
        ];
        ao_admin_update_existing_columns('domains', ['id'=>$domainId,'customer_id'=>$customerId], $domainValues);
        if(function_exists('ao_customer_log')) ao_customer_log($customerId,'domain.updated','Profil içinden domain güncellendi: #'.$domainId);
        flash('success','Müşteriye ait domain kaydı güncellendi.');
    } catch (Throwable $e) {
        flash('error','Domain kaydı güncellenemedi: '.$e->getMessage());
    }
    redirect_to('admin/customers/view?id='.$customerId.ao_tab_hash('domainler'));
}


// Ahost One: müşteri hizmet detayı ürün kataloğundan ayrıldı.
// admin/customers/service = müşteriye atanmış hizmet kaydı; admin/product-center/products = ürün paketi/katalog.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/customers/service-save') {
    require_admin();
    try { verify_csrf(); } catch(Throwable $e) { /* eski formlar için sessiz geçme yok; bu route yeni formdur */ throw $e; }
    $customerId=(int)($_POST['customer_id']??0);
    $serviceId=(int)($_POST['service_id']??0);
    if($customerId<=0 || $serviceId<=0){ flash('error','Hizmet veya müşteri bilgisi eksik.'); redirect_to('admin/customers'); }
    try{
        $q=db()->prepare('SELECT * FROM services WHERE id=? AND customer_id=? LIMIT 1');
        $q->execute([$serviceId,$customerId]);
        $service=$q->fetch();
        if(!$service) throw new Exception('Müşteriye ait hizmet kaydı bulunamadı.');
        $domain=trim((string)($_POST['domain'] ?? ($service['domain'] ?? '')));
        $status=trim((string)($_POST['status'] ?? ($service['status'] ?? 'active')));
        $billing=trim((string)($_POST['billing_cycle'] ?? ($service['billing_cycle'] ?? 'monthly')));
        $nextDue=trim((string)($_POST['next_due_date'] ?? ($service['next_due_date'] ?? '')));
        $autoRenew=(int)($_POST['auto_renew'] ?? ($service['auto_renew'] ?? 1));
        $productId=array_key_exists('product_id', $_POST) ? (int)$_POST['product_id'] : (int)($service['product_id'] ?? 0);
        db()->prepare('UPDATE services SET product_id=?, domain=?, status=?, billing_cycle=?, next_due_date=?, auto_renew=? WHERE id=? AND customer_id=?')->execute([$productId?:null,$domain,$status,$billing,($nextDue!==''?$nextDue:null),$autoRenew,$serviceId,$customerId]);
        if(!empty($_POST['hosting_update'])){
            foreach(['ns1'=>'VARCHAR(190) NULL','ns2'=>'VARCHAR(190) NULL','panel_type'=>'VARCHAR(80) NULL'] as $col=>$def){ try{ db()->exec("ALTER TABLE hosting_accounts ADD COLUMN {$col} {$def}"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
            $cols=[]; try{ foreach(db()->query('SHOW COLUMNS FROM hosting_accounts')->fetchAll() as $c){ $cols[$c['Field']]=true; } }catch(Throwable $e){ $cols=[]; }
            $serverId=(int)($_POST['server_id'] ?? 0);
            if($serverId>0){
                $sq=db()->prepare('SELECT * FROM server_nodes WHERE id=? LIMIT 1'); $sq->execute([$serverId]); $srv=$sq->fetch();
                if($srv){
                    $host=$srv['hostname'] ?: ($srv['name'] ?? '');
                    $_POST['server_name']=$host ?: ($srv['name'] ?? '');
                    $_POST['server_ip']=$srv['ip_address'] ?? ($_POST['server_ip'] ?? '');
                    if(empty($_POST['cpanel_url']) && $host) $_POST['cpanel_url']='https://'.$host.':2083';
                    if(empty($_POST['webmail_url']) && $host) $_POST['webmail_url']='https://'.$host.':2096';
                    if(empty($_POST['whm_url']) && $host) $_POST['whm_url']='https://'.$host.':2087';
                }
            }
            $allowed=['server_id','server_name','server_ip','whm_username','username','panel_password','package_name','cpanel_url','webmail_url','whm_url','directadmin_url','plesk_url','vps_panel_url','ns1','ns2','panel_type'];
            if(!empty($_POST['whm_username']) && empty($_POST['username'])) $_POST['username']=$_POST['whm_username'];
            $oldHosting=[]; try{ $oh=db()->prepare('SELECT h.*, s.domain, s.customer_id FROM hosting_accounts h LEFT JOIN services s ON s.id=h.service_id WHERE h.service_id=? LIMIT 1'); $oh->execute([$serviceId]); $oldHosting=$oh->fetch() ?: []; }catch(Throwable $e){}
            if(array_key_exists('panel_password', $_POST) && trim((string)$_POST['panel_password'])!=='' && trim((string)($_POST['panel_password'])) !== trim((string)($oldHosting['panel_password'] ?? ''))){
                $sync=ao_hosting_panel_change_password($oldHosting ?: ['service_id'=>$serviceId,'customer_id'=>$customerId], trim((string)$_POST['panel_password']));
                if(empty($sync['ok'])) throw new Exception($sync['message'] ?? 'Sunucu şifre değişikliğini kabul etmedi.');
            }
            $set=[]; $params=[];
            foreach($allowed as $col){ if(isset($cols[$col]) && array_key_exists($col,$_POST)){ $set[]="{$col}=?"; $params[]=trim((string)$_POST[$col]); } }
            $exists=false; try{ $st=db()->prepare('SELECT id FROM hosting_accounts WHERE service_id=? LIMIT 1'); $st->execute([$serviceId]); $exists=(bool)$st->fetchColumn(); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
            if($set){
                if($exists){ $params[]=$serviceId; db()->prepare('UPDATE hosting_accounts SET '.implode(', ',$set).' WHERE service_id=?')->execute($params); }
                else {
                    $insertCols=['service_id']; $insertVals=['?']; $insertParams=[$serviceId];
                    foreach($allowed as $col){ if(isset($cols[$col]) && array_key_exists($col,$_POST)){ $insertCols[]=$col; $insertVals[]='?'; $insertParams[]=trim((string)$_POST[$col]); } }
                    db()->prepare('INSERT INTO hosting_accounts('.implode(',',$insertCols).') VALUES('.implode(',',$insertVals).')')->execute($insertParams);
                }
                if(array_key_exists('panel_password', $_POST) && trim((string)$_POST['panel_password'])!=='' && trim((string)($_POST['panel_password'])) !== trim((string)($oldHosting['panel_password'] ?? ''))){
                    $nh=db()->prepare('SELECT h.*, s.domain, s.customer_id FROM hosting_accounts h LEFT JOIN services s ON s.id=h.service_id WHERE h.service_id=? LIMIT 1'); $nh->execute([$serviceId]); $newHosting=$nh->fetch() ?: $oldHosting;
                    $newHosting['customer_id']=$customerId; ao_hosting_notify_credentials($newHosting, trim((string)$_POST['panel_password']), 'hosting_password_changed');
                }
            }
        }
        if(function_exists('ao_customer_log')) ao_customer_log($customerId,'service.updated','Müşteri hizmet kaydı güncellendi: service #'.$serviceId);
        flash('success','Müşteriye ait hizmet kaydı güncellendi.');
    }catch(Throwable $e){ flash('error','Hizmet kaydı güncellenemedi: '.$e->getMessage()); }
    redirect_to('admin/customers/service?id='.$serviceId.'&customer_id='.$customerId);
}

// v7.3.0 Customer Profile Pro - admin service/domain actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/customers/service-action') {
    $customerId=(int)($_POST['customer_id']??0); $serviceId=(int)($_POST['service_id']??0); $action=trim($_POST['service_action']??'');
    $allowed=['suspend'=>'suspended','unsuspend'=>'active','terminate'=>'terminated','activate'=>'active','pending'=>'pending'];
    if($customerId>0 && $serviceId>0){
        try{
            if(isset($allowed[$action])){
                $q=db()->prepare('UPDATE services SET status=? WHERE id=? AND customer_id=?'); $q->execute([$allowed[$action],$serviceId,$customerId]);
                flash('success','Hizmet durumu güncellendi: '.$allowed[$action]);
            } elseif($action==='change-package'){
                $package=trim($_POST['package_name']??'');
                if($package!=='') { $q=db()->prepare('UPDATE hosting_accounts h JOIN services s ON s.id=h.service_id SET h.package_name=? WHERE h.service_id=? AND s.customer_id=?'); $q->execute([$package,$serviceId,$customerId]); flash('success','Hosting paketi güncellendi.'); }
            } elseif($action==='change-password'){
                $pass=trim($_POST['panel_password']??'');
                if($pass!=='') { $q=db()->prepare('UPDATE hosting_accounts h JOIN services s ON s.id=h.service_id SET h.panel_password=? WHERE h.service_id=? AND s.customer_id=?'); $q->execute([$pass,$serviceId,$customerId]); flash('success','Panel şifresi güncellendi.'); }
            } elseif($action==='move-server'){
                $server=trim($_POST['server_name']??''); $ip=trim($_POST['server_ip']??'');
                $q=db()->prepare('UPDATE hosting_accounts h JOIN services s ON s.id=h.service_id SET h.server_name=?, h.server_ip=? WHERE h.service_id=? AND s.customer_id=?'); $q->execute([$server,$ip,$serviceId,$customerId]); flash('success','Sunucu bilgileri güncellendi.');
            } else { flash('error','Geçersiz hizmet işlemi.'); }
        } catch(Throwable $e){ flash('error','Hizmet işlemi tamamlanamadı.'); }
    }
    if(trim((string)($_POST['return_to'] ?? '')) === 'service') { redirect_to('admin/customers/service?id='.$serviceId.'&customer_id='.$customerId); }
    redirect_to('admin/customers/view?id='.$customerId.ao_tab_hash('urunler'));
}

// Admin: delete customer notification log (uses controller)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/customers/notification-delete') {
    require_admin(); verify_csrf(); $id=(int)($_POST['id']??0); $cid=(int)($_POST['customer_id']??0);
    if($id){ try{ require_once dirname(__DIR__) . '/Controllers/Admin/AnnouncementsController.php';
            if (admin_announcement_delete($id)) { flash('success','Mesaj silindi.'); } else { flash('error','Silme başarısız.'); }
        }catch(Throwable $e){ flash('error','Silme başarısız.'); } }
    redirect_to('admin/customers/view?id='.$cid.ao_tab_hash('epostalar'));
}

// Admin: edit customer notification log (uses controller)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/customers/notification-edit') {
    require_admin(); verify_csrf(); $id=(int)($_POST['id']??0); $cid=(int)($_POST['customer_id']??0);
    $subject = trim((string)($_POST['subject']??'')); $message = trim((string)($_POST['message']??'')); $recipient = trim((string)($_POST['recipient']??'')); $status = trim((string)($_POST['status']??''));
    if($id){ try{ require_once dirname(__DIR__) . '/Controllers/Admin/AnnouncementsController.php';
            if (admin_announcement_update($id, ['subject'=>$subject,'message'=>$message,'recipient'=>$recipient,'status'=>$status])) { flash('success','Mesaj güncellendi.'); } else { flash('error','Güncelleme başarısız.'); }
        }catch(Throwable $e){ flash('error','Güncelleme başarısız.'); } }
    redirect_to('admin/customers/view?id='.$cid.ao_tab_hash('epostalar'));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/customers/domain-action') {
    $customerId=(int)($_POST['customer_id']??0); $domainId=(int)($_POST['domain_id']??0); $action=trim($_POST['domain_action']??'');
    if($customerId>0 && $domainId>0){
        try{
            if($action==='renew'){
                $d = ao_domain_row($domainId, $customerId); if(!$d) throw new Exception('Domain bulunamadı.');
                $res = ao_domain_create_renewal_order($d, (int)($_POST['years'] ?? 1), 'admin');
                flash('success','Domain doğrudan yenilenmedi; yenileme siparişi ve faturası oluşturuldu. Sipariş: #'.$res['order_id']);
            } elseif($action==='toggle-lock'){
                $q=db()->prepare('UPDATE domains SET lock_status=IF(lock_status=1,0,1) WHERE id=? AND customer_id=?'); $q->execute([$domainId,$customerId]); flash('success','Domain kilit durumu değiştirildi.');
            } elseif($action==='toggle-autorenew'){
                $q=db()->prepare('UPDATE domains SET auto_renew=IF(auto_renew=1,0,1) WHERE id=? AND customer_id=?'); $q->execute([$domainId,$customerId]); flash('success','Otomatik yenileme durumu değiştirildi.');
            } elseif($action==='update-epp'){
                $d = ao_domain_row($domainId, $customerId); if(!$d) throw new Exception('Domain bulunamadı.');
                if (trim($_POST['epp_code'] ?? '') !== '') { $epp=trim($_POST['epp_code']); $q=db()->prepare('UPDATE domains SET epp_code=? WHERE id=? AND customer_id=?'); $q->execute([$epp,$domainId,$customerId]); flash('success','EPP kodu manuel güncellendi.'); }
                else { $res = ao_domain_generate_epp($d); flash($res['ok']?'success':'error', $res['message']); }
            } elseif($action==='transfer'){
                $q=db()->prepare('UPDATE domains SET status="transfer_pending" WHERE id=? AND customer_id=?'); $q->execute([$domainId,$customerId]); flash('success','Domain transfer sürecine alındı.');
            } elseif($action==='update-registrar'){
                $registrar=trim($_POST['registrar']??''); $q=db()->prepare('UPDATE domains SET registrar=? WHERE id=? AND customer_id=?'); $q->execute([$registrar,$domainId,$customerId]); flash('success','Registrar bilgisi güncellendi.');
            } else { flash('error','Geçersiz domain işlemi.'); }
        } catch(Throwable $e){ flash('error','Domain işlemi tamamlanamadı.'); }
    }
    redirect_to('admin/customers/view?id='.$customerId.ao_tab_hash('domainler'));
}



// v7.3.0 Domain Center Pro - registrar, DNS, nameserver and client domain actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/domain-center/registrar-save') {
    $id=(int)($_POST['registrar_id']??0);
    $status=trim($_POST['status']??'inactive'); $test=(int)($_POST['test_mode']??0); $tlds=trim($_POST['supported_tlds']??'');
    try{
        $q=db()->prepare('UPDATE domain_registrars SET status=?, test_mode=?, supported_tlds=? WHERE id=?'); $q->execute([$status,$test,$tlds,$id]);
        $incomingConfig = $_POST['config'] ?? [];
        // DomainNameAPI için ana endpoint opsiyoneldir. Yanlışlıkla ?singlewsdl veya eski api.domainnameapi.com girildiyse temizlenir;
        // doğru canlı/test endpoint kod tarafından domain_registrars.test_mode değerine göre seçilir.
        $regSlug = '';
        try { $rs=db()->prepare('SELECT slug,module_name FROM domain_registrars WHERE id=? LIMIT 1'); $rs->execute([$id]); $rr=$rs->fetch(); $regSlug=strtolower(($rr['slug']??'').' '.($rr['module_name']??'')); } catch(Throwable $ignore) {}
        if (str_contains($regSlug,'domainnameapi') || str_contains($regSlug,'dna')) {
            $incomingConfig['auth_mode'] = 'apikey';
            $incomingConfig['test_mode'] = (string)$test;
            if (!empty($incomingConfig['api_endpoint']) && str_contains((string)$incomingConfig['api_endpoint'], 'domainnameapi.com')) {
                $incomingConfig['api_endpoint'] = '';
            }
        }
        foreach($incomingConfig as $k=>$v){
            $secret=in_array($k,['api_password','password','token','secret','api_key','api_secret','ote_api_key'],true)?1:0;
            $value=trim((string)$v);
            // Maskelenmiş veya boş şifre gönderildiyse eski gizli değeri silme.
            if ($secret && ($value==='' || preg_match('/^\*+$/',$value))) continue;
            $u=db()->prepare('INSERT INTO registrar_configs(registrar_id,config_key,config_value,is_secret) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE config_value=VALUES(config_value), is_secret=VALUES(is_secret)');
            $u->execute([$id,$k,$value,$secret]);
        }
        flash('success','Registrar yapılandırması kaydedildi.');
    }catch(Throwable $e){ flash('error','Registrar yapılandırması kaydedilemedi.'); }
    redirect_to('admin/domain-center/registrars');
}
if ($route === 'admin/domain-center/registrar-test') {
    require_admin();
    $id = (int)($_GET['id'] ?? 0);
    $domain = ahost_domain_clean($_GET['domain'] ?? 'example.com');
    try {
        $bundle = ao_registrar_bundle_by_id($id);
        if (!$bundle) { flash('error','Registrar bulunamadı.'); redirect_to('admin/domain-center/registrars'); }
        $res = ao_registrar_api_call($bundle, 'test', $domain);
        if ($res['ok']) { $decoded=json_decode($res['body']??'',true); $extra=''; if(is_array($decoded)){ $name=ao_find_deep($decoded,['name','Name','ResellerName']) ?: ''; $balance=ao_find_deep($decoded,['balance','Balance']) ?: ''; $currency=ao_find_deep($decoded,['currency','Currency']) ?: ''; $extra=trim(' '.$name.' '.$balance.' '.$currency); } flash('success', 'Registrar bağlantı testi başarılı.'.$extra.' Loglardan detay görülebilir.'); }
        else flash('error', 'Registrar bağlantı testi başarısız: '.$res['message'].' HTTP '.$res['code'].' Method: '.($res['method'] ?? 'test').'. API Logs ekranından ham yanıtı kontrol edin.');
    } catch (Throwable $e) { flash('error','Registrar bağlantı testi hata verdi: '.$e->getMessage()); }
    redirect_to('admin/domain-center/registrars');
}


// RC33: Domain Center single/all sync routes. Fixes /admin/domain-center/sync-all 404.
function ao_rc33_domain_sync_ensure_schema(){ static $done=false; if($done) return; $done=true;
    if(function_exists('ao_schema_ensure_v1200')) ao_schema_ensure_v1200();
    try{ db()->exec("ALTER TABLE domains ADD COLUMN last_synced_at DATETIME NULL"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("ALTER TABLE domains ADD COLUMN last_sync_status VARCHAR(40) NULL"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("ALTER TABLE domains ADD COLUMN last_sync_message TEXT NULL"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("ALTER TABLE domains ADD COLUMN nameservers TEXT NULL"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_rc33_domain_parse_date($value){
    $value=trim((string)$value); if($value==='') return null;
    $ts=strtotime($value); if(!$ts) return null;
    return date('Y-m-d',$ts);
}
function ao_rc33_domain_extract_nameservers($arr, $raw=''){
    $value = is_array($arr) ? ao_find_deep($arr, ['NameServers','Nameservers','nameservers','NameServer','nameserver','NS','ns']) : null;
    $list = [];
    $push = function($v) use (&$list) {
        if(is_array($v)){
            foreach($v as $item) $list[] = is_array($item) ? (string)($item['Name'] ?? $item['Host'] ?? $item['value'] ?? reset($item)) : (string)$item;
        } elseif($v !== null && $v !== '') {
            foreach(preg_split('/[\s,;]+/', (string)$v) as $item) $list[] = $item;
        }
    };
    $push($value);
    if(!$list && preg_match_all('/\bns[0-9a-z-]*\.[a-z0-9.-]+\b/i', (string)$raw, $m)) $list = $m[0];
    $list = array_values(array_unique(array_filter(array_map(function($ns){
        $ns = strtolower(trim((string)$ns));
        return preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $ns) ? $ns : '';
    }, $list))));
    return $list;
}
function ao_rc33_domain_sync_row($domainRow){
    ao_rc33_domain_sync_ensure_schema();
    $id=(int)($domainRow['id'] ?? 0);
    $domain=ahost_domain_clean($domainRow['domain_name'] ?? '');
    if($id<=0 || $domain==='') return ['ok'=>false,'message'=>'Domain kaydı geçersiz.'];
    $registrar=$domainRow['registrar'] ?? ($domainRow['registrar_name'] ?? 'domainnameapi');
    $status='success'; $message='Yerel kayıt senkron kontrolünden geçti.'; $raw='';
    try{
        $bundle=function_exists('ao_domain_registrar_bundle') ? ao_domain_registrar_bundle($domainRow) : null;
        if($bundle){
            $api=ao_registrar_api_call($bundle,'sync',$domain);
            if(empty($api['ok'])) $api=ao_registrar_api_call($bundle,'whois',$domain);
            $raw=(string)($api['body'] ?? '');
            if(!empty($api['ok'])){
                $arr=ao_json_xml_to_array($raw);
                $expiry=ao_rc33_domain_parse_date(ao_find_deep($arr,['ExpiryDate','ExpirationDate','RegistryExpiryDate','expires','expiry_date','EndDate','ExpireDate']) ?? '');
                $created=ao_rc33_domain_parse_date(ao_find_deep($arr,['CreationDate','CreatedDate','RegisterDate','registration_date','StartDate']) ?? '');
                $remoteStatus=(string)(ao_find_deep($arr,['Status','status','DomainStatus']) ?? '');
                $lock=ao_find_deep($arr,['LockStatus','TransferLock','TheftProtectionLock','lock_status']);
                $nameservers=ao_rc33_domain_extract_nameservers($arr,$raw);
                $sets=['last_synced_at=NOW()','last_sync_status=?','last_sync_message=?']; $params=['success','Registrar yanıtı alındı.'];
                if($expiry){ $sets[]='expiry_date=?'; $sets[]='next_due_date=?'; $params[]=$expiry; $params[]=$expiry; }
                if($created){ $sets[]='registration_date=?'; $params[]=$created; }
                if($remoteStatus!==''){ $sets[]='status=?'; $params[]=substr($remoteStatus,0,40); }
                if($lock!==null && $lock!=='') { $sets[]='lock_status=?'; $params[]=(preg_match('/^(1|true|locked|enable|enabled)$/i',(string)$lock)?1:0); }
                if($nameservers){ $sets[]='nameservers=?'; $params[]=implode("\n",$nameservers); }
                $params[]=$id;
                db()->prepare('UPDATE domains SET '.implode(',',$sets).' WHERE id=?')->execute($params);
                $message='Registrar yanıtı alındı ve yerel kayıt güncellendi.';
            } else {
                $status='failed'; $message=$api['message'] ?? 'Registrar yanıtı alınamadı.';
                db()->prepare('UPDATE domains SET last_synced_at=NOW(), last_sync_status=?, last_sync_message=? WHERE id=?')->execute([$status,$message,$id]);
            }
        } else {
            db()->prepare('UPDATE domains SET last_synced_at=NOW(), last_sync_status="success", last_sync_message="Registrar bağlantısı yok; yerel kayıt doğrulandı." WHERE id=?')->execute([$id]);
            $message='Registrar bağlantısı bulunmadı; yerel kayıt doğrulandı.';
        }
    }catch(Throwable $e){
        $status='failed'; $message=$e->getMessage();
        try{ db()->prepare('UPDATE domains SET last_synced_at=NOW(), last_sync_status=?, last_sync_message=? WHERE id=?')->execute([$status,$message,$id]); }catch(Throwable $ignore){}
    }
    try{ db()->prepare('INSERT INTO domain_operation_logs(domain_id,domain_name,operation,registrar,status,message,raw_response) VALUES(?,?,?,?,?,?,?)')->execute([$id,$domain,'sync',$registrar,$status,$message,substr($raw,0,5000)]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return ['ok'=>$status==='success','message'=>$message];
}
if ($route === 'admin/domain-center/sync' || $route === 'admin/domain-center/sync-all') {
    require_admin(); ao_rc33_domain_sync_ensure_schema();
    $ok=0; $fail=0;
    try{
        if($route === 'admin/domain-center/sync'){
            $id=(int)($_GET['id'] ?? 0);
            $q=db()->prepare('SELECT * FROM domains WHERE id=? LIMIT 1'); $q->execute([$id]); $row=$q->fetch();
            if(!$row) throw new Exception('Domain bulunamadı.');
            $res=ao_rc33_domain_sync_row($row); $res['ok'] ? $ok++ : $fail++;
            flash($res['ok']?'success':'error','Domain senkronizasyonu: '.$res['message']);
            redirect_to('admin/domain-center/view?id='.$id);
        }
        $rows=db()->query('SELECT * FROM domains ORDER BY id DESC')->fetchAll() ?: [];
        foreach($rows as $row){ $res=ao_rc33_domain_sync_row($row); $res['ok'] ? $ok++ : $fail++; }
        flash($fail?'warning':'success',$ok.' domain güncellendi, '.$fail.' domain hata verdi. Detaylar Operasyon Loglarında.');
    }catch(Throwable $e){ flash('error','Domain senkronizasyonu tamamlanamadı: '.$e->getMessage()); }
    redirect_to('admin/domain-center');
}


if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/domain-center/bulk-operation') { require_admin(); verify_csrf(); $op=trim($_POST['operation']??''); $domains=preg_split('/\R+/', trim($_POST['domains']??'')); $count=0; try{ foreach($domains as $dn){ $dn=ahost_domain_clean($dn); if(!$dn) continue; db()->prepare('INSERT INTO domain_operation_logs(domain_name,operation,registrar,status,message) VALUES(?,?,?,?,?)')->execute([$dn,$op,'auto','queued','Toplu operasyon kuyruğa alındı.']); $count++; } flash('success',$count.' domain için toplu operasyon kuyruğa alındı.'); }catch(Throwable $e){ flash('error','Toplu işlem kaydedilemedi: '.$e->getMessage()); } redirect_to('admin/domain-center/operations'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/domain-center/smart-pricing-save') {
    require_admin(); verify_csrf(); ao_schema_ensure_v810();
    try {
        $tld='.'.ltrim(strtolower(trim($_POST['tld'] ?? '.com')),'.');
        $mode=trim($_POST['mode'] ?? 'percent');
        $percent=(float)($_POST['markup_percent'] ?? 30); $fixed=(float)($_POST['markup_fixed'] ?? 0); $min=(float)($_POST['min_profit'] ?? 0);
        $currency=trim($_POST['currency'] ?? 'USD'); $override=trim($_POST['registrar_override'] ?? '');
        db()->prepare('INSERT INTO domain_pricing_rules(tld,mode,markup_percent,markup_fixed,min_profit,currency,registrar_override,is_active) VALUES(?,?,?,?,?,?,?,1) ON DUPLICATE KEY UPDATE mode=VALUES(mode),markup_percent=VALUES(markup_percent),markup_fixed=VALUES(markup_fixed),min_profit=VALUES(min_profit),currency=VALUES(currency),registrar_override=VALUES(registrar_override),is_active=1')->execute([$tld,$mode,$percent,$fixed,$min,$currency,$override?:null]);
        flash('success','Akıllı domain fiyat kuralı kaydedildi.');
    } catch(Throwable $e) { flash('error','Fiyat kuralı kaydedilemedi: '.$e->getMessage()); }
    redirect_to('admin/domain-center/smart-pricing');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/domain-center/registrar-cost-save') {
    require_admin(); verify_csrf(); ao_schema_ensure_v810();
    try {
        $registrar=trim($_POST['registrar_slug'] ?? 'domainnameapi'); $tld='.'.ltrim(strtolower(trim($_POST['tld'] ?? '.com')),'.'); $action=trim($_POST['action'] ?? 'register');
        $cost=(float)($_POST['cost'] ?? 0); $currency=trim($_POST['currency'] ?? 'USD');
        db()->prepare('INSERT INTO registrar_price_cache(registrar_slug,tld,action,cost,currency,source,last_checked_at) VALUES(?,?,?,?,? ,"manual",NOW()) ON DUPLICATE KEY UPDATE cost=VALUES(cost),currency=VALUES(currency),source="manual",last_checked_at=NOW()')->execute([$registrar,$tld,$action,$cost,$currency]);
        flash('success','Registrar alış fiyatı kaydedildi.');
    } catch(Throwable $e) { flash('error','Alış fiyatı kaydedilemedi: '.$e->getMessage()); }
    redirect_to('admin/domain-center/smart-pricing');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/accounting/payment-fee-save') {
    require_admin(); verify_csrf(); ao_schema_ensure_v900();
    try {
        $gateway=trim($_POST['gateway'] ?? 'paytr'); $label=trim($_POST['label'] ?? $gateway); $line=trim($_POST['invoice_line_label'] ?? 'Kart İşlem Komisyonu');
        $percent=(float)($_POST['fee_percent'] ?? 0); $fixed=(float)($_POST['fee_fixed'] ?? 0); $currency=trim($_POST['currency'] ?? 'TRY');
        $apiEnabled=!empty($_POST['api_enabled'])?1:0; $apiEndpoint=trim($_POST['api_endpoint'] ?? ''); $auth=trim($_POST['api_auth_json'] ?? '');
        db()->prepare('INSERT INTO payment_fee_rules(gateway,label,invoice_line_label,fee_percent,fee_fixed,last_known_fee_percent,last_known_fee_fixed,currency,payer_mode,rate_source,api_enabled,api_endpoint,api_auth_json,is_active) VALUES(?,?,?,?,?,?,?,?,"customer",?,?,?,?,1) ON DUPLICATE KEY UPDATE label=VALUES(label),invoice_line_label=VALUES(invoice_line_label),fee_percent=VALUES(fee_percent),fee_fixed=VALUES(fee_fixed),last_known_fee_percent=VALUES(last_known_fee_percent),last_known_fee_fixed=VALUES(last_known_fee_fixed),currency=VALUES(currency),payer_mode="customer",api_enabled=VALUES(api_enabled),api_endpoint=VALUES(api_endpoint),api_auth_json=VALUES(api_auth_json),is_active=1')->execute([$gateway,$label,$line,$percent,$fixed,$percent,$fixed,$currency,$apiEnabled?'api':'manual',$apiEnabled,$apiEndpoint,$auth]);
        flash('success','Kart işlem komisyonu kaydedildi. Komisyon her zaman müşterinin faturasına ayrı satır olarak eklenir.');
    } catch(Throwable $e) { flash('error','Komisyon kuralı kaydedilemedi: '.$e->getMessage()); }
    redirect_to('admin/accounting/payment-fees');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/accounting/payment-fee-sync') {
    require_admin(); verify_csrf();
    $gateway=trim($_POST['gateway'] ?? '');
    $res=ao_payment_commission_sync($gateway,true);
    flash($res['ok']?'success':'error',$res['message']);
    redirect_to('admin/accounting/payment-fees');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/domain-center/pricing-save') {
    $tld=trim($_POST['tld']??''); $reg=(float)($_POST['register_price']??0); $tr=(float)($_POST['transfer_price']??0); $ren=(float)($_POST['renew_price']??0); $restore=(float)($_POST['restore_price']??0); $backorder=(float)($_POST['backorder_price']??0); $cur=trim($_POST['currency']??'TRY'); $registrar=trim($_POST['registrar_slug']??'domainnameapi');
    if($tld){ try{ ao_v2410_ensure_schema(); $q=db()->prepare('INSERT INTO tld_pricing(tld,register_price,transfer_price,renew_price,restore_price,backorder_price,currency,registrar_slug,is_active) VALUES(?,?,?,?,?,?,?,?,1) ON DUPLICATE KEY UPDATE register_price=VALUES(register_price),transfer_price=VALUES(transfer_price),renew_price=VALUES(renew_price),restore_price=VALUES(restore_price),backorder_price=VALUES(backorder_price),currency=VALUES(currency),registrar_slug=VALUES(registrar_slug)'); $q->execute([$tld,$reg,$tr,$ren,$restore,$backorder,$cur,$registrar]); flash('success','TLD fiyatı kaydedildi.'); }catch(Throwable $e){ flash('error','TLD fiyatı kaydedilemedi.'); } }
    redirect_to('admin/domain-center/pricing');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($route === 'admin/domain-center/dns-save' || $route === 'client/domains/dns-save')) {
    $domainId=(int)($_POST['domain_id']??0); $customerId=(int)($_POST['customer_id']??0); $type=trim($_POST['record_type']??'A'); $host=trim($_POST['host']??'@'); $value=trim($_POST['record_value']??''); $priority=($_POST['priority']??'')===''?null:(int)$_POST['priority']; $ttl=(int)($_POST['ttl']??3600);
    if(str_starts_with($route,'client/')){ require_customer(); $cc=current_customer(); $customerId=(int)$cc['id']; }
    if($domainId && $value){ try{ $chk=db()->prepare('SELECT id FROM domains WHERE id=? AND customer_id=?'); $chk->execute([$domainId,$customerId]); if($chk->fetch()){ $q=db()->prepare('INSERT INTO domain_dns_records(domain_id,record_type,host,record_value,priority,ttl) VALUES(?,?,?,?,?,?)'); $q->execute([$domainId,$type,$host,$value,$priority,$ttl]); flash('success','DNS kaydı eklendi.'); } }catch(Throwable $e){ flash('error','DNS kaydı eklenemedi.'); } }
    redirect_to(str_starts_with($route,'client/') ? 'client/domains/view?id='.$domainId.ao_tab_hash('dns') : 'admin/domain-center/view?id='.$domainId.'#dns');
}
if (($route === 'admin/domain-center/dns-delete' || $route === 'client/domains/dns-delete')) {
    $id=(int)($_GET['id']??0); $domainId=(int)($_GET['domain_id']??0); $customerId=(int)($_GET['customer_id']??0);
    if(str_starts_with($route,'client/')){ require_customer(); $cc=current_customer(); $customerId=(int)$cc['id']; }
    try{ $q=db()->prepare('DELETE r FROM domain_dns_records r JOIN domains d ON d.id=r.domain_id WHERE r.id=? AND r.domain_id=? AND d.customer_id=?'); $q->execute([$id,$domainId,$customerId]); flash('success','DNS kaydı silindi.'); }catch(Throwable $e){ flash('error','DNS kaydı silinemedi.'); }
    redirect_to(str_starts_with($route,'client/') ? 'client/domains/view?id='.$domainId.ao_tab_hash('dns') : 'admin/domain-center/view?id='.$domainId.'#dns');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($route === 'admin/domain-center/ns-save' || $route === 'client/domains/ns-save')) {
    $domainId=(int)($_POST['domain_id']??0); $customerId=(int)($_POST['customer_id']??0);
    if(str_starts_with($route,'client/')){ require_customer(); $cc=current_customer(); $customerId=(int)$cc['id']; }
    $ns=[trim($_POST['ns1']??''),trim($_POST['ns2']??''),trim($_POST['ns3']??''),trim($_POST['ns4']??'')];
    try{ $chk=db()->prepare('SELECT id FROM domains WHERE id=? AND customer_id=?'); $chk->execute([$domainId,$customerId]); if($chk->fetch()){ $q=db()->prepare('INSERT INTO domain_nameservers(domain_id,ns1,ns2,ns3,ns4) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE ns1=VALUES(ns1),ns2=VALUES(ns2),ns3=VALUES(ns3),ns4=VALUES(ns4)'); $q->execute([$domainId,$ns[0],$ns[1],$ns[2],$ns[3]]); flash('success','Nameserver bilgileri güncellendi.'); } }catch(Throwable $e){ flash('error','Nameserver güncellenemedi.'); }
    redirect_to(str_starts_with($route,'client/') ? 'client/domains/view?id='.$domainId.ao_tab_hash('nameserver') : 'admin/domain-center/view?id='.$domainId.'#nameserver');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'client/domains/action') {
    require_customer(); $cc=current_customer(); $domainId=(int)($_POST['domain_id']??0); $action=trim($_POST['domain_action']??'');
    try{
        if($action==='renew'){ $d=ao_domain_row($domainId,$cc['id']); if(!$d) throw new Exception('Domain bulunamadı.'); $res=ao_domain_create_renewal_order($d,(int)($_POST['years']??1),'client'); flash('success','Yenileme talebi sipariş/fatura olarak oluşturuldu. Ödeme sonrası registrar yenilemesi çalışacak. Sipariş: #'.$res['order_id']); }
        elseif($action==='request-epp'){ $d=ao_domain_row($domainId,$cc['id']); if(!$d) throw new Exception('Domain bulunamadı.'); $res=ao_domain_generate_epp($d); flash($res['ok']?'success':'error',$res['message']); }
        elseif($action==='toggle-lock'){ $q=db()->prepare('UPDATE domains SET lock_status=IF(lock_status=1,0,1) WHERE id=? AND customer_id=?'); $q->execute([$domainId,$cc['id']]); flash('success','Domain kilit durumu değiştirildi.'); }
        elseif($action==='toggle-autorenew'){ $q=db()->prepare('UPDATE domains SET auto_renew=IF(auto_renew=1,0,1) WHERE id=? AND customer_id=?'); $q->execute([$domainId,$cc['id']]); flash('success','Oto yenileme güncellendi.'); }
        elseif($action==='transfer'){ $q=db()->prepare('UPDATE domains SET status="transfer_pending" WHERE id=? AND customer_id=?'); $q->execute([$domainId,$cc['id']]); flash('success','Transfer talebi oluşturuldu.'); }
    }catch(Throwable $e){ flash('error','Domain işlemi tamamlanamadı.'); }
    redirect_to('client/domains/view?id='.$domainId.ao_tab_hash('islem'));
}

// Customer profile and support form actions.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'client/profile') {
    require_customer(); verify_csrf(); $c=current_customer();
    $first=trim($_POST['first_name']??''); $last=trim($_POST['last_name']??'');
    try{
        if(!$first || !$last) throw new Exception('Ad ve soyad zorunludur.');
        $allowed=['first_name','last_name','company_name','phone','mobile','country','city','district','postcode','tax_office','tax_number','address'];
        $cols=[]; try{ foreach(db()->query('SHOW COLUMNS FROM customers')->fetchAll() as $col){ $cols[$col['Field'] ?? $col[0]]=true; } }catch(Throwable $x){}
        $sets=[]; $params=[];
        foreach($allowed as $key){ if(isset($cols[$key])){ $sets[]="$key=?"; $params[]=trim((string)($_POST[$key] ?? '')); } }
        if($sets){ $params[]=(int)$c['id']; db()->prepare('UPDATE customers SET '.implode(',',$sets).' WHERE id=?')->execute($params); }
        flash('success','Profil bilgileriniz güncellendi.');
    }catch(Throwable $e){ flash('error','Profil güncellenemedi: '.$e->getMessage()); }
    redirect_to('client/profile');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'client/support/reply') {
    require_customer(); verify_csrf(); $c=current_customer();
    $ticketId=(int)($_POST['ticket_id']??0); $message=trim((string)($_POST['message']??''));
    try{
        if(!$ticketId || $message==='') throw new Exception('Talep ve mesaj zorunlu.');
        $q=db()->prepare('SELECT * FROM tickets WHERE id=? AND customer_id=? LIMIT 1'); $q->execute([$ticketId,(int)$c['id']]); $ticket=$q->fetch();
        if(!$ticket) throw new Exception('Destek talebi bulunamadı.');
        db()->prepare('INSERT INTO ticket_replies(ticket_id,sender_type,message) VALUES(?,"customer",?)')->execute([$ticketId,$message]);
        if(strtolower((string)($ticket['status']??''))!=='open') db()->prepare('UPDATE tickets SET status="open" WHERE id=?')->execute([$ticketId]);
        flash('success','Yanıtınız eklendi. Talep yeniden açık duruma alındı.');
    }catch(Throwable $e){ flash('error','Yanıt eklenemedi: '.$e->getMessage()); }
    redirect_to('client/support?ticket_id='.$ticketId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'client/support') {
    require_customer(); $c=current_customer(); $subject=trim($_POST['subject']??''); $department=trim($_POST['department']??'Genel'); $priority=trim($_POST['priority']??'medium'); $message=trim($_POST['message']??'');
    if ($subject && $message) { $q=db()->prepare('INSERT INTO tickets(customer_id,subject,department,priority,status) VALUES(?,?,?,?,"open")'); $q->execute([$c['id'],$subject,$department,$priority]); $tid=db()->lastInsertId(); $r=db()->prepare('INSERT INTO ticket_replies(ticket_id,sender_type,message) VALUES(?,"customer",?)'); $r->execute([$tid,$message]); flash('success','Destek talebiniz oluşturuldu.'); }
    else { flash('error','Konu ve mesaj zorunludur.'); }
    redirect_to('client/support');
}
