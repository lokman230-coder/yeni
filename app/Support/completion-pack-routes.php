<?php
// v24.1.0 - Completion Pack: Live Chat, Account User permissions, Domain price sync, Menu fallback helpers
function ao_v2410_ensure_schema(){ static $done=false; if($done) return; $done=true;
    if(function_exists('ao_v2332_ensure_schema')) ao_v2332_ensure_schema();
    if(function_exists('ao_v23_ensure_schema')) ao_v23_ensure_schema();
    try{ db()->exec("CREATE TABLE IF NOT EXISTS support_live_chats (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NULL, visitor_name VARCHAR(190) NULL, visitor_email VARCHAR(190) NULL, department VARCHAR(120) DEFAULT 'Teknik Destek', subject VARCHAR(255) DEFAULT 'Canlı Sohbet', status VARCHAR(40) DEFAULT 'waiting', assigned_admin_id INT NULL, source_url VARCHAR(255) NULL, started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, closed_at DATETIME NULL, updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, KEY status(status), KEY customer_id(customer_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("CREATE TABLE IF NOT EXISTS support_live_messages (id INT AUTO_INCREMENT PRIMARY KEY, chat_id INT NOT NULL, sender_type VARCHAR(40) DEFAULT 'visitor', sender_id INT NULL, sender_name VARCHAR(190) NULL, message LONGTEXT NOT NULL, is_read TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY chat_id(chat_id), KEY sender_type(sender_type)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("CREATE TABLE IF NOT EXISTS customer_user_sessions (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NOT NULL, account_user_id INT NULL, ip_address VARCHAR(80) NULL, user_agent VARCHAR(255) NULL, last_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, is_active TINYINT(1) DEFAULT 1, KEY customer_id(customer_id), KEY account_user_id(account_user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("CREATE TABLE IF NOT EXISTS domain_price_import_logs (id INT AUTO_INCREMENT PRIMARY KEY, registrar_slug VARCHAR(120), source VARCHAR(120) DEFAULT 'manual', imported_count INT DEFAULT 0, message TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY registrar_slug(registrar_slug)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("CREATE TABLE IF NOT EXISTS module_update_logs (id INT AUTO_INCREMENT PRIMARY KEY, module_key VARCHAR(120), action VARCHAR(80), status VARCHAR(40) DEFAULT 'success', message TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY module_key(module_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("CREATE TABLE IF NOT EXISTS support_widget_events (id INT AUTO_INCREMENT PRIMARY KEY, event_type VARCHAR(80) NOT NULL, name VARCHAR(190) NULL, email VARCHAR(190) NULL, phone VARCHAR(80) NULL, query_text TEXT NULL, response_text LONGTEXT NULL, source_url VARCHAR(255) NULL, ip_address VARCHAR(80) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY event_type(event_type), KEY email(email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("CREATE TABLE IF NOT EXISTS support_widget_settings (id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(160) UNIQUE NOT NULL, setting_value LONGTEXT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    foreach(['support_widget_enabled'=>'1','support_widget_live_chat_enabled'=>'1','support_widget_ai_enabled'=>'1','support_widget_search_enabled'=>'1','support_widget_whatsapp_enabled'=>'1','support_widget_phone_enabled'=>'1','support_widget_ticket_enabled'=>'1'] as $k=>$v){ try{ save_setting($k, admin_setting($k,$v)); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
    try{ $cols=array_column(db()->query('SHOW COLUMNS FROM customer_account_users')->fetchAll(PDO::FETCH_ASSOC),'Field'); if(!in_array('twofa_enabled',$cols,true)) db()->exec('ALTER TABLE customer_account_users ADD COLUMN twofa_enabled TINYINT(1) DEFAULT 0'); if(!in_array('last_ip',$cols,true)) db()->exec('ALTER TABLE customer_account_users ADD COLUMN last_ip VARCHAR(80) NULL'); if(!in_array('custom_permissions',$cols,true)) db()->exec('ALTER TABLE customer_account_users ADD COLUMN custom_permissions LONGTEXT NULL'); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ $cols=array_column(db()->query('SHOW COLUMNS FROM tld_pricing')->fetchAll(PDO::FETCH_ASSOC),'Field'); if(!in_array('cost_price',$cols,true)) db()->exec('ALTER TABLE tld_pricing ADD COLUMN cost_price DECIMAL(12,2) DEFAULT 0'); if(!in_array('margin_percent',$cols,true)) db()->exec('ALTER TABLE tld_pricing ADD COLUMN margin_percent DECIMAL(8,2) DEFAULT 0'); if(!in_array('restore_price',$cols,true)) db()->exec('ALTER TABLE tld_pricing ADD COLUMN restore_price DECIMAL(12,2) DEFAULT 0'); if(!in_array('backorder_price',$cols,true)) db()->exec('ALTER TABLE tld_pricing ADD COLUMN backorder_price DECIMAL(12,2) DEFAULT 0'); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("CREATE TABLE IF NOT EXISTS domain_backorders (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NULL, domain_name VARCHAR(190) NOT NULL, tld VARCHAR(80) NULL, request_type VARCHAR(40) DEFAULT 'notify', contact_name VARCHAR(190) NULL, email VARCHAR(190) NULL, phone VARCHAR(80) NULL, notify_email TINYINT(1) DEFAULT 1, notify_sms TINYINT(1) DEFAULT 0, notify_whatsapp TINYINT(1) DEFAULT 0, fee_amount DECIMAL(12,2) DEFAULT 0, currency VARCHAR(10) DEFAULT 'TRY', payment_status VARCHAR(40) DEFAULT 'pending', status VARCHAR(40) DEFAULT 'watching', last_checked_at DATETIME NULL, available_at DATETIME NULL, notes TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, KEY domain_name(domain_name), KEY customer_id(customer_id), KEY status(status), KEY request_type(request_type)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    foreach(['domain_backorder_notify_fee'=>'99.00','domain_backorder_preorder_fee'=>'249.00','domain_backorder_currency'=>'TRY','domain_backorder_notify_email'=>'1','domain_backorder_notify_sms'=>'1','domain_backorder_notify_whatsapp'=>'1'] as $k=>$v){ try{ save_setting($k, admin_setting($k,$v)); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
    try{ db()->exec("INSERT IGNORE INTO notification_templates(event_key,template_key,title,email_subject,email_body,sms_body,whatsapp_body,is_active) VALUES
        ('domain_backorder_created','domain_backorder_created','Domain backorder talebi','Backorder talebiniz alındı: {{domain}}','Merhaba {{customer_name}}, {{domain}} için {{request_type}} talebiniz alındı. Ücret: {{amount}} {{currency}}. Domain uygun olduğunda seçtiğiniz kanallardan bilgilendirileceksiniz.','{{domain}} için backorder talebiniz alındı. Uygun olunca bilgilendirileceksiniz.','{{domain}} için backorder talebiniz alındı. Uygun olunca bilgilendirileceksiniz.',1),
        ('domain_backorder_available','domain_backorder_available','Domain uygunluk bildirimi: {{domain}}','{{domain}} domaini kontrol sırasında uygun göründü','Merhaba {{customer_name}}, {{domain}} kontrol sırasında uygun göründü. Lütfen hemen panelden kontrol edin veya destek ekibiyle iletişime geçin.','{{domain}} uygun göründü. Hızlıca kontrol edin.','{{domain}} uygun göründü. Hızlıca kontrol edin.',1)
    "); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_v2410_customer_log($customerId,$userId,$action,$desc=''){
    try{ ao_v2410_ensure_schema(); db()->prepare('INSERT INTO customer_user_activity_logs(customer_id,account_user_id,action,description,ip_address) VALUES(?,?,?,?,?)')->execute([(int)$customerId,$userId?:null,$action,$desc,$_SERVER['REMOTE_ADDR']??'']); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_domain_backorder_fee_v2711($domain, $type='notify'){
    ao_v2410_ensure_schema();
    $type = $type === 'preorder' ? 'preorder' : 'notify';
    $tld = '';
    if (preg_match('/\\.([a-z0-9][a-z0-9\\.-]+)$/i', (string)$domain, $m)) $tld = strtolower($m[1]);
    try{
        if($tld !== ''){
            $q=db()->prepare('SELECT backorder_price,currency FROM tld_pricing WHERE REPLACE(LOWER(tld),".","")=? OR LOWER(tld)=? LIMIT 1');
            $q->execute([str_replace('.','',$tld), '.'.$tld]);
            if($row=$q->fetch(PDO::FETCH_ASSOC)){
                $price=(float)($row['backorder_price'] ?? 0);
                if($price>0) return ['amount'=>$price,'currency'=>strtoupper($row['currency'] ?: admin_setting('domain_backorder_currency','TRY')),'tld'=>$tld];
            }
        }
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $key = $type === 'preorder' ? 'domain_backorder_preorder_fee' : 'domain_backorder_notify_fee';
    return ['amount'=>(float)admin_setting($key, $type==='preorder'?'249.00':'99.00'), 'currency'=>strtoupper(admin_setting('domain_backorder_currency','TRY')), 'tld'=>$tld];
}
function ao_domain_backorder_notify_v2711(array $row, $event='domain_backorder_created'){
    $vars=[
        'domain'=>$row['domain_name'] ?? '',
        'request_type'=>($row['request_type'] ?? 'notify') === 'preorder' ? 'Ön Sipariş / Backorder' : 'Düşünce Bildir',
        'amount'=>number_format((float)($row['fee_amount'] ?? 0),2,',','.'),
        'currency'=>$row['currency'] ?? 'TRY',
        'customer_name'=>trim((string)($row['contact_name'] ?? '')),
        'customer_email'=>$row['email'] ?? '',
        'customer_phone'=>$row['phone'] ?? '',
    ];
    try{ ao_notify_event($event, (int)($row['customer_id'] ?? 0), $vars); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{
        $adminMail = admin_setting('company_email', admin_setting('contact_form_target_email',''));
        if($adminMail && function_exists('ao_send_email_notification')){
            ao_send_email_notification($adminMail, 'Yeni domain backorder: '.$vars['domain'], $vars['domain'].' için '.$vars['request_type'].' talebi oluşturuldu. Ücret: '.$vars['amount'].' '.$vars['currency']."\nMüşteri: ".$vars['customer_name']."\nE-posta: ".$vars['customer_email']."\nTelefon: ".$vars['customer_phone'], 'domain_backorder_admin');
        }
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_v2413_kb_search($query, $limit=5){
    ao_v2410_ensure_schema();
    $query=trim((string)$query);
    if($query==='') return [];
    try{
        $like='%'.$query.'%';
        $sql="SELECT title,slug,excerpt,content,category FROM knowledge_articles WHERE status='published' AND (title LIKE ? OR excerpt LIKE ? OR content LIKE ? OR category LIKE ?) ORDER BY CASE WHEN title LIKE ? THEN 0 ELSE 1 END, id DESC LIMIT ".max(1,min(10,(int)$limit));
        $q=db()->prepare($sql); $q->execute([$like,$like,$like,$like,$like]);
        $rows=$q->fetchAll() ?: [];
        foreach($rows as &$r){
            $plain=trim(strip_tags((string)($r['excerpt'] ?: $r['content'] ?? '')));
            $r['excerpt']=mb_substr($plain,0,220);
            $r['url']=url('bilgi-bankasi').'?q='.urlencode($query).'#'.rawurlencode($r['slug'] ?? '');
        }
        return $rows;
    }catch(Throwable $e){ return []; }
}
function ao_v2413_ai_answer($query){
    $query=trim((string)$query);
    $items=ao_v2413_kb_search($query,3);
    if($items){
        $top=$items[0];
        $answer="Bilgi Bankası'nda en yakın sonucu buldum: ".$top['title'].". ".($top['excerpt'] ?: 'Detayları makalede inceleyebilirsiniz.');
        return ['answer'=>$answer,'items'=>$items,'handoff'=>false];
    }
    return ['answer'=>'Bu konu için bilgi bankasında net bir cevap bulamadım. İstersen canlı temsilciye aktarabilir veya ticket oluşturabilirsin.','items'=>[],'handoff'=>true];
}
function ao_v2413_json($data){ header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }

function ao_domain_price_from_matrix($pricing, $keys) {
    foreach ((array)$keys as $key) {
        if (!isset($pricing[$key]) || !is_array($pricing[$key])) continue;
        if (isset($pricing[$key][1]) && is_numeric($pricing[$key][1])) return (float)$pricing[$key][1];
        foreach ($pricing[$key] as $period => $price) {
            if ((int)$period >= 1 && is_numeric($price)) return (float)$price;
        }
    }
    return 0.0;
}
function ao_domain_currency_from_tld_row($row) {
    $currencies = $row['currencies'] ?? [];
    if (is_array($currencies)) {
        foreach (['registration','register','renew','transfer','restore','backorder'] as $key) {
            $cur = strtoupper(trim((string)($currencies[$key] ?? '')));
            if ($cur !== '') return $cur;
        }
    }
    return 'USD';
}
function ao_domain_fetch_registrar_tld_prices($registrarSlug, $limit=1000) {
    $registrarSlug = trim((string)$registrarSlug) ?: 'domainnameapi';
    $bundle = ao_registrar_bundle($registrarSlug);
    if (!$bundle) throw new Exception('Registrar kaydı bulunamadı: '.$registrarSlug);
    if (!ao_is_domainnameapi_bundle($bundle)) {
        throw new Exception('Bu otomatik TLD fiyat listesi şu an DomainNameAPI için destekleniyor.');
    }
    $client = ao_dna_client($bundle);
    $response = $client->getTldList(max(20, min(2000, (int)$limit)));
    if (!ao_dna_ok($response, 'getTldList')) throw new Exception(ao_dna_error_text($response));
    $rows = $response['data'] ?? [];
    if (!is_array($rows) || !$rows) throw new Exception('Registrar TLD fiyat listesi boş döndü.');
    return $rows;
}
function ao_domain_import_registrar_prices($registrarSlug='domainnameapi', $margin=25.0) {
    ao_v2410_ensure_schema();
    ao_schema_ensure_v810();
    ao_schema_ensure_v188();
    $registrarSlug = trim((string)$registrarSlug) ?: 'domainnameapi';
    $margin = max(0, (float)$margin);
    $rows = ao_domain_fetch_registrar_tld_prices($registrarSlug, 1200);
    $imported = 0;
    $skipped = 0;
    $skippedTlds = [];
    foreach ($rows as $row) {
        $rawTld = trim((string)($row['tld'] ?? ''));
        if ($rawTld === '') { $skipped++; $skippedTlds[] = '(TLD adı boş)'; continue; }
        $tldDot = '.'.ltrim(strtolower($rawTld), '.');
        $tldPlain = ltrim($tldDot, '.');
        $pricing = $row['pricing'] ?? [];
        $registerCost = ao_domain_price_from_matrix($pricing, ['registration','register']);
        $renewCost = ao_domain_price_from_matrix($pricing, ['renew']);
        $transferCost = ao_domain_price_from_matrix($pricing, ['transfer']);
        $restoreCost = ao_domain_price_from_matrix($pricing, ['restore']);
        $backorderCost = ao_domain_price_from_matrix($pricing, ['backorder','back_order','preorder']);
        if ($registerCost <= 0 && $renewCost <= 0 && $transferCost <= 0) { $skipped++; $skippedTlds[] = $tldDot.' fiyat alanı boş'; continue; }
        if ($registerCost <= 0) $registerCost = $renewCost > 0 ? $renewCost : $transferCost;
        if ($renewCost <= 0) $renewCost = $registerCost;
        if ($transferCost <= 0) $transferCost = $registerCost;
        $currency = ao_domain_currency_from_tld_row($row);
        $rateToTry = $currency === 'TRY' ? 1.0 : (float)ao_currency_rate($currency, 'TRY');
        if ($rateToTry <= 0) throw new Exception($currency.'/TRY kuru alınamadı.');
        $raw = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        foreach (['register'=>$registerCost,'renew'=>$renewCost,'transfer'=>$transferCost,'restore'=>$restoreCost,'backorder'=>$backorderCost] as $action => $cost) {
            if ($cost <= 0) continue;
            db()->prepare('INSERT INTO registrar_price_cache(registrar_slug,tld,action,cost,currency,source,raw_response,last_checked_at) VALUES(?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE cost=VALUES(cost),currency=VALUES(currency),source=VALUES(source),raw_response=VALUES(raw_response),last_checked_at=NOW()')
                ->execute([$registrarSlug, $tldDot, $action, $cost, $currency, 'domainnameapi_tld_list', mb_substr((string)$raw, 0, 4000)]);
        }
        $registerTry = round($registerCost * $rateToTry * (1 + $margin / 100), 2);
        $renewTry = round($renewCost * $rateToTry * (1 + $margin / 100), 2);
        $transferTry = round($transferCost * $rateToTry * (1 + $margin / 100), 2);
        $restoreTry = $restoreCost > 0 ? round($restoreCost * $rateToTry * (1 + $margin / 100), 2) : 0;
        $backorderTry = $backorderCost > 0 ? round($backorderCost * $rateToTry * (1 + $margin / 100), 2) : 0;
        $costTry = round($registerCost * $rateToTry, 2);
        db()->prepare('INSERT INTO tld_pricing(tld,register_price,transfer_price,renew_price,currency,registrar_slug,is_active,cost_price,margin_percent,restore_price,backorder_price) VALUES(?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE register_price=VALUES(register_price),transfer_price=VALUES(transfer_price),renew_price=VALUES(renew_price),currency=VALUES(currency),registrar_slug=VALUES(registrar_slug),is_active=1,cost_price=VALUES(cost_price),margin_percent=VALUES(margin_percent),restore_price=VALUES(restore_price),backorder_price=VALUES(backorder_price)')
            ->execute([$tldDot, $registerTry, $transferTry, $renewTry, 'TRY', $registrarSlug, 1, $costTry, $margin, $restoreTry, $backorderTry]);
        if ($currency === 'USD') {
            $saleUsd = round($registerCost * (1 + $margin / 100), 4);
            $saleTry = round($saleUsd * (float)ao_currency_rate('USD', 'TRY'), 2);
            db()->prepare('INSERT INTO domain_price_cache(tld,registrar,cost_usd,commission_percent,sale_usd,sale_try) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE cost_usd=VALUES(cost_usd),commission_percent=VALUES(commission_percent),sale_usd=VALUES(sale_usd),sale_try=VALUES(sale_try)')
                ->execute([$tldPlain, $registrarSlug, $registerCost, $margin, $saleUsd, $saleTry]);
        } else {
            $saleUsd = $registerTry / max(0.01, (float)ao_currency_rate('USD', 'TRY'));
            db()->prepare('INSERT INTO domain_price_cache(tld,registrar,cost_usd,commission_percent,sale_usd,sale_try) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE cost_usd=VALUES(cost_usd),commission_percent=VALUES(commission_percent),sale_usd=VALUES(sale_usd),sale_try=VALUES(sale_try)')
                ->execute([$tldPlain, $registrarSlug, round($saleUsd / (1 + $margin / 100), 4), $margin, round($saleUsd, 4), $registerTry]);
        }
        $imported++;
    }
    $skipMessage = $skipped ? ($skipped.' TLD fiyat eksik olduğu için atlandı.'.($skippedTlds ? ' Atlananlar: '.implode(', ', array_slice($skippedTlds, 0, 40)) : '')) : 'DomainNameAPI TLD fiyat listesi çekildi ve marjlı satış fiyatına dönüştürüldü.';
    db()->prepare('INSERT INTO domain_price_import_logs(registrar_slug,source,imported_count,message) VALUES(?,?,?,?)')
        ->execute([$registrarSlug, 'domainnameapi_tld_list', $imported, $skipMessage]);
    return ['imported'=>$imported, 'skipped'=>$skipped, 'skipped_tlds'=>$skippedTlds];
}


if ($route==='admin/support/live-chat/poll') { require_admin(); ao_v2410_ensure_schema(); $activeId=(int)($_GET['chat']??0); $payload=['ok'=>true,'rows'=>[],'messages'=>[]]; try{ $payload['rows']=db()->query('SELECT id,visitor_name,subject,status,created_at FROM support_live_chats ORDER BY FIELD(status,"waiting","active","closed"), id DESC LIMIT 80')->fetchAll(PDO::FETCH_ASSOC) ?: []; if($activeId){ $q=db()->prepare('SELECT sender_type,sender_name,message,created_at FROM support_live_messages WHERE chat_id=? ORDER BY id ASC'); $q->execute([$activeId]); $payload['messages']=$q->fetchAll(PDO::FETCH_ASSOC) ?: []; } }catch(Throwable $e){ $payload=['ok'=>false,'error'=>$e->getMessage()]; } ao_v2413_json($payload); }

if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/support/live-chat/reply') { require_admin(); verify_csrf(); ao_v2410_ensure_schema(); $id=(int)($_POST['chat_id']??0); $msg=trim($_POST['message']??''); try{ if($id<=0||$msg==='') throw new Exception('Sohbet ve mesaj zorunlu.'); $admin=current_admin(); db()->prepare('INSERT INTO support_live_messages(chat_id,sender_type,sender_id,sender_name,message,is_read) VALUES(?,?,?,?,?,0)')->execute([$id,'admin',$admin['id']??null,$admin['name']??'Admin',$msg]); db()->prepare("UPDATE support_live_chats SET status='active', assigned_admin_id=? WHERE id=?")->execute([$admin['id']??null,$id]); flash('success','Mesaj gönderildi.'); }catch(Throwable $e){ flash('error','Mesaj gönderilemedi: '.$e->getMessage()); } redirect_to('admin/support/live-chat?chat='.$id); }
if ($route==='admin/support/live-chat/close') { require_admin(); verify_csrf(); ao_v2410_ensure_schema(); $id=(int)($_GET['id']??0); try{ db()->prepare("UPDATE support_live_chats SET status='closed', closed_at=NOW() WHERE id=?")->execute([$id]); flash('success','Sohbet kapatıldı.'); }catch(Throwable $e){ flash('error','Sohbet kapatılamadı.'); } redirect_to('admin/support/live-chat'); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='support/live-chat/start') { ao_v2410_ensure_schema(); verify_csrf(); $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $phone=trim($_POST['phone']??''); $msg=trim($_POST['message']??''); try{ if(!$name||!$email||!$msg) throw new Exception('Ad, e-posta ve mesaj zorunlu.'); db()->prepare('INSERT INTO support_live_chats(visitor_name,visitor_email,department,subject,status,source_url) VALUES(?,?,?,?,?,?)')->execute([$name,$email,$_POST['department']??'Teknik Destek',$_POST['subject']??'Canlı Sohbet','waiting',$_SERVER['HTTP_REFERER']??'']); $cid=(int)db()->lastInsertId(); db()->prepare('INSERT INTO support_live_messages(chat_id,sender_type,sender_name,message) VALUES(?,?,?,?)')->execute([$cid,'visitor',$name,($phone?('Telefon: '.$phone."\n"):'').$msg]); db()->prepare('INSERT INTO support_widget_events(event_type,name,email,phone,query_text,source_url,ip_address) VALUES(?,?,?,?,?,?,?)')->execute(['live_chat',$name,$email,$phone,$msg,$_SERVER['HTTP_REFERER']??'',$_SERVER['REMOTE_ADDR']??'']); flash('success','Canlı sohbet talebiniz oluşturuldu. Destek ekibimiz panele düştü.'); }catch(Throwable $e){ flash('error','Sohbet başlatılamadı: '.$e->getMessage()); } redirect_to($_SERVER['HTTP_REFERER'] ?? ''); }
if ($route==='support/widget/search') { ao_v2410_ensure_schema(); $q=trim($_GET['q']??''); $items=ao_v2413_kb_search($q,6); try{ if($q!=='') db()->prepare('INSERT INTO support_widget_events(event_type,query_text,response_text,source_url,ip_address) VALUES(?,?,?,?,?)')->execute(['search',$q,json_encode($items,JSON_UNESCAPED_UNICODE),$_SERVER['HTTP_REFERER']??'',$_SERVER['REMOTE_ADDR']??'']); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } ao_v2413_json(['ok'=>true,'items'=>$items]); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='support/widget/ai') { ao_v2410_ensure_schema(); verify_csrf(); $q=trim($_POST['q']??''); if(function_exists('ao_ai_assistant_run')){ $assistant=ao_ai_assistant_run($q,'guest',false); $res=['answer'=>$assistant['message'] ?? 'Cevap bulunamadı.','handoff'=>empty($assistant['ok']),'actions'=>$assistant['actions'] ?? []]; } else { $res=ao_v2413_ai_answer($q); } try{ db()->prepare('INSERT INTO support_widget_events(event_type,query_text,response_text,source_url,ip_address) VALUES(?,?,?,?,?)')->execute(['ai',$q,$res['answer'],$_SERVER['HTTP_REFERER']??'',$_SERVER['REMOTE_ADDR']??'']); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } ao_v2413_json(['ok'=>true]+$res); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/support/widget-settings/save') {
    require_admin(); verify_csrf(); ao_v2410_ensure_schema();
    foreach(['support_widget_enabled','support_widget_live_chat_enabled','support_widget_ai_enabled','support_widget_search_enabled','support_widget_whatsapp_enabled','support_widget_phone_enabled','support_widget_ticket_enabled','support_hours_enabled'] as $k){
        save_setting($k, isset($_POST[$k])?'1':'0');
    }
    foreach(['support_whatsapp_number','support_call_number','support_hours_start','support_hours_end','support_widget_greeting','support_widget_position','support_widget_style','support_widget_edge_offset','support_widget_bottom_offset','support_widget_button_size','support_widget_icon_size','support_widget_search_icon','support_widget_search_label','support_widget_ai_icon','support_widget_ai_label','support_widget_live_icon','support_widget_live_label','support_widget_ticket_icon','support_widget_ticket_label','support_widget_center_icon','support_widget_center_label','support_widget_phone_icon','support_widget_whatsapp_icon'] as $k){
        if(isset($_POST[$k])) save_setting($k, trim((string)$_POST[$k]));
    }
    $menuItems = [];
    $rawItems = $_POST['support_widget_items'] ?? [];
    if (is_array($rawItems)) {
        foreach ($rawItems as $row) {
            if (!is_array($row)) continue;
            $label = trim((string)($row['label'] ?? ''));
            $icon = trim((string)($row['icon'] ?? ''));
            $urlValue = trim((string)($row['url'] ?? ''));
            if ($label === '' && $icon === '' && $urlValue === '') continue;
            $type = trim((string)($row['type'] ?? 'url'));
            if (!in_array($type, ['modal_search','modal_ai','modal_live','modal_center','url','phone','whatsapp','top'], true)) $type = 'url';
            $menuItems[] = ['enabled'=>isset($row['enabled'])?'1':'0','type'=>$type,'label'=>$label,'icon'=>$icon,'url'=>$urlValue,'color'=>trim((string)($row['color'] ?? ''))];
        }
    }
    $legacyMap = [
        'modal_search' => ['support_widget_search_icon','support_widget_search_label'],
        'modal_ai' => ['support_widget_ai_icon','support_widget_ai_label'],
        'modal_live' => ['support_widget_live_icon','support_widget_live_label'],
        'url' => ['support_widget_ticket_icon','support_widget_ticket_label'],
        'modal_center' => ['support_widget_center_icon','support_widget_center_label'],
        'phone' => ['support_widget_phone_icon', null],
        'whatsapp' => ['support_widget_whatsapp_icon', null],
    ];
    foreach ($menuItems as $item) {
        $type = (string)($item['type'] ?? '');
        if (!isset($legacyMap[$type])) continue;
        [$iconKey, $labelKey] = $legacyMap[$type];
        if ($iconKey && trim((string)($item['icon'] ?? '')) !== '') save_setting($iconKey, trim((string)$item['icon']));
        if ($labelKey && trim((string)($item['label'] ?? '')) !== '') save_setting($labelKey, trim((string)$item['label']));
    }
    save_setting('support_widget_items_json', json_encode($menuItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    flash('success','Sağ butonlar ve destek widget ayarları kaydedildi.');
    redirect_to('admin/support/widget-settings');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='domain/backorder-create') {
    verify_csrf(); ao_v2410_ensure_schema();
    $domain=ahost_domain_clean((string)($_POST['domain_name'] ?? $_POST['domain'] ?? ''));
    $type=($_POST['request_type'] ?? '')==='preorder' ? 'preorder' : 'notify';
    $customer=function_exists('current_customer') ? current_customer() : null;
    $customerId=(int)($customer['id'] ?? 0);
    $name=trim((string)($_POST['contact_name'] ?? trim(($customer['first_name']??'').' '.($customer['last_name']??''))));
    $email=trim((string)($_POST['email'] ?? ($customer['email'] ?? '')));
    $phone=trim((string)($_POST['phone'] ?? ($customer['phone'] ?? '')));
    try{
        if(!ahost_domain_valid($domain)) throw new Exception('Geçerli bir domain girin.');
        if($name==='' || $email==='') throw new Exception('Ad soyad ve e-posta zorunlu.');
        $fee=ao_domain_backorder_fee_v2711($domain,$type);
        $status='watching';
        $paymentStatus=((float)$fee['amount']>0) ? 'pending' : 'free';
        $q=db()->prepare('INSERT INTO domain_backorders(customer_id,domain_name,tld,request_type,contact_name,email,phone,notify_email,notify_sms,notify_whatsapp,fee_amount,currency,payment_status,status,notes) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $q->execute([$customerId?:null,$domain,$fee['tld'],$type,$name,$email,$phone,isset($_POST['notify_email'])?1:0,isset($_POST['notify_sms'])?1:0,isset($_POST['notify_whatsapp'])?1:0,(float)$fee['amount'],$fee['currency'],$paymentStatus,$status,trim((string)($_POST['notes']??''))]);
        $id=(int)db()->lastInsertId();
        if((float)$fee['amount']>0){
            if(!isset($_SESSION['ao_cart']) || !is_array($_SESSION['ao_cart'])) $_SESSION['ao_cart']=[];
            $key='backorder:'.$id;
            $_SESSION['ao_cart'][$key]=[
                'slug'=>$key,
                'name'=>($type==='preorder'?'Domain Backorder: ':'Domain Düşünce Bildir: ').$domain,
                'group'=>'Domain Backorder',
                'price'=>(float)$fee['amount'],
                'currency'=>$fee['currency'],
                'cycle'=>'one-time',
                'qty'=>1,
                'domain_name'=>$domain,
                'addons'=>[],
                'meta'=>['type'=>'domain_backorder','backorder_id'=>$id,'request_type'=>$type]
            ];
        }
        $row=['id'=>$id,'customer_id'=>$customerId,'domain_name'=>$domain,'request_type'=>$type,'contact_name'=>$name,'email'=>$email,'phone'=>$phone,'fee_amount'=>$fee['amount'],'currency'=>$fee['currency']];
        ao_domain_backorder_notify_v2711($row,'domain_backorder_created');
        flash('success','Backorder talebiniz alındı'.((float)$fee['amount']>0?' ve ödeme için sepete eklendi.':'.').' Domain uygun olduğunda seçtiğiniz kanallardan bilgilendirileceksiniz.');
    }catch(Throwable $e){ flash('error','Backorder talebi oluşturulamadı: '.$e->getMessage()); }
    redirect_to('domain?domain='.rawurlencode($domain).'#backorder');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/domain-center/backorder-settings-save') {
    require_admin(); verify_csrf(); ao_v2410_ensure_schema();
    foreach(['domain_backorder_notify_fee','domain_backorder_preorder_fee','domain_backorder_currency'] as $k){ if(isset($_POST[$k])) save_setting($k, trim((string)$_POST[$k])); }
    foreach(['domain_backorder_notify_email','domain_backorder_notify_sms','domain_backorder_notify_whatsapp'] as $k){ save_setting($k, isset($_POST[$k])?'1':'0'); }
    flash('success','Backorder ücret ve bildirim ayarları kaydedildi.');
    redirect_to('admin/domain-center/backorders');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/domain-center/backorder-status') {
    require_admin(); verify_csrf(); ao_v2410_ensure_schema();
    $id=(int)($_POST['id']??0); $status=preg_replace('/[^a-z_]/','',(string)($_POST['status']??'watching'));
    if(!in_array($status,['watching','notified','won','cancelled','closed'],true)) $status='watching';
    try{ db()->prepare('UPDATE domain_backorders SET status=?, updated_at=NOW() WHERE id=?')->execute([$status,$id]); flash('success','Backorder durumu güncellendi.'); }catch(Throwable $e){ flash('error','Durum güncellenemedi.'); }
    redirect_to('admin/domain-center/backorders');
}
if ($route==='admin/domain-center/backorders/check') {
    require_admin(); ao_v2410_ensure_schema();
    $checked=0; $available=0;
    try{
        $rows=db()->query("SELECT * FROM domain_backorders WHERE status='watching' ORDER BY id ASC LIMIT 80")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach($rows as $row){
            $checked++;
            $res=ao_domain_availability($row['domain_name']);
            db()->prepare('UPDATE domain_backorders SET last_checked_at=NOW() WHERE id=?')->execute([(int)$row['id']]);
            if(!empty($res['ok']) && !empty($res['available'])){
                $available++;
                db()->prepare("UPDATE domain_backorders SET status='notified', available_at=NOW(), updated_at=NOW() WHERE id=?")->execute([(int)$row['id']]);
                ao_domain_backorder_notify_v2711($row,'domain_backorder_available');
            }
        }
        flash('success', $checked.' backorder kontrol edildi. Uygun görünen: '.$available);
    }catch(Throwable $e){ flash('error','Backorder kontrolü tamamlanamadı: '.$e->getMessage()); }
    redirect_to('admin/domain-center/backorders');
}
if ($route==='client/account-users/resend') { require_customer(); verify_csrf(); ao_v2410_ensure_schema(); $c=current_customer(); $id=(int)($_GET['id']??0); try{ $token=bin2hex(random_bytes(16)); db()->prepare('UPDATE customer_account_users SET invite_token_hash=?, invited_at=NOW(), status=IF(status="disabled",status,"invited") WHERE id=? AND customer_id=?')->execute([hash('sha256',$token),$id,(int)$c['id']]); ao_v2410_customer_log((int)$c['id'],$id,'invite_resend','Davet bağlantısı yenilendi.'); flash('success','Davet bağlantısı yenilendi. Demo paketinde e-posta yerine log kaydı oluşturuldu.'); }catch(Throwable $e){ flash('error','Davet yenilenemedi.'); } redirect_to('client/account-users'); }

if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='client/account-users/permissions-save') { require_customer(); verify_csrf(); ao_v2410_ensure_schema(); $c=current_customer(); $id=(int)($_POST['user_id']??0); $perms=$_POST['permissions']??[]; if(!is_array($perms)) $perms=[]; $clean=array_values(array_unique(array_map('strval',$perms))); try{ db()->prepare('UPDATE customer_account_users SET custom_permissions=? WHERE id=? AND customer_id=?')->execute([json_encode($clean,JSON_UNESCAPED_UNICODE),$id,(int)$c['id']]); ao_v2410_customer_log((int)$c['id'],$id,'permissions_update','Alt kullanıcı yetkileri güncellendi.'); flash('success','Alt kullanıcı yetkileri güncellendi.'); }catch(Throwable $e){ flash('error','Yetkiler güncellenemedi.'); } redirect_to('client/account-users'); }

if ($route==='client/account-users/2fa-toggle') { require_customer(); verify_csrf(); ao_v2410_ensure_schema(); $c=current_customer(); $id=(int)($_GET['id']??0); try{ $q=db()->prepare('SELECT twofa_enabled FROM customer_account_users WHERE id=? AND customer_id=?'); $q->execute([$id,(int)$c['id']]); $cur=(int)$q->fetchColumn(); db()->prepare('UPDATE customer_account_users SET twofa_enabled=? WHERE id=? AND customer_id=?')->execute([$cur?0:1,$id,(int)$c['id']]); ao_v2410_customer_log((int)$c['id'],$id,'2fa_toggle',$cur?'2FA kapatıldı.':'2FA zorunlu yapıldı.'); flash('success','2FA durumu güncellendi.'); }catch(Throwable $e){ flash('error','2FA güncellenemedi.'); } redirect_to('client/account-users'); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/domain-center/pricing-import') {
    require_admin(); verify_csrf(); ao_v2410_ensure_schema();
    $registrar=trim($_POST['registrar_slug']??'domainnameapi');
    $margin=max(0,(float)($_POST['margin_percent']??25));
    try{
        $result = ao_domain_import_registrar_prices($registrar, $margin);
        $skipDetail = !empty($result['skipped_tlds']) ? ' Atlananlar: '.implode(', ', array_slice($result['skipped_tlds'], 0, 18)) : '';
        flash('success', (int)$result['imported'].' TLD fiyatı DomainNameAPI üzerinden çekildi ve %'.number_format($margin,2,',','.').' marjla güncellendi.'.(((int)$result['skipped']>0)?' '.(int)$result['skipped'].' kayıt fiyat eksik olduğu için atlandı.'.$skipDetail:''));
    }catch(Throwable $e){ flash('error','Fiyat aktarımı başarısız: '.$e->getMessage()); }
    redirect_to('admin/domain-center/pricing');
}

require_once __DIR__.'/catalog-content.php';


if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/security/settings-save') {
    require_admin(); verify_csrf(); ao_mfa_ensure_schema();
    $allowed=['admin_mfa_policy','customer_mfa_policy','mfa_default_method','mfa_otp_ttl_minutes','mfa_max_attempts','mfa_sms_sender','ip_whitelist','session_timeout_minutes'];
    foreach($allowed as $k){ if(isset($_POST[$k])) save_setting($k, trim((string)$_POST[$k])); }
    foreach(['mfa_mail_enabled','mfa_totp_enabled','mfa_sms_enabled','csrf_protection','rate_limit_login'] as $k){ save_setting($k, isset($_POST[$k])?'1':'0'); }
    flash('success','Güvenlik ve 2FA ayarları kaydedildi.');
    redirect_to('admin/security');
}
