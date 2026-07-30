<?php
// v18.7.0 Ahost Builder Pro 3.0 - site/admin/customer visual builder
function ao_builder_pro_ensure_schema(){ static $done=false; if($done) return; $done=true;
    try{ db()->exec("CREATE TABLE IF NOT EXISTS builder_pro_layouts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        target VARCHAR(32) NOT NULL,
        template_key VARCHAR(80) NOT NULL,
        title VARCHAR(190) NULL,
        layout_json LONGTEXT NULL,
        device_json LONGTEXT NULL,
        status VARCHAR(32) DEFAULT 'draft',
        created_by INT NULL,
        updated_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_builder_target_template(target, template_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("CREATE TABLE IF NOT EXISTS builder_pro_revisions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        layout_id INT NULL,
        target VARCHAR(32) NOT NULL,
        template_key VARCHAR(80) NOT NULL,
        layout_json LONGTEXT NULL,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_bp_rev(layout_id), INDEX idx_bp_target(target, template_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->prepare("INSERT INTO settings(setting_key,setting_value) VALUES('ahost_version','25.0.0-rc25'),('builder_pro_3_enabled','1'),('builder_pro_targets','site,admin,customer') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("ALTER TABLE builder_pro_layouts ADD COLUMN device VARCHAR(20) DEFAULT 'desktop'"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("ALTER TABLE builder_pro_layouts ADD COLUMN is_active TINYINT(1) DEFAULT 1"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("ALTER TABLE builder_pro_layouts ADD COLUMN global_tokens_json LONGTEXT NULL"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("ALTER TABLE builder_pro_revisions ADD COLUMN revision_note VARCHAR(190) NULL"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("CREATE TABLE IF NOT EXISTS builder_pro_global_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token_key VARCHAR(120) NOT NULL UNIQUE,
        token_value TEXT NULL,
        token_type VARCHAR(40) DEFAULT 'text',
        scope VARCHAR(40) DEFAULT 'global',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("CREATE TABLE IF NOT EXISTS builder_pro_components (
        id INT AUTO_INCREMENT PRIMARY KEY,
        component_key VARCHAR(120) NOT NULL UNIQUE,
        title VARCHAR(190) NOT NULL,
        target VARCHAR(32) DEFAULT 'site',
        component_json LONGTEXT NULL,
        is_global TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->exec("CREATE TABLE IF NOT EXISTS builder_pro_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        template_key VARCHAR(120) NOT NULL UNIQUE,
        title VARCHAR(190) NOT NULL,
        target VARCHAR(32) DEFAULT 'site',
        category VARCHAR(80) DEFAULT 'general',
        layout_json LONGTEXT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ db()->prepare("INSERT INTO settings(setting_key,setting_value) VALUES
        ('ahost_version','25.0.8-rc34'),
        ('visual_experience_studio_enabled','1'),
        ('builder_pro_content_manager','1'),
        ('builder_pro_ai_prompts','1')
        ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{
        $tokens=[
            ['brand.name','Ahost One','text','global'],
            ['brand.primary','#2563eb','color','global'],
            ['brand.accent','#06b6d4','color','global'],
            ['support.whatsapp','+90 555 000 00 00','text','support'],
            ['seo.default_title','Ahost One - Domain, Hosting ve Builder Platformu','seo','site'],
            ['campaign.hero_badge','Yeni nesil hosting deneyimi','text','site'],
        ];
        $st=db()->prepare('INSERT INTO builder_pro_global_tokens(token_key,token_value,token_type,scope) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE token_value=VALUES(token_value), token_type=VALUES(token_type), scope=VALUES(scope)');
        foreach($tokens as $t){ $st->execute($t); }
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{
        $components=[
            ['global_header','Global Header','site','[{"id":"cmp_head","cols":[{"id":"cmp_head_col","span":10,"widgets":[{"id":"cmp_head_menu","type":"header","title":"Ahost One","text":"Domain, Hosting, VPS, SSL, Blog","button":"Musteri Paneli","props":{"variableKey":"brand.name"}}]}]}]'],
            ['global_footer','Global Footer','site','[{"id":"cmp_foot","cols":[{"id":"cmp_foot_col","span":10,"widgets":[{"id":"cmp_foot_text","type":"footer","title":"Ahost One","text":"Footer menus, SEO metinleri ve destek baglantilari admin builder icinden duzenlenir.","props":{}}]}]}]'],
            ['customer_top_tabs','Musteri Ust Sekmeler','customer','[{"id":"cmp_client_tabs","cols":[{"id":"cmp_client_tabs_col","span":10,"widgets":[{"id":"cmp_client_tabs_w","type":"tabs","title":"Panel Sekmeleri","text":"Hizmetler, Domainler, Faturalar, Destek, Guvenlik","props":{}}]}]}]'],
        ];
        $st=db()->prepare('INSERT INTO builder_pro_components(component_key,title,target,component_json,is_global) VALUES(?,?,?,?,1) ON DUPLICATE KEY UPDATE title=VALUES(title), target=VALUES(target), component_json=VALUES(component_json), is_global=1');
        foreach($components as $c){ $st->execute($c); }
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_builder_pro_default_layout($target='site',$template='home'){
    $target = in_array($target,['site','admin','customer'],true) ? $target : 'site';
    if($target==='admin') return [[ 'id'=>'admin_row_1','cols'=>[
        ['id'=>'a1','span'=>2,'widgets'=>[['id'=>'ak1','type'=>'kpi','title'=>'MRR','text'=>'Aylık tekrar gelir','price'=>'₺75.756']]],
        ['id'=>'a2','span'=>2,'widgets'=>[['id'=>'ak2','type'=>'kpi','title'=>'ARR','text'=>'Yıllık gelir projeksiyonu','price'=>'₺909.077']]],
        ['id'=>'a3','span'=>3,'widgets'=>[['id'=>'ak3','type'=>'chart','title'=>'Gelir Analitiği','text'=>'Gelir, sipariş ve ticket trendi.']]],
        ['id'=>'a4','span'=>3,'widgets'=>[['id'=>'ak4','type'=>'ticket','title'=>'AI Operasyon Merkezi','text'=>'Öncelikli aksiyonlar ve SLA riskleri.']]],
    ]] ];
    if($target==='customer') return [[ 'id'=>'client_row_1','cols'=>[
        ['id'=>'c1','span'=>4,'widgets'=>[['id'=>'cw1','type'=>'renewal','title'=>'Yenileme Merkezi','text'=>'Hosting/domain/SSL yaklaşan ödemeler.']]],
        ['id'=>'c2','span'=>3,'widgets'=>[['id'=>'cw2','type'=>'product','title'=>'Aktif Hizmetler','text'=>'Disk, trafik, SSL ve sağlık göstergeleri.']]],
        ['id'=>'c3','span'=>3,'widgets'=>[['id'=>'cw3','type'=>'invoice','title'=>'Son Faturalar','text'=>'Ödeme durumu ve tahsilat akışı.']]],
    ]] ];
    return [[ 'id'=>'site_slider_row','cols'=>[ ['id'=>'sl1','span'=>10,'widgets'=>[['id'=>'sw_slider','type'=>'slider','title'=>'Menü Altı Slider','text'=>'Admin Slider Yönetimi ile kontrol edilir.','button'=>'Aktif']]] ]], [ 'id'=>'site_row_1','cols'=>[
        ['id'=>'s1','span'=>5,'widgets'=>[['id'=>'sw1','type'=>'hero','title'=>'Domain, hosting ve AI tek SaaS panelde','text'=>'Ahost One ile dijital hizmet satışını uçtan uca yönetin.','button'=>'Hemen Başla']]],
        ['id'=>'s2','span'=>3,'widgets'=>[['id'=>'sw2','type'=>'domain','title'=>'Domain Search Center Pro','text'=>'Domain sorgulama, WHOIS ve DNS araçlarını tek alanda kullanın.']]],
        ['id'=>'s3','span'=>2,'widgets'=>[['id'=>'sw_support','type'=>'support_widget','title'=>'Sağ Alt Destek','text'=>'WhatsApp, canlı destek, AI yardım ve ticket widgetı.']]],
    ]] ];
}
function ao_builder_pro_has_widget($target='site',$template='home',$type='support_widget',$default=true){
    $target = in_array($target,['site','admin','customer'],true) ? $target : 'site';
    $template = preg_replace('/[^a-z0-9_-]/i','', (string)$template) ?: 'home';
    $hasSaved=false; $layout=[];
    try{
        ao_builder_pro_ensure_schema();
        $q=db()->prepare('SELECT layout_json FROM builder_pro_layouts WHERE target=? AND template_key=? LIMIT 1');
        $q->execute([$target,$template]);
        $raw=$q->fetchColumn();
        if($raw){ $hasSaved=true; $layout=json_decode((string)$raw,true) ?: []; }
    }catch(Throwable $e){ return (bool)$default; }
    if(!$hasSaved) return (bool)$default;
    foreach((array)$layout as $row){
        foreach((array)($row['cols'] ?? []) as $col){
            foreach((array)($col['widgets'] ?? []) as $w){
                if(($w['type'] ?? '') === $type) return true;
            }
        }
    }
    return false;
}
function ao_builder_pro_get_layout($target='site',$template='home'){
    ao_builder_pro_ensure_schema();
    $q=db()->prepare('SELECT * FROM builder_pro_layouts WHERE target=? AND template_key=? LIMIT 1'); $q->execute([$target,$template]);
    $row=$q->fetch();
    if($row && !empty($row['layout_json'])) return json_decode($row['layout_json'],true) ?: ao_builder_pro_default_layout($target,$template);
    return ao_builder_pro_default_layout($target,$template);
}
function ao_builder_pro_save_layout($target,$template,$json){
    ao_builder_pro_ensure_schema();
    $target = in_array($target,['site','admin','customer'],true) ? $target : 'site';
    $template = preg_replace('/[^a-z0-9_-]/i','', (string)$template) ?: 'home';
    $arr=json_decode($json,true); if(!is_array($arr)) throw new Exception('Builder JSON geçersiz.');
    $json=json_encode($arr, JSON_UNESCAPED_UNICODE);
    $admin=(int)($_SESSION['admin_id'] ?? 0);
    db()->prepare("INSERT INTO builder_pro_layouts(target,template_key,title,layout_json,status,created_by,updated_by) VALUES(?,?,?,?, 'published', ?, ?) ON DUPLICATE KEY UPDATE layout_json=VALUES(layout_json), status='published', updated_by=VALUES(updated_by), updated_at=NOW()")->execute([$target,$template,ucfirst($target).' '.$template,$json,$admin,$admin]);
    $layoutId=(int)db()->lastInsertId();
    if(!$layoutId){$q=db()->prepare('SELECT id FROM builder_pro_layouts WHERE target=? AND template_key=? LIMIT 1');$q->execute([$target,$template]);$layoutId=(int)($q->fetchColumn() ?: 0);} 
    db()->prepare('INSERT INTO builder_pro_revisions(layout_id,target,template_key,layout_json,created_by) VALUES(?,?,?,?,?)')->execute([$layoutId,$target,$template,$json,$admin]);
}
function ao_builder_pro_columns(string $table): array {
    try {
        return array_column(db()->query('SHOW COLUMNS FROM `'.$table.'`')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    } catch(Throwable $e) {
        return [];
    }
}
function ao_builder_pro_money_from_text($value): ?float {
    $raw = trim(strip_tags((string)$value));
    if ($raw === '') return null;
    if (!preg_match('/(\d[\d\.\,\s]*)/u', $raw, $m)) return null;
    $num = preg_replace('/\s+/', '', $m[1]);
    if (str_contains($num, ',') && str_contains($num, '.')) {
        $num = str_replace('.', '', $num);
        $num = str_replace(',', '.', $num);
    } elseif (str_contains($num, ',')) {
        $num = str_replace(',', '.', $num);
    }
    $amount = round((float)$num, 2);
    return $amount > 0 ? $amount : null;
}
function ao_builder_pro_sync_bound_updates($json): array {
    $result = ['products' => 0, 'prices' => 0];
    $data = json_decode((string)$json, true);
    if (!is_array($data)) return $result;
    $products = (array)($data['products'] ?? []);
    if (!$products) return $result;
    if (function_exists('ao_v237_ensure_product_pricing_schema')) ao_v237_ensure_product_pricing_schema();
    $productCols = ao_builder_pro_columns('products');
    $pricingCols = ao_builder_pro_columns('product_pricing');
    $allowedCycles = ['one_time','onetime','monthly','quarterly','semiannually','annually','biennially','triennially'];
    foreach ($products as $item) {
        $id = (int)($item['id'] ?? 0);
        if ($id <= 0) continue;
        $name = trim((string)($item['name'] ?? ''));
        $short = trim((string)($item['short_description'] ?? ''));
        $set = [];
        $vals = [];
        if ($name !== '' && in_array('name', $productCols, true)) {
            $set[] = 'name=?';
            $vals[] = mb_substr($name, 0, 190, 'UTF-8');
        }
        if ($short !== '' && in_array('short_description', $productCols, true)) {
            $set[] = 'short_description=?';
            $vals[] = mb_substr($short, 0, 700, 'UTF-8');
        }
        if ($short !== '' && in_array('meta_description', $productCols, true)) {
            $set[] = 'meta_description=?';
            $vals[] = mb_substr($short, 0, 255, 'UTF-8');
        }
        if ($set) {
            $vals[] = $id;
            db()->prepare('UPDATE products SET '.implode(',', $set).' WHERE id=?')->execute($vals);
            $result['products']++;
        }
        $amountTry = ao_builder_pro_money_from_text($item['price_label'] ?? '');
        if ($amountTry === null || !$pricingCols) continue;
        $cycle = trim((string)($item['cycle'] ?? ''));
        if (!in_array($cycle, $allowedCycles, true)) {
            try {
                $q = db()->prepare('SELECT billing_cycle FROM products WHERE id=? LIMIT 1');
                $q->execute([$id]);
                $cycle = (string)($q->fetchColumn() ?: 'monthly');
            } catch(Throwable $e) {
                $cycle = 'monthly';
            }
        }
        if (!in_array($cycle, $allowedCycles, true)) $cycle = 'monthly';
        $usd = function_exists('ao_v237_price_currency_from_try') ? ao_v237_price_currency_from_try($amountTry, 'USD') : round($amountTry / 47.25, 2);
        $margin = function_exists('ao_v237_currency_margin') ? ao_v237_currency_margin('TRY') : 0;
        try {
            $q = db()->prepare('SELECT id FROM product_pricing WHERE product_id=? AND cycle=? LIMIT 1');
            $q->execute([$id, $cycle]);
            $pricingId = (int)($q->fetchColumn() ?: 0);
            if ($pricingId > 0) {
                $priceSet = ['price=?', 'currency=?', 'is_active=1'];
                $priceVals = [$amountTry, 'TRY'];
                foreach ([
                    'price_try' => $amountTry,
                    'price_usd' => $usd,
                    'base_currency' => 'TRY',
                    'exchange_rate' => 1,
                    'margin_percent' => $margin,
                    'auto_convert' => 1,
                ] as $col => $val) {
                    if (in_array($col, $pricingCols, true)) {
                        $priceSet[] = $col.'=?';
                        $priceVals[] = $val;
                    }
                }
                $priceVals[] = $pricingId;
                db()->prepare('UPDATE product_pricing SET '.implode(',', $priceSet).' WHERE id=?')->execute($priceVals);
            } else {
                $payload = [
                    'product_id' => $id,
                    'cycle' => $cycle,
                    'price' => $amountTry,
                    'setup_fee' => 0,
                    'currency' => 'TRY',
                    'price_usd' => $usd,
                    'price_try' => $amountTry,
                    'setup_fee_usd' => 0,
                    'setup_fee_try' => 0,
                    'base_currency' => 'TRY',
                    'exchange_rate' => 1,
                    'margin_percent' => $margin,
                    'auto_convert' => 1,
                    'is_active' => 1,
                ];
                $payload = array_filter($payload, fn($v, $k) => in_array($k, $pricingCols, true), ARRAY_FILTER_USE_BOTH);
                $fields = array_keys($payload);
                db()->prepare('INSERT INTO product_pricing(`'.implode('`,`', $fields).'`) VALUES('.implode(',', array_fill(0, count($fields), '?')).')')->execute(array_values($payload));
            }
            $legacy = [];
            $legacyVals = [];
            foreach (['price' => $amountTry, 'currency' => 'TRY', 'currency_code' => 'TRY', 'billing_cycle' => $cycle] as $col => $val) {
                if (in_array($col, $productCols, true)) {
                    $legacy[] = $col.'=?';
                    $legacyVals[] = $val;
                }
            }
            if ($legacy) {
                $legacyVals[] = $id;
                db()->prepare('UPDATE products SET '.implode(',', $legacy).' WHERE id=?')->execute($legacyVals);
            }
            $result['prices']++;
        } catch(Throwable $e) {
            error_log('[ao builder product sync] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
        }
    }
    return $result;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/builder-pro/inline-save') {
    require_admin();
    verify_csrf();
    header('Content-Type: application/json; charset=utf-8');
    try {
        ao_builder_pro_save_layout($_POST['target'] ?? 'site', $_POST['template_key'] ?? 'home', $_POST['layout_json'] ?? '[]');
        $sync = ao_builder_pro_sync_bound_updates($_POST['bound_updates_json'] ?? '{}');
        echo json_encode(['ok'=>true,'message'=>'Builder düzeni kaydedildi.','sync'=>$sync], JSON_UNESCAPED_UNICODE);
    } catch(Throwable $e) {
        http_response_code(422);
        echo json_encode(['ok'=>false,'message'=>'Builder kaydedilemedi: '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/builder-pro/upload-asset') {
    require_admin();
    verify_csrf();
    header('Content-Type: application/json; charset=utf-8');
    try {
        if (empty($_FILES['asset']['tmp_name']) || !is_uploaded_file($_FILES['asset']['tmp_name'])) {
            throw new Exception('Dosya seçilmedi.');
        }
        if ((int)($_FILES['asset']['size'] ?? 0) > 8 * 1024 * 1024) {
            throw new Exception('Görsel en fazla 8 MB olabilir.');
        }
        $tmp = $_FILES['asset']['tmp_name'];
        $allowedExt = ['jpg','jpeg','png','webp','gif','svg','avif','bmp','ico'];
        $origExt = strtolower(pathinfo((string)($_FILES['asset']['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($origExt, $allowedExt, true)) {
            throw new Exception('Desteklenen görsel formatları: JPG, PNG, WEBP, GIF, SVG, AVIF, BMP, ICO.');
        }
        $info = @getimagesize($tmp);
        $looksImage = $info && !empty($info['mime']) && str_starts_with(strtolower((string)$info['mime']), 'image/');
        if ($origExt === 'svg') {
            $svg = file_get_contents($tmp, false, null, 0, 262144) ?: '';
            if (!preg_match('~<svg[\s>]~i', $svg) || preg_match('~<script|on\w+\s*=|javascript:|<foreignObject~i', $svg)) {
                throw new Exception('SVG güvenli değil veya geçerli değil.');
            }
            $looksImage = true;
        }
        if (!$looksImage && in_array($origExt, ['avif','ico'], true)) {
            $looksImage = true;
        }
        if (!$looksImage) {
            throw new Exception('Sadece geçerli görsel dosyası yüklenebilir.');
        }
        $dir = __DIR__ . '/public/uploads/builder';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            throw new Exception('Yükleme klasörü oluşturulamadı.');
        }
        $ext = $origExt === 'jpeg' ? 'jpg' : $origExt;
        $name = 'builder_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = $dir . '/' . $name;
        if (!@move_uploaded_file($tmp, $target)) {
            throw new Exception('Dosya yüklenemedi.');
        }
        echo json_encode(['ok'=>true,'url'=>url('public/uploads/builder/'.$name),'message'=>'Görsel yüklendi.'], JSON_UNESCAPED_UNICODE);
    } catch(Throwable $e) {
        http_response_code(422);
        echo json_encode(['ok'=>false,'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/builder-pro/save') { require_admin(); verify_csrf(); try{ ao_builder_pro_save_layout($_POST['target'] ?? 'site', $_POST['template_key'] ?? 'home', $_POST['layout_json'] ?? '[]'); flash('success','Builder Pro 3.0 düzeni kaydedildi.'); }catch(Throwable $e){ flash('error','Builder kaydedilemedi: '.$e->getMessage()); } redirect_to('admin/builder-pro?target='.urlencode($_POST['target'] ?? 'site').'&template='.urlencode($_POST['template_key'] ?? 'home')); }
if ($route==='admin/builder-pro') { require_admin(); ao_builder_pro_ensure_schema(); view('builder-pro/index', ['pageTitle'=>'Ahost Builder Pro 3.0']); exit; }
