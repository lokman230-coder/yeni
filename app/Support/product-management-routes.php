<?php
// Product management helpers for pricing, content studio and product actions.
function ao_v2480_ensure_quick_price_schema(){ static $done=false; if($done) return; $done=true;
    try{
        if(function_exists('ao_v237_ensure_product_pricing_schema')) ao_v237_ensure_product_pricing_schema();
        db()->exec("CREATE TABLE IF NOT EXISTS product_price_update_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NULL,
            admin_id INT NULL,
            action VARCHAR(80) DEFAULT 'quick_update',
            cycle VARCHAR(40) DEFAULT 'monthly',
            old_snapshot LONGTEXT NULL,
            new_snapshot LONGTEXT NULL,
            note TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY product_id(product_id),
            KEY action(action),
            KEY cycle(cycle)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_v2480_price_row($productId,$cycle){
    try{ ao_v2480_ensure_quick_price_schema(); $q=db()->prepare('SELECT * FROM product_pricing WHERE product_id=? AND cycle=? LIMIT 1'); $q->execute([(int)$productId,(string)$cycle]); return $q->fetch(PDO::FETCH_ASSOC) ?: []; }catch(Throwable $e){ return []; }
}
function ao_v2480_log_price_change($productId,$action,$cycle,$old,$new,$note=''){
    try{ ao_v2480_ensure_quick_price_schema(); $admin=function_exists('current_admin')?current_admin():null; db()->prepare('INSERT INTO product_price_update_logs(product_id,admin_id,action,cycle,old_snapshot,new_snapshot,note) VALUES(?,?,?,?,?,?,?)')->execute([(int)$productId,$admin['id']??null,$action,$cycle,json_encode($old,JSON_UNESCAPED_UNICODE),json_encode($new,JSON_UNESCAPED_UNICODE),$note]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_v2480_upsert_price($productId,$cycle,$priceUsd,$priceTry,$setupUsd=0,$setupTry=0,$active=1,$action='quick_update',$note=''){
    ao_v2480_ensure_quick_price_schema();
    $productId=(int)$productId; $cycle=(string)($cycle ?: 'monthly');
    $rate=function_exists('ao_v237_currency_rate') ? (float)ao_v237_currency_rate('USD') : 47.25;
    $margin=function_exists('ao_v237_currency_margin') ? (float)ao_v237_currency_margin('USD') : 0.0;
    $priceUsd=function_exists('ao_v237_parse_money') ? ao_v237_parse_money($priceUsd) : round((float)str_replace(',','.',(string)$priceUsd),2);
    $priceTry=function_exists('ao_v237_parse_money') ? ao_v237_parse_money($priceTry) : round((float)str_replace(',','.',(string)$priceTry),2);
    $setupUsd=function_exists('ao_v237_parse_money') ? ao_v237_parse_money($setupUsd) : round((float)str_replace(',','.',(string)$setupUsd),2);
    $setupTry=function_exists('ao_v237_parse_money') ? ao_v237_parse_money($setupTry) : round((float)str_replace(',','.',(string)$setupTry),2);
    if($priceUsd>0 && $priceTry<=0 && function_exists('ao_v237_price_try_from_currency')) $priceTry=ao_v237_price_try_from_currency($priceUsd,'USD');
    elseif($priceUsd>0 && $priceTry<=0 && $rate>0) $priceTry=round($priceUsd*$rate,2);
    if($priceTry>0 && $priceUsd<=0 && function_exists('ao_v237_price_currency_from_try')) $priceUsd=ao_v237_price_currency_from_try($priceTry,'USD');
    elseif($priceTry>0 && $priceUsd<=0 && $rate>0) $priceUsd=round($priceTry/$rate,2);
    if($setupUsd>0 && $setupTry<=0 && function_exists('ao_v237_price_try_from_currency')) $setupTry=ao_v237_price_try_from_currency($setupUsd,'USD');
    elseif($setupUsd>0 && $setupTry<=0 && $rate>0) $setupTry=round($setupUsd*$rate,2);
    if($setupTry>0 && $setupUsd<=0 && function_exists('ao_v237_price_currency_from_try')) $setupUsd=ao_v237_price_currency_from_try($setupTry,'USD');
    elseif($setupTry>0 && $setupUsd<=0 && $rate>0) $setupUsd=round($setupTry/$rate,2);
    $old=ao_v2480_price_row($productId,$cycle);
    $base=($priceUsd>0 || $setupUsd>0) ? 'USD' : 'TRY';
    db()->prepare('INSERT INTO product_pricing(product_id,cycle,price,setup_fee,currency,price_usd,price_try,setup_fee_usd,setup_fee_try,base_currency,exchange_rate,margin_percent,auto_convert,is_active) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,1,?) ON DUPLICATE KEY UPDATE price=VALUES(price), setup_fee=VALUES(setup_fee), currency=VALUES(currency), price_usd=VALUES(price_usd), price_try=VALUES(price_try), setup_fee_usd=VALUES(setup_fee_usd), setup_fee_try=VALUES(setup_fee_try), base_currency=VALUES(base_currency), exchange_rate=VALUES(exchange_rate), margin_percent=VALUES(margin_percent), auto_convert=1, is_active=VALUES(is_active)')
        ->execute([$productId,$cycle,$priceTry,$setupTry,'TRY',$priceUsd,$priceTry,$setupUsd,$setupTry,$base,$rate,$margin,(int)$active]);
    $new=ao_v2480_price_row($productId,$cycle);
    ao_v2480_log_price_change($productId,$action,$cycle,$old,$new,$note);
    try{
        $display=function_exists('ao_v2331_product_display_price') ? ao_v2331_product_display_price($productId) : ['try'=>$priceTry];
        $cols=array_column(db()->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_ASSOC),'Field');
        $set=[]; $vals=[];
        if(in_array('price',$cols,true)){ $set[]='price=?'; $vals[]=(float)($display['try'] ?? $priceTry); }
        if(in_array('currency',$cols,true)){ $set[]='currency=?'; $vals[]='TRY'; }
        if(in_array('currency_code',$cols,true)){ $set[]='currency_code=?'; $vals[]='TRY'; }
        if($set){ $vals[]=$productId; db()->prepare('UPDATE products SET '.implode(',',$set).' WHERE id=?')->execute($vals); }
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return $new;
}
function ao_v2480_bulk_target_products(){
    $ids=array_map('intval', $_POST['product_ids'] ?? []); $ids=array_values(array_filter(array_unique($ids)));
    if($ids) return $ids;
    $group=(int)($_POST['bulk_group_id'] ?? 0);
    try{
        if($group>0){ $q=db()->prepare('SELECT id FROM products WHERE group_id=?'); $q->execute([$group]); return array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN) ?: []); }
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/product-center/quick-price-update') {
    require_admin(); verify_csrf();
    $id=(int)($_POST['product_id'] ?? 0); $cycle=trim($_POST['cycle'] ?? 'monthly');
    try{ if($id<=0) throw new Exception('Ürün seçilmedi.'); ao_v2480_upsert_price($id,$cycle,$_POST['price_usd']??0,$_POST['price_try']??0,$_POST['setup_usd']??0,$_POST['setup_try']??0,!empty($_POST['is_active'])?1:0,'quick_update','Ürün listesinden hızlı fiyat düzeltme.'); flash('success','Hızlı fiyat düzeltme kaydedildi. Site, sepet ve müşteri paneli güncel vitrin fiyatını okuyacak.'); }
    catch(Throwable $e){ flash('error','Hızlı fiyat düzeltme yapılamadı: '.$e->getMessage()); }
    redirect_to('admin/product-center/products');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/product-center/bulk-price-update') {
    require_admin(); verify_csrf();
    $ids=ao_v2480_bulk_target_products(); $cycle=trim($_POST['bulk_cycle'] ?? 'monthly'); $mode=trim($_POST['bulk_mode'] ?? 'percent_increase'); $value=function_exists('ao_v237_parse_money')?ao_v237_parse_money($_POST['bulk_value']??0):(float)($_POST['bulk_value']??0); $count=0;
    try{
        if(!$ids) throw new Exception('Güncellenecek ürün seçilmedi.');
        foreach($ids as $pid){
            $old=ao_v2480_price_row($pid,$cycle); $priceUsd=(float)($old['price_usd'] ?? 0); $priceTry=(float)($old['price_try'] ?? ($old['price'] ?? 0)); $setupUsd=(float)($old['setup_fee_usd'] ?? 0); $setupTry=(float)($old['setup_fee_try'] ?? ($old['setup_fee'] ?? 0));
            $active=isset($old['is_active']) ? (int)$old['is_active'] : 1;
            if($mode==='percent_increase'){ $priceUsd=round($priceUsd*(1+$value/100),2); $priceTry=round($priceTry*(1+$value/100),2); }
            elseif($mode==='percent_decrease'){ $priceUsd=round($priceUsd*(1-$value/100),2); $priceTry=round($priceTry*(1-$value/100),2); }
            elseif($mode==='add_try'){ $priceTry=max(0,round($priceTry+$value,2)); $priceUsd=0; }
            elseif($mode==='add_usd'){ $priceUsd=max(0,round($priceUsd+$value,2)); $priceTry=0; }
            elseif($mode==='fixed_try'){ $priceTry=max(0,round($value,2)); $priceUsd=0; }
            elseif($mode==='fixed_usd'){ $priceUsd=max(0,round($value,2)); $priceTry=0; }
            elseif($mode==='refresh_usd_rate'){ $priceTry=0; }
            ao_v2480_upsert_price($pid,$cycle,$priceUsd,$priceTry,$setupUsd,$setupTry,$active,'bulk_update','Ürünler listesinden toplu fiyat güncelleme: '.$mode.' / '.$value); $count++;
        }
        flash('success',$count.' ürün için toplu fiyat güncelleme tamamlandı.');
    }catch(Throwable $e){ flash('error','Toplu fiyat güncelleme yapılamadı: '.$e->getMessage()); }
    redirect_to('admin/product-center/products');
}

function ao_v2331_product_display_price($productId){
    // Liste ve site vitrinlerinde fiyatın 0 görünmesini engelleyen ortak fiyat seçici.
    // Öncelik: aktif periyotlar; Tek seferlik/Aylık > Aylık > 3/6 aylık > yıllık.
    ao_v237_ensure_product_pricing_schema();
    $productId=(int)$productId;
    $order=['one_time','monthly','quarterly','semiannually','annually','biennially','triennially'];
    try{
        $st=db()->prepare("SELECT * FROM product_pricing WHERE product_id=? ORDER BY is_active DESC, FIELD(cycle,'one_time','monthly','quarterly','semiannually','annually','biennially','triennially'), id ASC");
        $st->execute([$productId]);
        $rows=$st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach($rows as $r){
            $try=(float)($r['price_try'] ?? 0);
            $usd=(float)($r['price_usd'] ?? 0);
            $price=(float)($r['price'] ?? 0);
            $cur=strtoupper((string)($r['currency'] ?? 'TRY'));
            if($try<=0 && $usd>0){ $try=round($usd*ao_v237_currency_rate('USD'),2); }
            if($try<=0 && $price>0){ $try=function_exists('ao_v237_price_try_from_currency') ? ao_v237_price_try_from_currency($price,$cur) : ($cur==='USD' ? round($price*ao_v237_currency_rate('USD'),2) : $price); }
            if($usd<=0 && $try>0){ $rate=ao_v237_currency_rate('USD'); $usd=$rate>0?round($try/$rate,2):0; }
            if($try>0 || $usd>0){
                return ['try'=>$try,'usd'=>$usd,'cycle'=>(string)($r['cycle'] ?? 'monthly'),'active'=>(int)($r['is_active'] ?? 0)];
            }
        }
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{
        $q=db()->prepare('SELECT price,currency,currency_code FROM products WHERE id=? LIMIT 1'); $q->execute([$productId]); $p=$q->fetch(PDO::FETCH_ASSOC) ?: [];
        $price=(float)($p['price'] ?? 0); $cur=strtoupper((string)($p['currency_code'] ?? $p['currency'] ?? 'TRY'));
        $try=function_exists('ao_v237_price_try_from_currency') ? ao_v237_price_try_from_currency($price,$cur) : ($cur==='USD' ? round($price*ao_v237_currency_rate('USD'),2) : $price);
        return ['try'=>$try,'usd'=>($try>0?round($try/ao_v237_currency_rate('USD'),2):0),'cycle'=>'legacy','active'=>0];
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return ['try'=>0,'usd'=>0,'cycle'=>'none','active'=>0];
}




// v24.0.0 - Product Content Studio: WordPress kalitesinde güvenli HTML editörü desteği
function ao_v2400_sanitize_product_html($html){
    $html = (string)$html;
    if ($html === '') return '';
    // Riskli blokları tamamen kaldır.
    $html = preg_replace('#<(script|style|iframe|object|embed|form|input|textarea|select|button|meta|link|base)[^>]*>.*?</\\1>#is', '', $html);
    $html = preg_replace('#<(script|style|iframe|object|embed|form|input|textarea|select|button|meta|link|base)[^>]*/?>#is', '', $html);
    // Event attribute ve javascript/data payload temizliği.
    $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('/(href|src)\s*=\s*("|\')\s*(javascript:|data:text\/html|vbscript:)[^"\']*("|\')/i', '$1="#"', $html);
    $html = preg_replace('/style\s*=\s*("|\')[^"\']*(expression|javascript:|url\s*\()[^"\']*("|\')/i', '', $html);
    $allowed = '<h1><h2><h3><h4><h5><h6><p><br><hr><strong><b><em><i><u><s><mark><small><span><div><section><article><blockquote><pre><code><ul><ol><li><table><thead><tbody><tfoot><tr><th><td><a><img><figure><figcaption>';
    $html = strip_tags($html, $allowed);
    return trim($html);
}
function ao_v2400_plain_from_html($html, $limit=220){
    $text = trim(preg_replace('/\s+/', ' ', strip_tags((string)$html)));
    if (function_exists('mb_substr') && function_exists('mb_strlen')) return mb_strlen($text) > $limit ? mb_substr($text,0,$limit).'…' : $text;
    return strlen($text) > $limit ? substr($text,0,$limit).'…' : $text;
}

// v23.3.2 - Ürün sekmeleri, klonlama, revizyon ve müşteri hesap kullanıcıları
function ao_v2332_ensure_schema(){ static $done=false; if($done) return; $done=true;
    try{
        db()->exec("CREATE TABLE IF NOT EXISTS product_revision_logs (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, admin_id INT NULL, action VARCHAR(80) DEFAULT 'update', snapshot_json LONGTEXT NULL, note TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY product_id(product_id), KEY action(action)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        db()->exec("CREATE TABLE IF NOT EXISTS configurable_options (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, name VARCHAR(190) NOT NULL, option_type VARCHAR(40) DEFAULT 'dropdown', required TINYINT(1) DEFAULT 0, sort_order INT DEFAULT 0, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY product_id(product_id), KEY is_active(is_active)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        db()->exec("CREATE TABLE IF NOT EXISTS configurable_option_values (id INT AUTO_INCREMENT PRIMARY KEY, option_id INT NOT NULL, label VARCHAR(190) NOT NULL, price_monthly DECIMAL(14,2) DEFAULT 0, sort_order INT DEFAULT 0, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY option_id(option_id), KEY is_active(is_active)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        try{
            $coCols=array_column(db()->query('SHOW COLUMNS FROM configurable_options')->fetchAll(PDO::FETCH_ASSOC),'Field');
            if(!in_array('required',$coCols,true)) db()->exec('ALTER TABLE configurable_options ADD COLUMN required TINYINT(1) DEFAULT 0');
            if(!in_array('is_active',$coCols,true)) db()->exec('ALTER TABLE configurable_options ADD COLUMN is_active TINYINT(1) DEFAULT 1');
            if(!in_array('created_at',$coCols,true)) db()->exec('ALTER TABLE configurable_options ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        try{
            $covCols=array_column(db()->query('SHOW COLUMNS FROM configurable_option_values')->fetchAll(PDO::FETCH_ASSOC),'Field');
            if(!in_array('price_monthly',$covCols,true)) db()->exec('ALTER TABLE configurable_option_values ADD COLUMN price_monthly DECIMAL(14,2) DEFAULT 0');
            if(!in_array('sort_order',$covCols,true)) db()->exec('ALTER TABLE configurable_option_values ADD COLUMN sort_order INT DEFAULT 0');
            if(!in_array('is_active',$covCols,true)) db()->exec('ALTER TABLE configurable_option_values ADD COLUMN is_active TINYINT(1) DEFAULT 1');
            if(!in_array('created_at',$covCols,true)) db()->exec('ALTER TABLE configurable_option_values ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
            if(in_array('price_delta',$covCols,true)) db()->exec('UPDATE configurable_option_values SET price_monthly=COALESCE(NULLIF(price_monthly,0),price_delta,0)');
        }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        db()->exec("CREATE TABLE IF NOT EXISTS server_groups (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(190) NOT NULL, strategy VARCHAR(80) DEFAULT 'least_used', location VARCHAR(120) NULL, status VARCHAR(40) DEFAULT 'active', notes TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_server_group_name(name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        db()->exec("CREATE TABLE IF NOT EXISTS customer_account_users (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NOT NULL, name VARCHAR(190) NOT NULL, email VARCHAR(190) NOT NULL, phone VARCHAR(80) NULL, role_key VARCHAR(80) DEFAULT 'viewer', permissions_json LONGTEXT NULL, status VARCHAR(40) DEFAULT 'invited', invite_token_hash VARCHAR(190) NULL, invited_at DATETIME NULL, accepted_at DATETIME NULL, last_login_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_customer_user_email(customer_id,email), KEY customer_id(customer_id), KEY status(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        db()->exec("CREATE TABLE IF NOT EXISTS customer_user_activity_logs (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NOT NULL, account_user_id INT NULL, action VARCHAR(120) NOT NULL, description TEXT NULL, ip_address VARCHAR(80) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY customer_id(customer_id), KEY account_user_id(account_user_id), KEY action(action)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // v23.3.6: MySQL/MariaDB uyumlu kolon ekleme. Bazı sunucularda ALTER ... IF NOT EXISTS desteklenmediği için SHOW COLUMNS kullanılır.
        $aoCols=[]; try { $aoCols=array_column(db()->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_ASSOC),'Field'); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        $aoAdd=function($name,$definition) use (&$aoCols){ try{ if(!in_array($name,$aoCols,true)){ db()->exec('ALTER TABLE products ADD COLUMN '.$definition); $aoCols[]=$name; } }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } };
        $aoAdd('server_group_id','server_group_id INT NULL');
        $aoAdd('default_server_id','default_server_id INT NULL');
        $aoAdd('short_description','short_description TEXT NULL');
        $aoAdd('visibility',"visibility VARCHAR(40) DEFAULT 'visible'");
        $aoAdd('seo_title','seo_title VARCHAR(190) NULL');
        $aoAdd('meta_description','meta_description TEXT NULL');
        $aoAdd('sort_order','sort_order INT DEFAULT 0');
        $aoAdd('is_featured','is_featured TINYINT(1) DEFAULT 0');
        $aoAdd('is_popular','is_popular TINYINT(1) DEFAULT 0');
        $aoAdd('is_new','is_new TINYINT(1) DEFAULT 0');
        $aoAdd('card_image_url','card_image_url VARCHAR(255) NULL');
        $aoAdd('card_background','card_background VARCHAR(190) NULL');
        $aoAdd('card_text_color','card_text_color VARCHAR(40) NULL');
        $aoAdd('card_button_color','card_button_color VARCHAR(40) NULL');
        $aoAdd('card_button_text_color','card_button_text_color VARCHAR(40) NULL');
        $aoAdd('card_icon','card_icon VARCHAR(80) NULL');
        $aoAdd('hero_kicker','hero_kicker VARCHAR(190) NULL');
        $aoAdd('hero_title','hero_title VARCHAR(255) NULL');
        $aoAdd('hero_subtitle','hero_subtitle TEXT NULL');
        $aoAdd('hero_image_url','hero_image_url VARCHAR(255) NULL');
        $aoAdd('hero_background','hero_background VARCHAR(190) NULL');
        $aoAdd('hero_primary_label','hero_primary_label VARCHAR(120) NULL');
        $aoAdd('hero_primary_url','hero_primary_url VARCHAR(255) NULL');
        $aoAdd('hero_secondary_label','hero_secondary_label VARCHAR(120) NULL');
        $aoAdd('hero_secondary_url','hero_secondary_url VARCHAR(255) NULL');
        $aoAdd('hero_tertiary_label','hero_tertiary_label VARCHAR(120) NULL');
        $aoAdd('hero_tertiary_url','hero_tertiary_url VARCHAR(255) NULL');
        try{ $snCols=array_column(db()->query('SHOW COLUMNS FROM server_nodes')->fetchAll(PDO::FETCH_ASSOC),'Field'); if(!in_array('server_group_id',$snCols,true)) db()->exec('ALTER TABLE server_nodes ADD COLUMN server_group_id INT NULL'); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        try{ db()->exec("INSERT IGNORE INTO server_groups(name,strategy,location,status,notes) VALUES ('Türkiye Hosting','least_used','TR','active','Yeni hosting hesapları en az dolu Türkiye sunucusuna atanır.'),('Almanya Hosting','least_used','DE','active','Avrupa lokasyonlu hosting ve VPS ürünleri için sunucu grubu.'),('Manuel Teslimat','manual',NULL,'active','Otomasyon kullanılmayan ürünlerde manuel teslimat grubu.')"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_v2332_log_product_revision($productId,$action='update',$note=''){
    try{ ao_v2332_ensure_schema(); $q=db()->prepare('SELECT * FROM products WHERE id=? LIMIT 1'); $q->execute([(int)$productId]); $product=$q->fetch(PDO::FETCH_ASSOC) ?: []; $pq=db()->prepare('SELECT * FROM product_pricing WHERE product_id=?'); $pq->execute([(int)$productId]); $snapshot=['product'=>$product,'pricing'=>$pq->fetchAll(PDO::FETCH_ASSOC) ?: []]; $admin=current_admin(); db()->prepare('INSERT INTO product_revision_logs(product_id,admin_id,action,snapshot_json,note) VALUES(?,?,?,?,?)')->execute([(int)$productId,$admin['id']??null,$action,json_encode($snapshot,JSON_UNESCAPED_UNICODE),$note]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_v2332_customer_user_permissions($role){
    $all=['invoices.view','invoices.pay','tickets.open','tickets.view','hosting.manage','domains.manage','dns.manage','orders.create','profile.edit','users.manage'];
    $map=['owner'=>$all,'full'=>$all,'billing'=>['invoices.view','invoices.pay','tickets.open'],'technical'=>['tickets.open','tickets.view','hosting.manage','domains.manage','dns.manage'],'domain'=>['domains.manage','dns.manage','tickets.open'],'hosting'=>['hosting.manage','tickets.open'],'viewer'=>['invoices.view','tickets.view']];
    return $map[$role] ?? $map['viewer'];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/product-center/product-save') {
    require_admin(); verify_csrf();
    $id=(int)($_POST['id']??0); $group=(int)($_POST['group_id']??0); $name=trim($_POST['name']??''); $slug=trim($_POST['slug']??''); $type=trim($_POST['type']??'service'); $module=trim($_POST['module_name']??'manual'); $serverGroup=(int)($_POST['server_group_id']??0); $defaultServer=(int)($_POST['default_server_id']??0); $whm=trim($_POST['whm_package']??''); $shortDesc=trim($_POST['short_description']??''); $desc=ao_v2400_sanitize_product_html($_POST['description']??''); $custom=(int)($_POST['is_custom_build_enabled']??0); $visibility=trim($_POST['visibility']??'visible'); $seoTitle=trim($_POST['seo_title']??''); $metaDesc=trim($_POST['meta_description']??''); $sortOrder=(int)($_POST['sort_order']??0); $isFeatured=!empty($_POST['is_featured'])?1:0; $isPopular=!empty($_POST['is_popular'])?1:0; $isNew=!empty($_POST['is_new'])?1:0; $cardImage=trim($_POST['card_image_url']??''); $cardBg=trim($_POST['card_background']??''); $cardText=trim($_POST['card_text_color']??''); $cardBtn=trim($_POST['card_button_color']??''); $cardBtnText=trim($_POST['card_button_text_color']??''); $cardIcon=trim($_POST['card_icon']??''); $heroKicker=trim($_POST['hero_kicker']??''); $heroTitle=trim($_POST['hero_title']??''); $heroSubtitle=trim($_POST['hero_subtitle']??''); $heroImage=trim($_POST['hero_image_url']??''); $heroBg=trim($_POST['hero_background']??''); $heroPrimaryLabel=trim($_POST['hero_primary_label']??''); $heroPrimaryUrl=trim($_POST['hero_primary_url']??''); $heroSecondaryLabel=trim($_POST['hero_secondary_label']??''); $heroSecondaryUrl=trim($_POST['hero_secondary_url']??''); $heroTertiaryLabel=trim($_POST['hero_tertiary_label']??''); $heroTertiaryUrl=trim($_POST['hero_tertiary_url']??'');
    if($slug==='') $slug = preg_replace('/[^a-z0-9]+/','-', strtolower($name));
    $slug = trim(preg_replace('/[^a-z0-9\-]+/','-', strtolower($slug)),'-');
    try{ if(!$name||!$slug) throw new Exception('Ürün adı ve slug zorunlu.');
        ao_v237_ensure_product_pricing_schema(); ao_v2332_ensure_schema();
        if($id>0) {
            ao_v2332_log_product_revision($id,'before_update','Ürün düzenlemeden önce otomatik kayıt.');
            db()->prepare('UPDATE products SET group_id=?,name=?,slug=?,type=?,module_name=?,server_group_id=?,default_server_id=?,whm_package=?,short_description=?,description=?,is_custom_build_enabled=?,visibility=?,seo_title=?,meta_description=?,sort_order=?,is_featured=?,is_popular=?,is_new=?,card_image_url=?,card_background=?,card_text_color=?,card_button_color=?,card_button_text_color=?,card_icon=?,hero_kicker=?,hero_title=?,hero_subtitle=?,hero_image_url=?,hero_background=?,hero_primary_label=?,hero_primary_url=?,hero_secondary_label=?,hero_secondary_url=?,hero_tertiary_label=?,hero_tertiary_url=? WHERE id=?')->execute([$group,$name,$slug,$type,$module,$serverGroup,$defaultServer?:null,$whm,$shortDesc,$desc,$custom,$visibility,$seoTitle,$metaDesc,$sortOrder,$isFeatured,$isPopular,$isNew,$cardImage,$cardBg,$cardText,$cardBtn,$cardBtnText,$cardIcon,$heroKicker,$heroTitle,$heroSubtitle,$heroImage,$heroBg,$heroPrimaryLabel,$heroPrimaryUrl,$heroSecondaryLabel,$heroSecondaryUrl,$heroTertiaryLabel,$heroTertiaryUrl,$id]);
            $productId=$id;
        } else {
            db()->prepare('INSERT INTO products(group_id,name,slug,type,module_name,server_group_id,default_server_id,whm_package,short_description,description,is_custom_build_enabled,is_active,visibility,seo_title,meta_description,sort_order,is_featured,is_popular,is_new,card_image_url,card_background,card_text_color,card_button_color,card_button_text_color,card_icon,hero_kicker,hero_title,hero_subtitle,hero_image_url,hero_background,hero_primary_label,hero_primary_url,hero_secondary_label,hero_secondary_url,hero_tertiary_label,hero_tertiary_url) VALUES(?,?,?,?,?,?,?,?,?,?,?,1,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$group,$name,$slug,$type,$module,$serverGroup,$defaultServer?:null,$whm,$shortDesc,$desc,$custom,$visibility,$seoTitle,$metaDesc,$sortOrder,$isFeatured,$isPopular,$isNew,$cardImage,$cardBg,$cardText,$cardBtn,$cardBtnText,$cardIcon,$heroKicker,$heroTitle,$heroSubtitle,$heroImage,$heroBg,$heroPrimaryLabel,$heroPrimaryUrl,$heroSecondaryLabel,$heroSecondaryUrl,$heroTertiaryLabel,$heroTertiaryUrl]);
            $productId=(int)db()->lastInsertId();
        }
        if(!empty($productId)) {
            ao_v237_save_product_prices($productId);
            if(function_exists('ao_v249_save_product_checkout_addons')) ao_v249_save_product_checkout_addons($productId);
            if(function_exists('ao_v249_save_product_custom_fields')) ao_v249_save_product_custom_fields($productId,$group);
            ao_v2332_log_product_revision($productId,$id>0?'update':'create',$id>0?'Ürün güncellendi.':'Ürün oluşturuldu.');
        }
        flash('success','Ürün, fiyatlandırma, ek paketler ve özel alanlar kaydedildi.');
    }catch(Throwable $e){ flash('error','Ürün kaydedilemedi: '.$e->getMessage()); }
    $returnTab = trim((string)($_POST['return_tab'] ?? 'fiyat'));
    if(!preg_match('/^[a-z0-9_-]+$/', $returnTab)) $returnTab = 'fiyat';
    redirect_to('admin/product-center/products?edit='.(int)($productId ?? $id).'#tab-'.$returnTab);
}

if ($route === 'admin/product-center/product-clone') {
    require_admin(); verify_csrf(); ao_v2332_ensure_schema(); ao_v237_ensure_product_pricing_schema(); $id=(int)($_GET['id']??0);
    try{
        $q=db()->prepare('SELECT * FROM products WHERE id=? LIMIT 1'); $q->execute([$id]); $p=$q->fetch(PDO::FETCH_ASSOC); if(!$p) throw new Exception('Ürün bulunamadı.');
        $baseSlug=preg_replace('/-kopya-[0-9]+$/','',(string)$p['slug']); $newSlug=$baseSlug.'-kopya-'.date('His');
        $cols=array_column(db()->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_ASSOC),'Field');
        $copyCols=array_values(array_intersect($cols,['group_id','name','slug','type','module_name','server_group_id','default_server_id','whm_package','short_description','description','is_custom_build_enabled','is_active','visibility','seo_title','meta_description','sort_order','price','currency','currency_code','card_image_url','card_background','card_text_color','card_button_color','card_button_text_color','card_icon','hero_kicker','hero_title','hero_subtitle','hero_image_url','hero_background','hero_primary_label','hero_primary_url','hero_secondary_label','hero_secondary_url','hero_tertiary_label','hero_tertiary_url']));
        $data=[]; foreach($copyCols as $c){ $data[$c]=$p[$c]??null; }
        $data['name']=($p['name'] ?? 'Ürün').' Kopya'; $data['slug']=$newSlug; if(isset($data['is_active'])) $data['is_active']=0;
        $sql='INSERT INTO products('.implode(',',array_keys($data)).') VALUES('.implode(',',array_fill(0,count($data),'?')).')'; db()->prepare($sql)->execute(array_values($data)); $newId=(int)db()->lastInsertId();
        $ps=db()->prepare('SELECT * FROM product_pricing WHERE product_id=?'); $ps->execute([$id]); foreach($ps->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r){ unset($r['id']); $r['product_id']=$newId; $sql='INSERT INTO product_pricing('.implode(',',array_keys($r)).') VALUES('.implode(',',array_fill(0,count($r),'?')).')'; try{db()->prepare($sql)->execute(array_values($r));}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
        if(function_exists('ao_v249_ensure_product_checkout_addons_schema')) ao_v249_ensure_product_checkout_addons_schema();
        try{
            $as=db()->prepare('SELECT * FROM product_checkout_addons WHERE product_id=?');
            $as->execute([$id]);
            foreach($as->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r){
                unset($r['id']);
                $r['product_id']=$newId;
                $sql='INSERT INTO product_checkout_addons('.implode(',',array_keys($r)).') VALUES('.implode(',',array_fill(0,count($r),'?')).')';
                db()->prepare($sql)->execute(array_values($r));
            }
        }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        if(function_exists('ao_v249_ensure_product_custom_fields_schema')) ao_v249_ensure_product_custom_fields_schema();
        try{
            $fs=db()->prepare('SELECT * FROM product_custom_fields WHERE product_id=?');
            $fs->execute([$id]);
            foreach($fs->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r){
                unset($r['id']);
                $oldPrefix='p'.(int)$id.'-';
                $newPrefix='p'.(int)$newId.'-';
                if(str_starts_with((string)($r['field_key'] ?? ''), $oldPrefix)) $r['field_key']=$newPrefix.substr((string)$r['field_key'], strlen($oldPrefix));
                else $r['field_key']=$newPrefix.trim(preg_replace('~[^a-z0-9_-]+~','-',strtolower((string)($r['field_key'] ?? 'alan'))),'-');
                $r['product_id']=$newId;
                $sql='INSERT INTO product_custom_fields('.implode(',',array_keys($r)).') VALUES('.implode(',',array_fill(0,count($r),'?')).')';
                db()->prepare($sql)->execute(array_values($r));
            }
        }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        ao_v2332_log_product_revision($newId,'clone','Ürün #'.$id.' üzerinden klonlandı.'); flash('success','Ürün klonlandı. Yeni kopya pasif olarak oluşturuldu.'); redirect_to('admin/product-center/products?edit='.$newId);
    }catch(Throwable $e){ flash('error','Ürün klonlanamadı: '.$e->getMessage()); redirect_to('admin/product-center/products'); }
}
if ($route === 'admin/product-center/product-toggle') {
    require_admin(); verify_csrf(); $id=(int)($_GET['id']??0);
    try { $q=db()->prepare('SELECT is_active FROM products WHERE id=?'); $q->execute([$id]); $cur=(int)$q->fetchColumn(); db()->prepare('UPDATE products SET is_active=? WHERE id=?')->execute([$cur?0:1,$id]); flash('success',$cur?'Ürün pasife alındı.':'Ürün aktifleştirildi.'); } catch(Throwable $e){ flash('error','Ürün durumu değiştirilemedi.'); }
    redirect_to('admin/product-center/products');
}
if ($route === 'admin/product-center/product-delete') {
    require_admin(); verify_csrf(); $id=(int)($_GET['id']??0);
    try { db()->prepare('DELETE FROM products WHERE id=?')->execute([$id]); flash('success','Ürün kalıcı olarak silindi.'); } catch(Throwable $e){ flash('error','Ürün silinemedi: Bu ürüne bağlı sipariş/hizmet olabilir.'); }
    redirect_to('admin/product-center/products');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/product-center/config-option-save') {
    verify_csrf();
    $pid=(int)($_POST['product_id']??0); $name=trim($_POST['name']??''); $type=trim($_POST['option_type']??'dropdown'); $values=trim($_POST['values']??'');
    try{ if(!$pid||!$name) throw new Exception('Ürün ve seçenek adı zorunlu.'); db()->prepare('INSERT INTO configurable_options(product_id,name,option_type,required,sort_order) VALUES(?,?,?,?,0)')->execute([$pid,$name,$type,1]); $oid=(int)db()->lastInsertId();
        foreach(array_filter(array_map('trim', explode("\n", $values))) as $i=>$line){ [$label,$price]=array_pad(array_map('trim', explode('|',$line,2)),2,'0'); db()->prepare('INSERT INTO configurable_option_values(option_id,label,price_monthly,sort_order) VALUES(?,?,?,?)')->execute([$oid,$label,(float)$price,$i+1]); }
        flash('success','Konfigüre edilebilir seçenek kaydedildi.');
    }catch(Throwable $e){ flash('error','Seçenek kaydedilemedi: '.$e->getMessage()); }
    redirect_to('admin/product-center/config-options');
}
if ($route === 'admin/product-center/config-option-delete') {
    require_admin(); verify_csrf(); ao_v2332_ensure_schema();
    $id=(int)($_GET['id'] ?? 0);
    try{
        if($id<=0) throw new Exception('Seçenek bulunamadı.');
        db()->prepare('DELETE FROM configurable_option_values WHERE option_id=?')->execute([$id]);
        db()->prepare('DELETE FROM configurable_options WHERE id=?')->execute([$id]);
        flash('success','Konfigüre edilebilir seçenek silindi.');
    }catch(Throwable $e){ flash('error','Seçenek silinemedi: '.$e->getMessage()); }
    redirect_to('admin/product-center/config-options');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/hosting-server/save') {
    verify_csrf();
    $id=(int)($_POST['id']??0); $name=trim($_POST['name']??''); $panel=trim($_POST['panel_type']??'whm'); $host=trim($_POST['hostname']??''); $ip=trim($_POST['ip_address']??''); $user=trim($_POST['username']??''); $token=trim($_POST['api_token']??''); $status=trim($_POST['status']??'inactive'); $test=(int)($_POST['test_mode']??1); $notes=trim($_POST['notes']??'');
    try{
        if(!$name) throw new Exception('Sunucu adı zorunlu.');
        if($id>0){
            if($token==='') db()->prepare('UPDATE server_nodes SET name=?,panel_type=?,hostname=?,ip_address=?,username=?,status=?,test_mode=?,notes=? WHERE id=?')->execute([$name,$panel,$host,$ip,$user,$status,$test,$notes,$id]);
            else db()->prepare('UPDATE server_nodes SET name=?,panel_type=?,hostname=?,ip_address=?,username=?,api_token=?,status=?,test_mode=?,notes=? WHERE id=?')->execute([$name,$panel,$host,$ip,$user,$token,$status,$test,$notes,$id]);
            flash('success','Sunucu/API node güncellendi.');
        } else {
            db()->prepare('INSERT INTO server_nodes(name,panel_type,hostname,ip_address,username,api_token,status,test_mode,notes) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$name,$panel,$host,$ip,$user,$token,$status,$test,$notes]); flash('success','Sunucu/API node kaydedildi.');
        }
    }catch(Throwable $e){ flash('error','Sunucu kaydedilemedi: '.$e->getMessage()); }
    redirect_to('admin/hosting-server/servers');
}


