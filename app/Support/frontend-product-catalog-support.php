<?php
// v23.3.5 Frontend Product Catalog - dynamic product/group/detail pages
function ao_v2335_cycle_label($cycle){
    $labels=['one_time'=>'Tek Seferlik','onetime'=>'Tek Seferlik','monthly'=>'Aylık','quarterly'=>'3 Aylık','semiannually'=>'6 Aylık','annually'=>'Yıllık','biennially'=>'2 Yıllık','triennially'=>'3 Yıllık'];
    return $labels[$cycle] ?? ucwords(str_replace(['_','-'],' ',(string)$cycle));
}
function ao_v2336_products_columns(){
    static $cols=null; if($cols!==null) return $cols;
    try { $cols=array_column(db()->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_ASSOC),'Field'); }
    catch(Throwable $e){ $cols=[]; }
    return $cols;
}
function ao_v2335_product_public_where(){
    $cols=ao_v2336_products_columns();
    $where=[];
    if(in_array('is_active',$cols,true)) $where[]='p.is_active=1';
    // Kayıtsız ziyaretçiler için ürün vitrini login istemez; sadece adminin gizlediği ürünler listeden çıkarılır.
    // Eski kurulumlarda visibility kolonu yoksa SQL hatası vermeden tüm aktif ürünler gösterilir.
    if(in_array('visibility',$cols,true)) $where[]="(p.visibility IS NULL OR p.visibility IN ('visible','public','show',''))";
    return $where ? implode(' AND ',$where) : '1=1';
}
function ao_v2336_product_group_columns(){
    static $cols=null; if($cols!==null) return $cols;
    try { $cols=array_column(db()->query('SHOW COLUMNS FROM product_groups')->fetchAll(PDO::FETCH_ASSOC),'Field'); }
    catch(Throwable $e){ $cols=[]; }
    return $cols;
}
function ao_v2335_product_groups(){
    try {
        $gcols=ao_v2336_product_group_columns();
        $where=in_array('is_active',$gcols,true) ? 'WHERE g.is_active=1' : 'WHERE 1=1';
        $order=in_array('sort_order',$gcols,true) ? 'g.sort_order,g.name' : 'g.name';
        $rows = db()->query("SELECT g.*, COUNT(p.id) product_count FROM product_groups g LEFT JOIN products p ON p.group_id=g.id AND ".ao_v2335_product_public_where()." $where GROUP BY g.id ORDER BY $order")->fetchAll();
        return function_exists('ao_module_filter_product_groups') ? ao_module_filter_product_groups($rows) : $rows;
    } catch(Throwable $e){ return []; }
}
function ao_v2335_products($groupSlug=null){
    try {
        $sql="SELECT p.*, g.name group_name, g.slug group_slug, g.type group_type, (COALESCE(svc.service_count,0)+COALESCE(ord.order_count,0)) ao_sales_count FROM products p LEFT JOIN product_groups g ON g.id=p.group_id LEFT JOIN (SELECT product_id, COUNT(*) service_count FROM services GROUP BY product_id) svc ON svc.product_id=p.id LEFT JOIN (SELECT product_id, COUNT(*) order_count FROM order_items GROUP BY product_id) ord ON ord.product_id=p.id WHERE ".ao_v2335_product_public_where();
        $vals=[];
        if($groupSlug){ $sql.=" AND g.slug=?"; $vals[]=$groupSlug; }
        $sql.=" ORDER BY COALESCE(p.is_featured,0) DESC, COALESCE(p.is_popular,0) DESC, ao_sales_count DESC, COALESCE(p.sort_order,0), p.name";
        $q=db()->prepare($sql); $q->execute($vals); $rows=$q->fetchAll();
        return function_exists('ao_module_filter_products') ? ao_module_filter_products($rows) : $rows;
    } catch(Throwable $e){ return []; }
}
function ao_v2335_product_by_slug($slug){
    try {
        $q=db()->prepare("SELECT p.*, g.name group_name, g.slug group_slug, g.type group_type FROM products p LEFT JOIN product_groups g ON g.id=p.group_id WHERE p.slug=? AND ".ao_v2335_product_public_where()." LIMIT 1");
        $q->execute([$slug]);
        $row = $q->fetch() ?: null;
        if ($row && function_exists('ao_module_filter_products') && !ao_module_filter_products([$row])) return null;
        return $row;
    } catch(Throwable $e){ return null; }
}
function ao_v2335_group_by_slug($slug){
    try {
        $gcols=ao_v2336_product_group_columns(); $active=in_array('is_active',$gcols,true)?' AND is_active=1':''; $q=db()->prepare("SELECT * FROM product_groups WHERE slug=?$active LIMIT 1"); $q->execute([$slug]); $row=$q->fetch() ?: null;
        if ($row && function_exists('ao_module_filter_product_groups') && !ao_module_filter_product_groups([$row])) return null;
        return $row;
    } catch(Throwable $e){ return null; }
}
function ao_v2335_product_pricing($productId){
    try {
        $q=db()->prepare("SELECT * FROM product_pricing WHERE product_id=? AND (is_active=1 OR is_active IS NULL) ORDER BY FIELD(cycle,'monthly','annually','quarterly','semiannually','biennially','triennially','one_time','onetime'), cycle");
        $q->execute([(int)$productId]); return $q->fetchAll();
    } catch(Throwable $e){ return []; }
}
function ao_v2524_price_row_try($row){
    $usd=(float)($row['price_usd'] ?? 0);
    $try=(float)($row['price_try'] ?? 0);
    $price=(float)($row['price'] ?? 0);
    $cur=strtoupper((string)($row['currency'] ?? 'TRY'));
    if($try<=0 && $usd>0) $try=(float)ao_v23_price_try($usd,'USD');
    if($try<=0 && $price>0) $try=(float)ao_v23_price_try($price,$cur);
    return round(max(0,$try),2);
}
function ao_v2524_product_price_options($product){
    $rows=ao_v2335_product_pricing((int)($product['id']??0));
    $options=[];
    foreach($rows as $r){
        $amount=ao_v2524_price_row_try($r);
        if($amount<=0) continue;
        $cycle=(string)($r['cycle'] ?? 'monthly');
        $options[$cycle]=['cycle'=>$cycle,'amount'=>$amount,'currency'=>'TRY','row'=>$r];
    }
    if($options) return array_values($options);
    $amount=(float)($product['price'] ?? 0);
    $cur=strtoupper((string)($product['currency'] ?? 'TRY'));
    if($cur!=='TRY') $amount=(float)ao_v23_price_try($amount,$cur);
    $cycle=(string)($product['billing_cycle'] ?? 'monthly');
    return [['cycle'=>$cycle,'amount'=>round(max(0,$amount),2),'currency'=>'TRY','row'=>[]]];
}
function ao_v2524_selected_price_option($product,$requestedCycle=''){
    $options=ao_v2524_product_price_options($product);
    $requestedCycle=(string)$requestedCycle;
    foreach($options as $opt) if(($opt['cycle'] ?? '')===$requestedCycle) return $opt;
    return $options[0] ?? ['cycle'=>'monthly','amount'=>0,'currency'=>'TRY','row'=>[]];
}
function ao_v2335_primary_price($product){
    $pricing=ao_v2335_product_pricing((int)($product['id']??0));
    foreach($pricing as $r){
        $usd=(float)($r['price_usd'] ?? 0); $try=(float)($r['price_try'] ?? 0); $price=(float)($r['price'] ?? 0); $cur=strtoupper((string)($r['currency'] ?? 'TRY'));
        if($try<=0 && $usd>0) $try=(float)ao_v23_price_try($usd,'USD');
        if($try<=0 && $price>0) $try=(float)ao_v23_price_try($price,$cur);
        if($try>0) return ['amount'=>$try,'currency'=>'TRY','cycle'=>$r['cycle'] ?? 'monthly','usd'=>$usd];
    }
    $amount=(float)($product['price'] ?? 0); $cur=strtoupper((string)($product['currency'] ?? 'TRY'));
    if($cur!=='TRY') $amount=(float)ao_v23_price_try($amount,$cur);
    return ['amount'=>$amount,'currency'=>'TRY','cycle'=>$product['billing_cycle'] ?? 'monthly','usd'=>0];
}
function ao_v2510_product_config_options($productId){
    $productId=(int)$productId;
    if($productId<=0) return [];
    try{
        ao_v2332_ensure_schema();
        $q=db()->prepare('SELECT * FROM configurable_options WHERE product_id=? AND (is_active=1 OR is_active IS NULL) ORDER BY sort_order,id');
        $q->execute([$productId]);
        $rows=$q->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach($rows as &$row){
            $vq=db()->prepare('SELECT * FROM configurable_option_values WHERE option_id=? AND (is_active=1 OR is_active IS NULL) ORDER BY sort_order,id');
            $vq->execute([(int)$row['id']]);
            $row['values']=$vq->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        return $rows;
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); return []; }
}
function ao_v249_product_custom_fields($productId,$groupId=0){
    $productId=(int)$productId;
    $groupId=(int)$groupId;
    if($productId<=0) return [];
    try{
        if(function_exists('ao_v249_ensure_product_custom_fields_schema')) ao_v249_ensure_product_custom_fields_schema();
        elseif(function_exists('ao_v238_ensure_schema')) ao_v238_ensure_schema();
        $q=db()->prepare('SELECT * FROM product_custom_fields WHERE is_active=1 AND (product_id=? OR (product_id=0 AND group_id=?)) ORDER BY product_id DESC, sort_order, id');
        $q->execute([$productId,$groupId]);
        return $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); return []; }
}
function ao_v2510_selected_config_summary($productId,$posted){
    $options=ao_v2510_product_config_options($productId);
    $selected=[]; $extra=0.0;
    foreach($options as $opt){
        $oid=(int)($opt['id'] ?? 0);
        $raw=$posted[$oid] ?? null;
        if($raw===null || $raw==='') continue;
        $values=$opt['values'] ?? [];
        $chosen=[];
        if(($opt['option_type'] ?? '') === 'quantity'){
            $qty=max(0,(int)$raw);
            if($qty>0){
                $unit=(float)($values[0]['price_monthly'] ?? 0);
                $extra += $qty * $unit;
                $chosen[]=['label'=>$qty.' adet','price'=>$qty*$unit];
            }
        } else {
            $ids=is_array($raw) ? array_map('intval',$raw) : [(int)$raw];
            foreach($values as $v){
                if(in_array((int)$v['id'],$ids,true)){
                    $price=(float)($v['price_monthly'] ?? 0);
                    $extra += $price;
                    $chosen[]=['label'=>(string)$v['label'],'price'=>$price];
                }
            }
        }
        if($chosen){
            $selected[]=['name'=>(string)$opt['name'],'type'=>(string)$opt['option_type'],'items'=>$chosen];
        }
    }
    return ['items'=>$selected,'extra'=>round($extra,2)];
}
function ao_v2510_seed_default_config_options(){
    static $done=false; if($done) return; $done=true;
    try{
        ao_v2332_ensure_schema();
        $sets=[
            'hosting'=>[
                ['Disk Alanı','dropdown',[['10 GB SSD',0],['25 GB SSD',60],['50 GB SSD',110],['100 GB SSD',190]]],
                ['Trafik','dropdown',[['100 GB',0],['250 GB',75],['Limitsiz',160]]],
                ['E-posta Hesabı','dropdown',[['10 adet',0],['25 adet',45],['Limitsiz',120]]],
                ['Günlük Yedekleme','radio',[['Yok',0],['Aktif',80]]],
            ],
            'web'=>[
                ['Sayfa Sayısı','dropdown',[['5 sayfa',0],['10 sayfa',2500],['20 sayfa',5200]]],
                ['Tasarım Seviyesi','radio',[['Standart',0],['Premium UI',3500],['Özel Kurumsal Tasarım',7500]]],
                ['Ek Dil','quantity',[['Dil başı',1200]]],
            ],
            'mobile'=>[
                ['Platform','radio',[['Android',0],['iOS',3500],['Android + iOS',6200]]],
                ['Ekran Sayısı','dropdown',[['5 ekran',0],['10 ekran',2800],['20 ekran',6400]]],
                ['Yayınlama Desteği','radio',[['Yok',0],['Google Play / App Store desteği',2200]]],
            ],
        ];
        $products=db()->query("SELECT id,name,type FROM products WHERE is_active=1 AND (type IN ('hosting','web','mobile','android') OR name LIKE '%Hosting%' OR name LIKE '%Web%' OR name LIKE '%Mobil%' OR name LIKE '%Mobile%')")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach($products as $p){
            $hay=mb_strtolower(($p['type'] ?? '').' '.($p['name'] ?? ''),'UTF-8');
            $key=str_contains($hay,'hosting') ? 'hosting' : ((str_contains($hay,'mobil') || str_contains($hay,'mobile') || str_contains($hay,'android')) ? 'mobile' : (str_contains($hay,'web') ? 'web' : ''));
            if($key==='' || empty($sets[$key])) continue;
            db()->prepare('UPDATE products SET is_custom_build_enabled=1 WHERE id=?')->execute([(int)$p['id']]);
            foreach($sets[$key] as $sort=>$def){
                [$name,$type,$values]=$def;
                $q=db()->prepare('SELECT id FROM configurable_options WHERE product_id=? AND name=? LIMIT 1');
                $q->execute([(int)$p['id'],$name]);
                $oid=(int)($q->fetchColumn() ?: 0);
                if(!$oid){
                    db()->prepare('INSERT INTO configurable_options(product_id,name,option_type,required,sort_order,is_active) VALUES(?,?,?,?,?,1)')->execute([(int)$p['id'],$name,$type,0,($sort+1)*10]);
                    $oid=(int)db()->lastInsertId();
                }
                $cq=db()->prepare('SELECT COUNT(*) FROM configurable_option_values WHERE option_id=?');
                $cq->execute([$oid]);
                if((int)$cq->fetchColumn() > 0) continue;
                foreach($values as $i=>$v){
                    db()->prepare('INSERT INTO configurable_option_values(option_id,label,price_monthly,sort_order,is_active) VALUES(?,?,?,?,1)')->execute([$oid,$v[0],(float)$v[1],$i+1]);
                }
            }
        }
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='support/chat-submit') { ao_v23_ensure_schema(); verify_csrf(); try{ $department=trim($_POST['department']??'Teknik Destek'); $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $phone=trim($_POST['phone']??''); $subject=trim($_POST['subject']??'Destek Talebi'); $message=trim($_POST['message']??''); if(!$name||!$email||!$message) throw new Exception('Ad soyad, e-posta ve mesaj zorunlu.'); db()->prepare('INSERT INTO support_chat_leads(department,name,email,phone,subject,message,page_url) VALUES(?,?,?,?,?,?,?)')->execute([$department,$name,$email,$phone,$subject,$message,$_SERVER['HTTP_REFERER']??'']); try{db()->prepare('INSERT INTO tickets(customer_id,subject,message,status,priority,department,created_at) VALUES(NULL,?,?,?,?,?,NOW())')->execute([$subject,$message,'open','medium',$department]);}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } flash('success','Destek ekibimize bilgi verildi, en kısa sürede sizinle iletişime geçilecektir.'); }catch(Throwable $e){ flash('error','Destek talebi oluşturulamadı: '.$e->getMessage()); } redirect_to($_SERVER['HTTP_REFERER'] ?? ''); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/knowledge-base/seed') { require_admin(); verify_csrf(); ao_v23_seed_knowledge(); flash('success','Bilgi Bankası taslak makaleleri oluşturuldu.'); redirect_to('admin/support/knowledgebase'); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/currency-center/save') { require_admin(); verify_csrf(); ao_v23_ensure_schema(); foreach(['USD','EUR','GBP'] as $c){$r=(float)($_POST['rate'][$c]??0);$m=(float)($_POST['margin'][$c]??0);$f=$r+($r*$m/100); try{db()->prepare('INSERT INTO currency_rates(currency_code,tcmb_rate,margin_percent,final_rate) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE tcmb_rate=VALUES(tcmb_rate),margin_percent=VALUES(margin_percent),final_rate=VALUES(final_rate)')->execute([$c,$r,$m,$f]);}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }} if(function_exists('ao_v237_refresh_try_prices')) ao_v237_refresh_try_prices(); flash('success','Kur ve marj ayarları kaydedildi. Ürünlerin TRY fiyatları güncellendi.'); redirect_to('admin/currency-center'); }

if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/translation-center/scan') {
    require_admin(); verify_csrf();
    $result = ao_translation_scan_missing();
    if ($result['languages'] === 0) flash('error','Önce Dil Ayarları\'ndan en az bir hedef dili aktif edin.');
    else flash('success', $result['found'].' eksik çeviri kaydı bulundu ve çeviri belleğine eklendi ('.$result['languages'].' aktif dil için tarandı).');
    redirect_to('admin/translation-center');
}
if ($route==='admin/translation-center/languages') {
    require_admin(); ao_v23_ensure_schema();
    view('translation-center/languages', ['pageTitle'=>'Dil Ayarları','languages'=>ao_translation_active_languages()]); exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/translation-center/languages-save') {
    require_admin(); verify_csrf(); ao_v23_ensure_schema();
    $selected = (array)($_POST['active'] ?? []);
    try{
        db()->exec("UPDATE translation_languages SET is_active=0");
        if ($selected) {
            $in = implode(',', array_fill(0, count($selected), '?'));
            db()->prepare("UPDATE translation_languages SET is_active=1 WHERE code IN ($in)")->execute(array_values($selected));
        }
        flash('success','Dil ayarları kaydedildi.');
    }catch(Throwable $e){ flash('error','Dil ayarları kaydedilemedi: '.$e->getMessage()); }
    redirect_to('admin/translation-center/languages');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/provider-center/save') { require_admin(); verify_csrf(); ao_v23_ensure_schema(); $slug=ao_v23_slug($_POST['provider_slug']??''); try{db()->prepare('INSERT INTO provider_accounts(provider_slug,provider_name,api_status,balance_amount,balance_currency,docs,is_active) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE provider_name=VALUES(provider_name),api_status=VALUES(api_status),balance_amount=VALUES(balance_amount),balance_currency=VALUES(balance_currency),docs=VALUES(docs),is_active=VALUES(is_active)')->execute([$slug,trim($_POST['provider_name']??$slug),$_POST['api_status']??'configured',(float)($_POST['balance_amount']??0),$_POST['balance_currency']??'TRY',$_POST['docs']??'',isset($_POST['is_active'])?1:0]); flash('success','Provider kaydedildi.');}catch(Throwable $e){flash('error','Provider kaydedilemedi: '.$e->getMessage());} redirect_to('admin/provider-center'); }


if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='client/account-users/save') {
    require_customer(); verify_csrf(); ao_v2332_ensure_schema(); $c=current_customer();
    $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $phone=trim($_POST['phone']??''); $role=trim($_POST['role_key']??'viewer');
    try{ if(!$name || !$email) throw new Exception('Ad soyad ve e-posta zorunlu.'); $perms=ao_v2332_customer_user_permissions($role); $token=bin2hex(random_bytes(16)); db()->prepare('INSERT INTO customer_account_users(customer_id,name,email,phone,role_key,permissions_json,status,invite_token_hash,invited_at) VALUES(?,?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),phone=VALUES(phone),role_key=VALUES(role_key),permissions_json=VALUES(permissions_json),status=VALUES(status),invite_token_hash=VALUES(invite_token_hash),invited_at=NOW()')->execute([(int)$c['id'],$name,$email,$phone,$role,json_encode($perms,JSON_UNESCAPED_UNICODE),'invited',hash('sha256',$token)]); db()->prepare('INSERT INTO customer_user_activity_logs(customer_id,action,description,ip_address) VALUES(?,?,?,?)')->execute([(int)$c['id'],'user_invited',$email.' davet edildi.',$_SERVER['REMOTE_ADDR']??'']); flash('success','Kullanıcı daveti oluşturuldu. Mail gönderim bağlantısı eklendiğinde davet otomatik iletilecek.'); }catch(Throwable $e){ flash('error','Kullanıcı eklenemedi: '.$e->getMessage()); }
    redirect_to('client/account-users');
}
if ($route==='client/account-users/toggle') { require_customer(); verify_csrf(); ao_v2332_ensure_schema(); $c=current_customer(); $id=(int)($_GET['id']??0); try{ $q=db()->prepare('SELECT status FROM customer_account_users WHERE id=? AND customer_id=?'); $q->execute([$id,(int)$c['id']]); $cur=(string)$q->fetchColumn(); $new=$cur==='active'?'disabled':'active'; db()->prepare('UPDATE customer_account_users SET status=? WHERE id=? AND customer_id=?')->execute([$new,$id,(int)$c['id']]); flash('success','Kullanıcı durumu güncellendi.'); }catch(Throwable $e){ flash('error','Durum değiştirilemedi.'); } redirect_to('client/account-users'); }
if ($route==='client/account-users/delete') { require_customer(); verify_csrf(); ao_v2332_ensure_schema(); $c=current_customer(); $id=(int)($_GET['id']??0); try{ db()->prepare('DELETE FROM customer_account_users WHERE id=? AND customer_id=?')->execute([$id,(int)$c['id']]); flash('success','Kullanıcı silindi.'); }catch(Throwable $e){ flash('error','Kullanıcı silinemedi.'); } redirect_to('client/account-users'); }
