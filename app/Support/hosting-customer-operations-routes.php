<?php
// v7.9.0 Hosting & Customer Operations
function ao_schema_ensure_v790() {
    static $done=false; if($done) return; $done=true;
    try { db()->exec('CREATE TABLE IF NOT EXISTS hosting_account_logs (id int(11) NOT NULL AUTO_INCREMENT, hosting_account_id int(11) DEFAULT NULL, service_id int(11) DEFAULT NULL, admin_id int(11) DEFAULT NULL, action varchar(120) NOT NULL, description text DEFAULT NULL, old_value text DEFAULT NULL, new_value text DEFAULT NULL, created_at timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY(id), KEY hosting_account_id(hosting_account_id), KEY service_id(service_id), KEY action(action)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec('CREATE TABLE IF NOT EXISTS order_status_logs (id int(11) NOT NULL AUTO_INCREMENT, order_id int(11) NOT NULL, admin_id int(11) DEFAULT NULL, old_status varchar(60) DEFAULT NULL, new_status varchar(60) DEFAULT NULL, action varchar(120) NOT NULL, note text DEFAULT NULL, created_at timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY(id), KEY order_id(order_id), KEY action(action)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec('CREATE TABLE IF NOT EXISTS customer_groups (id int(11) NOT NULL AUTO_INCREMENT, name varchar(160) NOT NULL, discount_percent decimal(6,2) DEFAULT 0, description text DEFAULT NULL, is_active tinyint(1) DEFAULT 1, created_at timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec('CREATE TABLE IF NOT EXISTS customer_group_members (id int(11) NOT NULL AUTO_INCREMENT, customer_id int(11) NOT NULL, group_id int(11) NOT NULL, created_at timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY(id), UNIQUE KEY customer_group_unique(customer_id, group_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { $cols=[]; foreach(db()->query('SHOW COLUMNS FROM hosting_accounts')->fetchAll() as $c) $cols[$c['Field']]=true; if(empty($cols['server_id'])) db()->exec('ALTER TABLE hosting_accounts ADD COLUMN server_id int(11) DEFAULT NULL AFTER service_id'); if(empty($cols['panel_type'])) db()->exec('ALTER TABLE hosting_accounts ADD COLUMN panel_type VARCHAR(80) NULL AFTER ns2'); if(empty($cols['suspended_at'])) db()->exec('ALTER TABLE hosting_accounts ADD COLUMN suspended_at datetime DEFAULT NULL AFTER created_at'); if(empty($cols['terminated_at'])) db()->exec('ALTER TABLE hosting_accounts ADD COLUMN terminated_at datetime DEFAULT NULL AFTER suspended_at'); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { $cols=[]; foreach(db()->query('SHOW COLUMNS FROM customers')->fetchAll() as $c) $cols[$c['Field']]=true; if(empty($cols['group_id'])) db()->exec('ALTER TABLE customers ADD COLUMN group_id int(11) DEFAULT NULL AFTER id'); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
ao_schema_ensure_v790();
function ao_hosting_log($hostingId,$serviceId,$action,$description='',$old='',$new='') {
    try { ao_schema_ensure_v790(); $admin=current_admin(); db()->prepare('INSERT INTO hosting_account_logs(hosting_account_id,service_id,admin_id,action,description,old_value,new_value) VALUES(?,?,?,?,?,?,?)')->execute([(int)$hostingId,(int)$serviceId,$admin['id']??null,$action,$description,$old,$new]); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_order_log_status($orderId,$old,$new,$action,$note='') {
    try { ao_schema_ensure_v790(); $admin=current_admin(); db()->prepare('INSERT INTO order_status_logs(order_id,admin_id,old_status,new_status,action,note) VALUES(?,?,?,?,?,?)')->execute([(int)$orderId,$admin['id']??null,$old,$new,$action,$note]); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_hosting_account_by_service($serviceId) {
    $q=db()->prepare('SELECT h.*, s.customer_id, s.status service_status, s.domain service_domain FROM hosting_accounts h LEFT JOIN services s ON s.id=h.service_id WHERE h.service_id=? LIMIT 1');
    $q->execute([(int)$serviceId]); return $q->fetch() ?: null;
}
function ao_random_hosting_password($len=14) {
    $chars='abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%'; $out=''; for($i=0;$i<$len;$i++) $out.=$chars[random_int(0, strlen($chars)-1)]; return $out;
}

function ao_hosting_safe_int($row, $keys, $default=0) {
    foreach((array)$keys as $k){ if(isset($row[$k]) && $row[$k] !== '' && $row[$k] !== null) return (int)$row[$k]; }
    return (int)$default;
}
function ao_hosting_metric_rows($h) {
    // Demo veriler kaldırıldı - gerçek hosting hesabı integre edilinceye kadar boş döndür
    if(empty($h) || !isset($h['id'])) return [];
    
    $diskLimit=ao_hosting_safe_int($h,['disk_mb','disk_limit'],10240); $diskUsed=ao_hosting_safe_int($h,['disk_used_mb','disk_used'],0);
    $trafficLimit=ao_hosting_safe_int($h,['bandwidth_mb','bandwidth_limit'],102400); $trafficUsed=ao_hosting_safe_int($h,['bandwidth_used_mb','bandwidth_used'],0);
    $rows=[
      ['key'=>'disk','label'=>'Disk','icon'=>'💾','used'=>$diskUsed,'limit'=>$diskLimit,'unit'=>'MB'],
      ['key'=>'traffic','label'=>'Trafik','icon'=>'📈','used'=>$trafficUsed,'limit'=>$trafficLimit,'unit'=>'MB'],
      ['key'=>'mail','label'=>'E-posta','icon'=>'✉️','used'=>ao_hosting_safe_int($h,['mail_used'],0),'limit'=>ao_hosting_safe_int($h,['mail_limit'],0),'unit'=>'hesap'],
      ['key'=>'mysql','label'=>'MySQL','icon'=>'🗄️','used'=>ao_hosting_safe_int($h,['mysql_used'],0),'limit'=>ao_hosting_safe_int($h,['mysql_limit'],0),'unit'=>'veritabanı'],
      ['key'=>'cpu','label'=>'CPU','icon'=>'⚙️','used'=>ao_hosting_safe_int($h,['cpu_percent','cpu_used_percent'],0),'limit'=>100,'unit'=>'%'],
      ['key'=>'ram','label'=>'RAM','icon'=>'🧠','used'=>ao_hosting_safe_int($h,['ram_used_mb'],0),'limit'=>ao_hosting_safe_int($h,['ram_mb','ram_limit_mb'],0),'unit'=>'MB'],
      ['key'=>'inode','label'=>'Inode','icon'=>'🔢','used'=>ao_hosting_safe_int($h,['inode_used'],0),'limit'=>ao_hosting_safe_int($h,['inode_limit'],0),'unit'=>'inode'],
      ['key'=>'ftp','label'=>'FTP','icon'=>'📁','used'=>ao_hosting_safe_int($h,['ftp_used'],0),'limit'=>ao_hosting_safe_int($h,['ftp_limit'],0),'unit'=>'hesap'],
      ['key'=>'cron','label'=>'Cron','icon'=>'⏱️','used'=>ao_hosting_safe_int($h,['cron_used'],0),'limit'=>ao_hosting_safe_int($h,['cron_limit'],0),'unit'=>'görev'],
      ['key'=>'addon','label'=>'Addon Domain','icon'=>'🌐','used'=>ao_hosting_safe_int($h,['addon_domain_used'],0),'limit'=>ao_hosting_safe_int($h,['addon_domain_limit'],0),'unit'=>'domain'],
      ['key'=>'subdomain','label'=>'Subdomain','icon'=>'🔗','used'=>ao_hosting_safe_int($h,['subdomain_used'],0),'limit'=>ao_hosting_safe_int($h,['subdomain_limit'],0),'unit'=>'subdomain'],
    ];
    foreach($rows as &$r){ $r['percent']=$r['limit']>0?min(100,round($r['used']*100/$r['limit'])):0; $r['left']=$r['limit']>0?max(0,$r['limit']-$r['used']):0; }
    return $rows;
}
if (!function_exists('ao_domain_display_name')) {
function ao_domain_display_name($d) {
    $name=trim((string)($d['domain_name'] ?? ($d['domain'] ?? ($d['full_domain'] ?? ($d['name'] ?? '')))));
    if($name===''){
        $sld=trim((string)($d['sld'] ?? '')); $tld=trim((string)($d['tld'] ?? ''));
        if($sld!=='' && $tld!=='') $name=$sld.'.'.ltrim($tld,'.');
    }
    return $name;
}
}
if (!function_exists('ao_status_tr')) {
function ao_status_tr($status) {
    $s=strtolower((string)$status);
    return ['active'=>'Aktif','pending'=>'Beklemede','suspended'=>'Askıda','terminated'=>'Sonlandırıldı','cancelled'=>'İptal','expired'=>'Süresi Doldu','paid'=>'Ödendi','unpaid'=>'Ödenmedi'][$s] ?? ($status ?: '-');
}
}
function ao_hosting_panel_change_password($hostingAccount, $newPassword) {
    $server=null;
    try {
        if(!empty($hostingAccount['server_id'])){
            $q=db()->prepare('SELECT * FROM server_nodes WHERE id=? LIMIT 1'); $q->execute([(int)$hostingAccount['server_id']]); $server=$q->fetch() ?: null;
        }
        if(!$server && !empty($hostingAccount['server_name'])){
            $q=db()->prepare('SELECT * FROM server_nodes WHERE hostname=? OR name=? OR ip_address=? LIMIT 1');
            $q->execute([$hostingAccount['server_name'],$hostingAccount['server_name'],$hostingAccount['server_ip'] ?? '']); $server=$q->fetch() ?: null;
        }
    } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $username=trim((string)($hostingAccount['username'] ?? ($hostingAccount['whm_username'] ?? '')));
    if($username==='') return ['ok'=>false,'message'=>'Panel kullanıcı adı boş.'];
    if(!$server) return ['ok'=>false,'message'=>'Hosting hesabına bağlı sunucu bulunamadı.'];
    $panel=strtolower((string)($server['panel_type'] ?? 'whm'));
    $payload=['username'=>$username,'server'=>$server['hostname'] ?? ($server['name'] ?? ''),'password_changed'=>true,'test_mode'=>(int)($server['test_mode'] ?? 0)];
    if((int)($server['test_mode'] ?? 0)===1){
        try { db()->prepare('INSERT INTO hosting_operation_queue(service_id,server_id,operation,status,request_payload,response_payload,executed_at) VALUES(?,?,?,"done",?,?,NOW())')->execute([(int)($hostingAccount['service_id'] ?? 0),(int)($server['id'] ?? 0),'change-password',json_encode($payload, JSON_UNESCAPED_UNICODE),'Test modu: WHM API çağrısı yapılmadı.']); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        return ['ok'=>true,'message'=>'Sunucu test modunda; şifre değişimi simüle edildi. Canlı değişim için sunucuda test modunu kapatın.'];
    }
    if(in_array($panel, ['whm','cpanel'], true)){
        $host=trim((string)($server['hostname'] ?: ($server['ip_address'] ?? '')));
        $rootUser=trim((string)($server['username'] ?? ''));
        $token=trim((string)($server['api_token'] ?? ''));
        if($host==='' || $rootUser==='' || $token==='') return ['ok'=>false,'message'=>'WHM host, kullanıcı veya API token eksik.'];
        if(!preg_match('~^https?://~i',$host)) $host='https://'.$host;
        $url=rtrim($host,'/').':2087/json-api/passwd?'.http_build_query(['api.version'=>1,'user'=>$username,'password'=>$newPassword]);
        $res=ao_http_request('GET',$url,['Authorization: whm '.$rootUser.':'.$token],null,30);
        $ok=false; $message='WHM API HTTP '.$res['code'];
        $data=json_decode((string)$res['body'], true);
        if(is_array($data)){
            $meta=$data['metadata'] ?? [];
            $ok=((int)($meta['result'] ?? 0)===1);
            $message=$ok ? 'WHM şifre değişikliğini onayladı.' : (string)($meta['reason'] ?? 'WHM şifre değişimini reddetti.');
        }
        try { db()->prepare('INSERT INTO hosting_operation_queue(service_id,server_id,operation,status,request_payload,response_payload,executed_at) VALUES(?,?,?,?,?,?,NOW())')->execute([(int)($hostingAccount['service_id'] ?? 0),(int)($server['id'] ?? 0),'change-password',$ok?'done':'failed',json_encode($payload, JSON_UNESCAPED_UNICODE),(string)$res['body']]); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        return ['ok'=>$ok,'message'=>$message];
    }
    try {
        db()->prepare('INSERT INTO hosting_operation_queue(service_id,server_id,operation,status,request_payload,executed_at) VALUES(?,?,?,"done",?,NOW())')
            ->execute([(int)($hostingAccount['service_id'] ?? 0),(int)($hostingAccount['server_id'] ?? 0),'change-password',json_encode($payload, JSON_UNESCAPED_UNICODE)]);
    } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return ['ok'=>true,'message'=>'Panel adaptörü için operasyon kuyruğa alındı.'];
}

function ao_hosting_notify_credentials($hostingAccount, $newPassword, $event='hosting_password_changed') {
    try {
        $customerId=(int)($hostingAccount['customer_id'] ?? 0);
        if(!$customerId && !empty($hostingAccount['service_id'])){
            $q=db()->prepare('SELECT customer_id FROM services WHERE id=? LIMIT 1'); $q->execute([(int)$hostingAccount['service_id']]); $customerId=(int)($q->fetchColumn() ?: 0);
        }
        if(!$customerId) return ['email'=>['ok'=>false,'message'=>'Müşteri bulunamadı.'],'sms'=>['ok'=>false,'message'=>'Müşteri bulunamadı.']];
        $q=db()->prepare('SELECT * FROM customers WHERE id=? LIMIT 1'); $q->execute([$customerId]); $c=$q->fetch(); if(!$c) return [];
        $name=trim(($c['first_name']??'').' '.($c['last_name']??'')) ?: 'Değerli müşterimiz';
        $domain=trim((string)($hostingAccount['domain'] ?? ''));
        $username=trim((string)($hostingAccount['username'] ?? ($hostingAccount['whm_username'] ?? '')));
        $panelUrl=trim((string)($hostingAccount['cpanel_url'] ?? ($hostingAccount['directadmin_url'] ?? ($hostingAccount['webmail_url'] ?? ''))));
        $subject='Hosting panel bilgileriniz güncellendi';
        $body="Merhaba {$name},\n\n".($domain!=='' ? "{$domain} hizmetinizin " : 'Hosting hizmetinizin ')."panel şifresi güncellendi.\n\nPanel URL: ".($panelUrl ?: '-')."\nKullanıcı adı: ".($username ?: '-')."\nŞifre: {$newPassword}\nSunucu: ".(($hostingAccount['server_name'] ?? '') ?: '-')."\n\nGüvenliğiniz için bu bilgileri kimseyle paylaşmayın.";
        $sms=($domain!=='' ? $domain.' ' : '').'hosting panel bilgileriniz: Kullanıcı '.$username.' Şifre '.$newPassword.($panelUrl ? ' Panel '.$panelUrl : '');
        $out=[];
        if(!empty($c['email']) && function_exists('ao_send_email_notification')) $out['email']=ao_send_email_notification($c['email'],$subject,$body,$event);
        if(!empty($c['phone']) && function_exists('ao_send_sms')) $out['sms']=ao_send_sms($c['phone'],mb_substr($sms,0,459,'UTF-8'),$event);
        try { db()->prepare('INSERT INTO hosting_password_change_logs(service_id,hosting_account_id,actor_type,actor_id,sync_status,message) VALUES(?,?,"admin",?,"done",?)')->execute([(int)($hostingAccount['service_id']??0),(int)($hostingAccount['id']??0),(int)((current_admin()['id']??0)),'Şifre değiştirildi; mail/SMS bildirimi tetiklendi.']); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        return $out;
    } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); return []; }
}

function ao_server_panel_urls($server) {
    $host=ao_host_from_server_row($server); return ['cpanel'=>ao_panel_url_from_host($host,'cpanel'),'webmail'=>ao_panel_url_from_host($host,'webmail'),'whm'=>ao_panel_url_from_host($host,'whm'),'directadmin'=>ao_panel_url_from_host($host,'directadmin'),'plesk'=>ao_panel_url_from_host($host,'plesk'),'vps'=>ao_panel_url_from_host($host,'vps')];
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/hosting-server/account-action') {
    require_admin(); verify_csrf();
    $serviceId=(int)($_POST['service_id']??0); $action=trim($_POST['action']??'');
    try{
        if(!$serviceId || !$action) throw new Exception('Hizmet ve işlem zorunlu.');
        $h=ao_hosting_account_by_service($serviceId);
        if(!$h) throw new Exception('Hosting hesabı bulunamadı.');
        $oldStatus=$h['service_status']??'';
        if($action==='suspend'){
            db()->prepare('UPDATE services SET status="suspended" WHERE id=?')->execute([$serviceId]);
            db()->prepare('UPDATE hosting_accounts SET suspended_at=NOW() WHERE service_id=?')->execute([$serviceId]);
            ao_hosting_log($h['id'],$serviceId,'suspend','Hizmet askıya alındı.',$oldStatus,'suspended'); flash('success','Hosting askıya alındı.');
        } elseif($action==='unsuspend'){
            db()->prepare('UPDATE services SET status="active" WHERE id=?')->execute([$serviceId]);
            db()->prepare('UPDATE hosting_accounts SET suspended_at=NULL WHERE service_id=?')->execute([$serviceId]);
            ao_hosting_log($h['id'],$serviceId,'unsuspend','Hizmet tekrar aktif edildi.',$oldStatus,'active'); flash('success','Hosting açıldı.');
        } elseif($action==='terminate'){
            db()->prepare('UPDATE services SET status="terminated" WHERE id=?')->execute([$serviceId]);
            db()->prepare('UPDATE hosting_accounts SET terminated_at=NOW() WHERE service_id=?')->execute([$serviceId]);
            ao_hosting_log($h['id'],$serviceId,'terminate','Hizmet sonlandırıldı.',$oldStatus,'terminated'); flash('success','Hosting sonlandırıldı.');
        } elseif($action==='change-password'){
            $pass=(string)($_POST['panel_password']??''); if($pass==='') $pass=ao_random_hosting_password();
            $sync=ao_hosting_panel_change_password($h,$pass);
            if(empty($sync['ok'])) throw new Exception($sync['message'] ?? 'Sunucu şifre değişikliğini kabul etmedi.');
            db()->prepare('UPDATE hosting_accounts SET panel_password=? WHERE service_id=?')->execute([$pass,$serviceId]);
            $h['panel_password']=$pass;
            ao_hosting_notify_credentials($h,$pass,'hosting_password_changed');
            ao_hosting_log($h['id'],$serviceId,'password.changed','Panel şifresi değiştirildi, sunucu senkronu ve müşteri bildirimi çalıştı.','***','***'); flash('success','Panel şifresi güncellendi; müşteriye e-posta/SMS bildirimi gönderildi veya loglandı.');
        } elseif($action==='change-package'){
            $pkg=trim($_POST['package_name']??''); if($pkg==='') throw new Exception('Paket adı boş.');
            db()->prepare('UPDATE hosting_accounts SET package_name=? WHERE service_id=?')->execute([$pkg,$serviceId]);
            ao_hosting_log($h['id'],$serviceId,'package.changed','Paket değiştirildi.',$h['package_name']??'',$pkg); flash('success','Hosting paketi güncellendi.');
        } elseif($action==='move-server'){
            $serverId=(int)($_POST['server_id']??0); $q=db()->prepare('SELECT * FROM server_nodes WHERE id=? LIMIT 1'); $q->execute([$serverId]); $srv=$q->fetch(); if(!$srv) throw new Exception('Sunucu bulunamadı.');
            $urls=ao_server_panel_urls($srv);
            db()->prepare('UPDATE hosting_accounts SET server_id=?, server_name=?, server_ip=?, cpanel_url=?, webmail_url=?, whm_url=?, directadmin_url=?, vps_panel_url=? WHERE service_id=?')->execute([$serverId,$srv['hostname']?:$srv['name'],$srv['ip_address'],$urls['cpanel'],$urls['webmail'],$urls['whm'],$urls['directadmin'],$urls['vps'],$serviceId]);
            ao_hosting_log($h['id'],$serviceId,'server.changed','Sunucu değiştirildi.',$h['server_name']??'',($srv['hostname']?:$srv['name'])); flash('success','Sunucu bilgisi değiştirildi.');
        } else throw new Exception('Bilinmeyen işlem.');
    }catch(Throwable $e){ flash('error','Hosting işlemi başarısız: '.$e->getMessage()); }
    $back=trim($_POST['back']??''); redirect_to($back ?: 'admin/hosting-server/accounts');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/hosting-server/bulk-password-change') {
    require_admin(); verify_csrf();
    $ids=array_map('intval', (array)($_POST['hosting_account_ids'] ?? []));
    $samePassword=trim((string)($_POST['same_password'] ?? ''));
    $done=0; $failed=0;
    try{
        if(!$ids) throw new Exception('En az bir hosting hesabı seçin.');
        foreach($ids as $hid){
            try{
                $q=db()->prepare('SELECT h.*, s.domain, s.customer_id, s.status service_status, c.email, c.phone FROM hosting_accounts h LEFT JOIN services s ON s.id=h.service_id LEFT JOIN customers c ON c.id=s.customer_id WHERE h.id=? LIMIT 1');
                $q->execute([$hid]); $h=$q->fetch(); if(!$h) { $failed++; continue; }
                $pass=$samePassword!=='' ? $samePassword : ao_random_hosting_password();
                $sync=ao_hosting_panel_change_password($h,$pass);
                if(empty($sync['ok'])) { $failed++; continue; }
                db()->prepare('UPDATE hosting_accounts SET panel_password=? WHERE id=?')->execute([$pass,$hid]);
                $h['panel_password']=$pass;
                ao_hosting_notify_credentials($h,$pass,'hosting_bulk_password_changed');
                ao_hosting_log((int)$h['id'],(int)$h['service_id'],'password.bulk_changed','Toplu panel şifre değişimi yapıldı.','***','***');
                $done++;
            }catch(Throwable $e){ $failed++; error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        }
        flash($done>0?'success':'error','Toplu şifre değişimi tamamlandı. Başarılı: '.$done.' · Hatalı: '.$failed);
    }catch(Throwable $e){ flash('error','Toplu şifre değişimi başarısız: '.$e->getMessage()); }
    redirect_to('admin/hosting-server/accounts');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/orders/status') {
    require_admin(); verify_csrf();
    $id=(int)($_POST['order_id']??0); $status=trim($_POST['status']??''); $note=trim($_POST['note']??'');
    try{
        $q=db()->prepare('SELECT * FROM orders WHERE id=? LIMIT 1'); $q->execute([$id]); $o=$q->fetch(); if(!$o) throw new Exception('Sipariş bulunamadı.');
        $old=$o['status'];
        if($status==='active'){ ao_create_invoice_for_order($id); ao_provision_order($id); $new='active'; try{ $cq=db()->prepare('SELECT * FROM customers WHERE id=? LIMIT 1'); $cq->execute([(int)($o['customer_id']??0)]); $cust=$cq->fetch(); ao_notify_event('order_activated',(int)($o['customer_id']??0),['order_number'=>$o['order_number']??('#'.$id),'customer_name'=>trim(($cust['first_name']??'').' '.($cust['last_name']??''))]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
        else { $new=$status ?: $old; db()->prepare('UPDATE orders SET status=?, provision_status=IF(?="cancelled","cancelled",provision_status) WHERE id=?')->execute([$new,$new,$id]); }
        ao_order_log_status($id,$old,$new,'order.status',$note); flash('success','Sipariş durumu güncellendi.');
    }catch(Throwable $e){ flash('error','Sipariş durumu güncellenemedi: '.$e->getMessage()); }
    redirect_to('admin/orders/view?id='.$id);
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/customers/group-save') {
    require_admin(); verify_csrf();
    try{ $name=trim($_POST['name']??''); if(!$name) throw new Exception('Grup adı zorunlu.'); $discount=(float)($_POST['discount_percent']??0); $desc=trim($_POST['description']??''); db()->prepare('INSERT INTO customer_groups(name,discount_percent,description,is_active) VALUES(?,?,?,1)')->execute([$name,$discount,$desc]); flash('success','Müşteri grubu oluşturuldu.'); }catch(Throwable $e){ flash('error','Grup kaydedilemedi: '.$e->getMessage()); }
    redirect_to('admin/customers/groups');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/customers/group-assign') {
    require_admin(); verify_csrf();
    try{ $customer=(int)($_POST['customer_id']??0); $group=(int)($_POST['group_id']??0); if(!$customer||!$group) throw new Exception('Müşteri ve grup zorunlu.'); db()->prepare('UPDATE customers SET group_id=? WHERE id=?')->execute([$group,$customer]); db()->prepare('INSERT IGNORE INTO customer_group_members(customer_id,group_id) VALUES(?,?)')->execute([$customer,$group]); ao_customer_log($customer,'customer.group.assigned','Müşteri gruba atandı: '.$group); flash('success','Müşteri gruba atandı.'); }catch(Throwable $e){ flash('error','Grup ataması yapılamadı: '.$e->getMessage()); }
    redirect_to('admin/customers/groups');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/customers/bulk-message') {
    require_admin(); verify_csrf();
    $channel=trim($_POST['channel']??'email'); $group=(int)($_POST['group_id']??0); $subject=trim($_POST['subject']??'Ahost One Bilgilendirme'); $msg=trim($_POST['message']??'');
    try{ if(!$msg) throw new Exception('Mesaj boş.'); $q=$group?db()->prepare('SELECT * FROM customers WHERE group_id=? AND status<>"deleted"'):db()->prepare('SELECT * FROM customers WHERE status<>"deleted"'); $q->execute($group?[$group]:[]); $customers=$q->fetchAll(); foreach($customers as $c){ ao_log_simple($channel,'bulk-message','queued',$subject.' -> '.$c['email'],json_encode(['customer_id'=>$c['id'],'message'=>$msg], JSON_UNESCAPED_UNICODE)); } flash('success',count($customers).' müşteri için '.$channel.' bildirimi kuyruğa alındı.'); }catch(Throwable $e){ flash('error','Toplu mesaj hazırlanamadı: '.$e->getMessage()); }
    redirect_to('admin/customers/groups');
}
