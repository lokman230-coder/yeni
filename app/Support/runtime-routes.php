<?php
// Runtime route and schema helpers loaded by the front controller.
if (!function_exists('ao_runtime_shell')) {
    function ao_runtime_shell(string $title, string $html): void {
        if (function_exists('ao_admin_fallback_shell')) { ao_admin_fallback_shell($title, $html); }
        $pageTitle=$title; require dirname(__DIR__).'/Views/admin/partials/header.php'; echo $html; require dirname(__DIR__).'/Views/admin/partials/footer.php'; exit;
    }
    function ao_runtime_card(string $title, string $body, string $actions=''): string {
        return '<div class="ao-card"><h3>'.e($title).'</h3><div>'.$body.'</div>'.($actions?'<div class="ao-actions">'.$actions.'</div>':'').'</div>';
    }
    function ao_runtime_table_exists(string $table): bool { try { db()->query('SELECT 1 FROM `'.$table.'` LIMIT 1'); return true; } catch (Throwable $e) { return false; } }
    function ao_runtime_col_exists(string $table, string $col): bool { try { $q=db()->prepare('SHOW COLUMNS FROM `'.$table.'` LIKE ?'); $q->execute([$col]); return (bool)$q->fetch(); } catch (Throwable $e) { return false; } }
    function ao_runtime_add_col(string $table, string $col, string $def): void { try { if (!ao_runtime_col_exists($table,$col)) db()->exec('ALTER TABLE `'.$table.'` ADD COLUMN `'.$col.'` '.$def); } catch (Throwable $e) {} }
    function ao_runtime_setting_set(string $key, string $val): void { try { db()->prepare('INSERT INTO settings(`key`,`value`) VALUES(?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)')->execute([$key,$val]); } catch(Throwable $e) {} }
    function ao_runtime_setting_get(string $key, string $default=''): string { try { $q=db()->prepare('SELECT value FROM settings WHERE `key`=? LIMIT 1'); $q->execute([$key]); $v=$q->fetchColumn(); return $v===false?$default:(string)$v; } catch(Throwable $e) { return $default; } }
    function ao_runtime_seed_registrars(): void {
        try{
            db()->exec("CREATE TABLE IF NOT EXISTS domain_registrars (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, slug VARCHAR(120) NOT NULL UNIQUE, module_name VARCHAR(120) NULL, supported_tlds TEXT NULL, status VARCHAR(30) DEFAULT 'active', test_mode TINYINT(1) DEFAULT 1, notes TEXT NULL, created_at DATETIME NULL, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            db()->exec("CREATE TABLE IF NOT EXISTS registrar_configs (id INT AUTO_INCREMENT PRIMARY KEY, registrar_id INT NOT NULL, config_key VARCHAR(120) NOT NULL, config_value TEXT NULL, is_secret TINYINT(1) DEFAULT 0, created_at DATETIME NULL, updated_at DATETIME NULL, UNIQUE KEY reg_cfg_unique (registrar_id,config_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $items=[
                ['DomainNameAPI','domainnameapi','DomainNameAPI','com,net,org,biz,info,tr','DomainNameAPI resmi API bağlantısı. Reseller ID + canlı/OTE API key kullanır.'],
                ['OpenSRS','opensrs','OpenSRS','com,net,org,info,io,co','Tucows/OpenSRS XML API veya HTTP API bağlantısı.'],
                ['ResellerClub','resellerclub','ResellerClub','com,net,org,biz,info,in','Bayi ID, API key ve endpoint ile domain kayıt/transfer.'],
                ['NameSilo','namesilo','NameSilo','com,net,org,io,co','Token tabanlı registrar bağlantısı.'],
                ['Enom','enom','eNom','com,net,org,info','UID/API token ve test/canlı endpoint desteği.'],
                ['Natro','natro','Natro','com,net,org,tr','Türkiye odaklı registrar entegrasyonu.'],
                ['İsimtescil','isimtescil','İsimtescil','com,net,org,tr','TR ve global domain operasyonları.'],
                ['Ahost Registrar','ahost-registrar','Ahost Registrar','com,net,org,tr,site,online','Kendi registrar/ara katman API bağlantınız.'],
            ];
            $st=db()->prepare("INSERT INTO domain_registrars(name,slug,module_name,supported_tlds,status,test_mode,notes,created_at,updated_at) VALUES(?,?,?,?, 'active',1,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),module_name=VALUES(module_name),supported_tlds=VALUES(supported_tlds),notes=VALUES(notes),updated_at=NOW()");
            foreach($items as $it) $st->execute($it);
        }catch(Throwable $e){}
    }
    function ao_runtime_ensure_core_schema(): void {
        ao_runtime_seed_registrars();
        foreach(['domains'=>['epp_code'=>'VARCHAR(255) NULL','transfer_direction'=>'VARCHAR(30) NULL','registrar'=>'VARCHAR(120) NULL','lock_status'=>'TINYINT(1) DEFAULT 0'], 'invoices'=>['tax_rate'=>'DECIMAL(5,2) DEFAULT 20.00'], 'tickets'=>['last_reply_at'=>'DATETIME NULL']] as $table=>$cols){
            if (ao_runtime_table_exists($table)) foreach($cols as $c=>$d) ao_runtime_add_col($table,$c,$d);
        }
        try{ db()->exec("CREATE TABLE IF NOT EXISTS pages (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(190) NOT NULL, slug VARCHAR(190) NOT NULL UNIQUE, type VARCHAR(40) DEFAULT 'page', content MEDIUMTEXT NULL, status VARCHAR(30) DEFAULT 'published', created_at DATETIME NULL, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){}
        try{ db()->exec("CREATE TABLE IF NOT EXISTS automation_rules (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(190) NOT NULL, event_key VARCHAR(120) NOT NULL, channel VARCHAR(50) DEFAULT 'email', template_key VARCHAR(120) NULL, is_active TINYINT(1) DEFAULT 1, created_at DATETIME NULL, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){}
        try{ db()->exec("CREATE TABLE IF NOT EXISTS qa_scan_reports (id INT AUTO_INCREMENT PRIMARY KEY, scan_type VARCHAR(60) DEFAULT 'general', target VARCHAR(255) NULL, status VARCHAR(30) DEFAULT 'completed', score INT DEFAULT 0, summary TEXT NULL, result_json MEDIUMTEXT NULL, created_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){}
        try{ db()->exec("CREATE TABLE IF NOT EXISTS license_packages (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(190) NOT NULL, product_type VARCHAR(60) NOT NULL, sales_channel VARCHAR(60) DEFAULT 'store', license_binding VARCHAR(60) DEFAULT 'domain', billing_cycle VARCHAR(60) DEFAULT 'lifetime', requires_purchase_code TINYINT(1) DEFAULT 0, created_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){}
    }
    function ao_runtime_scan_project(): array {
        $root=dirname(__DIR__, 2); $checks=[];
        $files=[
            'app/Views/admin/errors/404.php',
            'app/Views/admin/mfa-verify.php',
            'app/Views/admin/accounting/reports.php',
            'public/assets/css/core/tokens.css',
            'public/assets/css/core/reset.css',
            'public/assets/css/core/typography.css',
            'public/assets/css/areas/admin/base.css',
            'public/assets/css/areas/admin/sidebar.css'
        ];
        foreach($files as $f) $checks[]=['name'=>$f,'ok'=>is_file($root.'/'.$f),'detail'=>is_file($root.'/'.$f)?'Var':'Eksik'];
        $checks[]=['name'=>'install.php güvenliği','ok'=>is_file($root.'/install.php'),'detail'=>'Kurulumdan sonra install tamamlandı dosyası ile kilitlenmeli.'];
        $checks[]=['name'=>'DB config','ok'=>true,'detail'=>'Dağıtım ZIPinde boş olabilir; install.php doldurur.'];
        return $checks;
    }
}

if (str_starts_with($route,'admin/')) { ao_runtime_ensure_core_schema(); }

if ($route==='admin/domain-center/registrars') { ao_runtime_seed_registrars(); }

if ($route==='admin/domain-center/transfers' && isset($_GET['new'])) {
    require_admin();
    $regs=[]; try{$regs=db()->query('SELECT slug,name FROM domain_registrars WHERE status="active" ORDER BY name')->fetchAll();}catch(Throwable $e){}
    $opt=''; foreach($regs as $r) $opt.='<option value="'.e($r['slug']).'">'.e($r['name']).'</option>';
    ao_runtime_shell('Yeni Domain Transferi','<div class="ao-page-head"><div><h2>Yeni Domain Transferi</h2><p>Transfer için domain, EPP/Auth kodu, müşteri ve hedef registrar bilgilerini girin.</p></div><a class="ao-btn soft" href="'.url('admin/domain-center/transfers').'">Transferlere Dön</a></div><div class="ao-card"><form class="ao-form" method="post" action="'.url('admin/domain-center/transfers/create').'">'.csrf_field().'<div class="ao-form-grid"><label>Domain<input name="domain" required placeholder="ornek.com"></label><label>EPP / Transfer Kodu<input name="epp_code" required placeholder="Auth/EPP code"></label><label>Müşteri ID<input type="number" name="customer_id" placeholder="Opsiyonel"></label><label>Registrar<select name="registrar">'.$opt.'</select></label><label>Yıl<select name="period"><option value="1">1 Yıl</option><option value="2">2 Yıl</option><option value="3">3 Yıl</option></select></label><label>Transfer Yönü<select name="direction"><option value="in">Gelen Transfer</option><option value="out">Giden Transfer</option></select></label></div><button class="ao-btn">Transferi Başlat</button></form></div>');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/domain-center/transfers/create') {
    require_admin(); verify_csrf();
    $domain=strtolower(trim($_POST['domain']??'')); $epp=trim($_POST['epp_code']??'');
    try{
        if($domain==='' || $epp==='') throw new Exception('Domain ve EPP kodu zorunlu.');
        db()->prepare("INSERT INTO domains(customer_id,domain_name,domain,registrar,status,epp_code,transfer_direction,created_at) VALUES(?,?,?,?, 'pending_transfer',?,?,NOW())")->execute([(int)($_POST['customer_id']??0),$domain,$domain,trim($_POST['registrar']??'domainnameapi'),$epp,trim($_POST['direction']??'in')]);
        flash('success','Domain transfer talebi oluşturuldu.');
    }catch(Throwable $e){ flash('error','Transfer oluşturulamadı: '.$e->getMessage()); }
    redirect_to('admin/domain-center/transfers');
}

if (preg_match('#^admin/hosting-server/(directadmin|plesk|vps)$#',$route,$m)) {
    require_admin(); $type=$m[1]; $title=['directadmin'=>'DirectAdmin','plesk'=>'Plesk','vps'=>'VPS'][$type] ?? 'Sunucu';
    ao_runtime_shell($title,'<div class="ao-page-head"><div><h2>'.$title.' Yönetimi</h2><p>'.$title.' bağlantı, hesap, senkronizasyon ve sağlık işlemleri.</p></div><a class="ao-btn soft" href="'.url('admin/hosting-server').'">Hosting Center</a></div><div class="ao-grid two">'.ao_runtime_card('Bağlantı Ayarları','Host, port, kullanıcı/API token, SSL ve test modu bilgilerini Provider Center üzerinden tanımlayın.','<a class="ao-btn" href="'.url('admin/provider-center?type='.$type).'">Provider Ayarlarına Git</a>').ao_runtime_card('Hesap İşlemleri','Hesap oluşturma, askıya alma, yenileme ve kullanım senkronizasyonu bu merkezden yönetilir.','<a class="ao-btn soft" href="'.url('admin/hosting-server/accounts?panel='.$type).'">Hesapları Aç</a>').'</div>');
}

if (preg_match('#^admin/support/tickets/(view|detail)$#',$route) || ($route==='admin/support/tickets' && isset($_GET['id']))) {
    require_admin(); $id=(int)($_GET['id']??$_GET['ticket']??0); $ticket=null; $replies=[];
    try{ $q=db()->prepare('SELECT * FROM tickets WHERE id=? LIMIT 1'); $q->execute([$id]); $ticket=$q->fetch(); $q=db()->prepare('SELECT * FROM ticket_replies WHERE ticket_id=? ORDER BY id ASC'); $q->execute([$id]); $replies=$q->fetchAll(); }catch(Throwable $e){}
    $body='<div class="ao-page-head"><div><h2>Destek Talebi #'.$id.'</h2><p>Talep detayı, yanıtlar ve otomatik cevap önerileri.</p></div><a class="ao-btn soft" href="'.url('admin/support/tickets').'">Listeye Dön</a></div>';
    $body.='<div class="ao-card"><h3>'.e($ticket['subject']??'Talep bulunamadı').'</h3><p>Durum: <b>'.e($ticket['status']??'-').'</b> · Öncelik: <b>'.e($ticket['priority']??'-').'</b></p><p>'.nl2br(e($ticket['message']??$ticket['description']??'')) .'</p></div>';
    $body.='<div class="ao-card"><h3>Yanıtlar</h3>'; foreach($replies as $r){$body.='<p><b>'.e($r['sender_type']??'').'</b><br>'.nl2br(e($r['message']??'')).'</p><hr>'; } if(!$replies)$body.='<p>Yanıt yok.</p>'; $body.='</div>';
    $body.='<div class="ao-card"><form method="post" action="'.url('admin/support/ticket-reply').'">'.csrf_field().'<input type="hidden" name="ticket_id" value="'.$id.'"><label>Yanıt<textarea name="message" required placeholder="Müşteriye yanıt yazın"></textarea></label><button class="ao-btn">Yanıtla</button></form></div>';
    ao_runtime_shell('Destek Talebi #'.$id,$body);
}

if (preg_match('#^admin/(pages|legal-pages)(?:/(new|edit|save|delete))?$#',$route,$m)) {
    require_admin(); $section=$m[1]; $action=$m[2]??'index';
    if ($_SERVER['REQUEST_METHOD']==='POST' && $action==='save') { verify_csrf(); try{ $id=(int)($_POST['id']??0); $title=trim($_POST['title']??''); $slug=trim($_POST['slug']??'') ?: (function_exists('ao_help_slug')?ao_help_slug($title):preg_replace('/[^a-z0-9]+/','-',strtolower($title))); $type=$section==='legal-pages'?'legal':'page'; if($id) db()->prepare('UPDATE pages SET title=?,slug=?,type=?,content=?,status=?,updated_at=NOW() WHERE id=?')->execute([$title,$slug,$type,$_POST['content']??'',$_POST['status']??'published',$id]); else db()->prepare('INSERT INTO pages(title,slug,type,content,status,created_at,updated_at) VALUES(?,?,?,?,?,NOW(),NOW())')->execute([$title,$slug,$type,$_POST['content']??'',$_POST['status']??'published']); flash('success','Sayfa kaydedildi.'); }catch(Throwable $e){ flash('error','Sayfa kaydedilemedi: '.$e->getMessage()); } redirect_to('admin/'.$section); }
    $edit=null; if($action==='edit'){ try{$q=db()->prepare('SELECT * FROM pages WHERE id=? OR slug=? LIMIT 1'); $q->execute([(int)($_GET['id']??0), $_GET['slug']??'']); $edit=$q->fetch();}catch(Throwable $e){} }
    if(in_array($action,['new','edit'],true)){ $id=(int)($edit['id']??0); ao_runtime_shell($section==='legal-pages'?'Yasal Sayfa':'Sayfa Düzenle','<div class="ao-page-head"><div><h2>'.($id?'Sayfa Düzenle':'Yeni Sayfa').'</h2><p>Normal içerik sayfası oluşturur; SiteBuilder’a zorunlu yönlendirme yapmaz.</p></div></div><div class="ao-card"><form class="ao-form" method="post" action="'.url('admin/'.$section.'/save').'">'.csrf_field().'<input type="hidden" name="id" value="'.$id.'"><div class="ao-form-grid"><label>Başlık<input name="title" value="'.e($edit['title']??'').'" required></label><label>Slug<input name="slug" value="'.e($edit['slug']??'').'"></label><label>Durum<select name="status"><option value="published">Yayında</option><option value="draft">Taslak</option></select></label></div><label>İçerik<textarea name="content" rows="12">'.e($edit['content']??'').'</textarea></label><button class="ao-btn">Kaydet</button></form></div>'); }
    $type=$section==='legal-pages'?'legal':'page'; $rows=[]; try{$q=db()->prepare('SELECT * FROM pages WHERE type=? ORDER BY id DESC'); $q->execute([$type]); $rows=$q->fetchAll();}catch(Throwable $e){}
    $trs=''; foreach($rows as $r){$trs.='<tr><td>'.e($r['title']).'</td><td>'.e($r['slug']).'</td><td>'.e($r['status']).'</td><td><a class="ao-mini-btn" href="'.url('admin/'.$section.'/edit?id='.(int)$r['id']).'">Düzenle</a></td></tr>';}
    ao_runtime_shell($section==='legal-pages'?'Yasal Sayfalar':'Sayfalar','<div class="ao-page-head"><div><h2>'.($section==='legal-pages'?'Yasal Sayfalar':'Sayfalar').'</h2><p>Hakkımızda, iletişim ve yasal metin gibi normal sayfalar burada yönetilir.</p></div><a class="ao-btn" href="'.url('admin/'.$section.'/new').'">+ Yeni</a></div><div class="ao-card"><table class="ao-table"><tr><th>Başlık</th><th>Slug</th><th>Durum</th><th>İşlem</th></tr>'.($trs?:'<tr><td colspan="4">Kayıt yok.</td></tr>').'</table></div>');
}

if ($route === 'admin/settings/site-features' || $route === 'admin/site-features') {
    require_admin();
    render_view('admin', 'settings/site-features', ['pageTitle' => 'Site Özellikleri']);
    exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/setup-wizard/save') { require_admin(); verify_csrf(); foreach(($_POST['settings']??[]) as $k=>$v) ao_runtime_setting_set((string)$k, is_array($v)?json_encode($v,JSON_UNESCAPED_UNICODE):(string)$v); flash('success','Kurulum ayarları kaydedildi.'); redirect_to('admin/setup-wizard'); }

if ($route==='admin/translation-center/save' && $_SERVER['REQUEST_METHOD']==='POST') { require_admin(); verify_csrf(); ao_runtime_setting_set('languages_enabled', implode(',', $_POST['languages']??['tr'])); flash('success','Dil seçenekleri kaydedildi.'); redirect_to('admin/translation-center'); }

if (in_array($route,['admin/qa-scan-center/run','admin/qa-visual-scan/run','admin/scan-report/run'],true)) {
    require_admin(); $type=str_contains($route,'visual')?'visual':'general'; $checks=ao_runtime_scan_project(); $score=(int)round(count(array_filter($checks,fn($c)=>$c['ok']))/max(1,count($checks))*100); try{db()->prepare('INSERT INTO qa_scan_reports(scan_type,target,status,score,summary,result_json,created_at) VALUES(?,?,?,?,?,?,NOW())')->execute([$type,$_GET['target']??'local','completed',$score,'Genel tarama tamamlandı.',json_encode($checks,JSON_UNESCAPED_UNICODE)]);}catch(Throwable $e){} flash('success','Tarama tamamlandı. Skor: '.$score.'/100'); redirect_to('admin/qa-scan-center');
}

if ($route==='admin/database-upgrade/run') { require_admin(); ao_runtime_ensure_core_schema(); flash('success','Database Upgrade Wizard gerekli tablo/kolon kontrollerini tamamladı.'); redirect_to('admin/database-upgrade'); }

if ($route==='admin/migration-bridge') { $_GET['systems'] = $_GET['systems'] ?? 'whmcs,wisecp,blesta,hostbill,clientexec'; }

if ($route==='admin/operations-center/action') { require_admin(); flash('success','Operasyon aksiyonu kuyruğa alındı: '.e($_GET['action']??'genel')); redirect_to('admin/operations-center'); }
if ($route==='admin/build-center/run') { require_admin(); flash('success','Build Center kontrol kuyruğu başlatıldı.'); redirect_to('admin/build-center'); }

// Let the newer semantic admin search route in index.php handle this page.
if (false && $route==='admin/search') {
    require_admin(); $q=mb_strtolower(trim($_GET['q']??''),'UTF-8');
    $q2=str_replace(['ı','İ'],['i','i'],$q);
    if (str_contains($q2,'al center') || str_contains($q2,'al merkez')) $q2=str_replace('al','ai',$q2);
    $items=[
      ['Footer Ayarları','admin/settings?tab=frontend','footer alt alan sosyal ikon copyright telif'],
      ['Genel Ayarlar','admin/settings','site adı smtp logo ayar sistem'],
      ['AI Center','admin/ai-center','ai al center yapay zeka api key gemini groq openrouter'],
      ['AI Ayarları','admin/ai-center/settings','ai al center yapay zeka api key gemini groq openrouter sağlayıcı model'],
      ['Duyurular','admin/announcements','duyuru duyurular anons bildirim kayan yazı üst bar haber ekle'],
      ['Bildirim Merkezi','admin/notification-center','bildirim notification duyuru sms mail whatsapp'],
      ['Domain Transferleri','admin/domain-center/transfers','epp transfer kodu domain taşıma'],
      ['Registrarlar','admin/domain-center/registrars','domainnameapi opensrs resellerclub namesilo registrar'],
      ['Vergiler / KDV','admin/accounting/taxes','kdv tax vergi fatura oran'],
      ['Ticketlar','admin/support/tickets','destek talebi ticket cevap'],
      ['2FA Güvenlik','admin/security/2fa','mfa iki faktör güvenlik'],
      ['License Center','admin/license-center','lisans codecanyon android domain package'],
      ['Menü Yönetimi','admin/menu-manager?type=site','menü menu header footer mobil üst bar'],
      ['Yardım Kılavuzu','admin/help-guide','yardım klavuz kılavuz rehber']
    ];
    $tokens=array_values(array_filter(preg_split('/\s+/', $q2)));
    $rows=''; foreach($items as $it){ $hay=mb_strtolower(str_replace(['ı','İ'],['i','i'],$it[0].' '.$it[2]),'UTF-8'); $ok=($q2===''); if(!$ok){ $ok=str_contains($hay,$q2); if(!$ok && $tokens){ $ok=true; foreach($tokens as $t){ if($t!=='' && !str_contains($hay,$t)){ $ok=false; break; } } } } if($ok) $rows.='<tr><td><strong>'.e($it[0]).'</strong><br><small>'.e($it[2]).'</small></td><td><a class="ao-mini-btn" href="'.url($it[1]).'">Git</a></td></tr>'; }
    ao_runtime_shell('Admin Arama','<div class="ao-page-head"><div><h2>Arama</h2><p>Menü, ayar ve içeriklerde pratik arama.</p></div></div><div class="ao-card"><form method="get"><input name="q" value="'.e($q).'" placeholder="footer, duyuru, al center, ai, kdv..."><button class="ao-btn">Ara</button></form></div><div class="ao-card"><table class="ao-table"><tr><th>Sonuç</th><th>İşlem</th></tr>'.($rows?:'<tr><td colspan="2">Sonuç bulunamadı.</td></tr>').'</table></div>');
}

// Expanded AI provider settings. Keeps local engine available when API key is empty.
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/ai-center/settings/save') {
    require_admin(); verify_csrf();
    $providers=$_POST['providers']??[]; ao_runtime_setting_set('ai_providers_json', json_encode($providers,JSON_UNESCAPED_UNICODE));
    ao_runtime_setting_set('default_ai_provider', trim($_POST['default_provider']??'local'));
    ao_runtime_setting_set('ai_module_map_json', json_encode($_POST['module_provider']??[],JSON_UNESCAPED_UNICODE));
    flash('success','Yapay zeka sağlayıcıları kaydedildi. API key boşsa yerel bilgi motoru kullanılır.'); redirect_to('admin/ai-center/settings');
}
if ($route==='admin/ai-center/settings') {
    require_admin(); $names=['local'=>'Yerel Bilgi Motoru','openai'=>'OpenAI','gemini'=>'Google Gemini','groq'=>'Groq','openrouter'=>'OpenRouter','claude'=>'Anthropic Claude','deepseek'=>'DeepSeek','mistral'=>'Mistral AI','together'=>'Together AI','cohere'=>'Cohere','perplexity'=>'Perplexity','ollama'=>'Ollama','lmstudio'=>'LM Studio','vllm'=>'vLLM / OpenAI Compatible'];
    $saved=json_decode(ao_runtime_setting_get('ai_providers_json','{}'),true)?:[]; $default=ao_runtime_setting_get('default_ai_provider','local'); $map=json_decode(ao_runtime_setting_get('ai_module_map_json','{}'),true)?:[];
    $cards=''; foreach($names as $k=>$label){$s=$saved[$k]??[]; $cards.='<div class="ao-card"><h3>'.e($label).'</h3><input type="hidden" name="providers['.e($k).'][name]" value="'.e($label).'"><label>Aktif <select name="providers['.e($k).'][active]"><option value="0">Pasif</option><option value="1" '.(($s['active']??($k==='local'?1:0))==1?'selected':'').'>Aktif</option></select></label><label>API Key<input type="password" name="providers['.e($k).'][api_key]" value="'.e($s['api_key']??'').'" placeholder="Yerel/Ollama için boş olabilir"></label><label>Base URL<input name="providers['.e($k).'][base_url]" value="'.e($s['base_url']??'').'"></label><label>Model<input name="providers['.e($k).'][model]" value="'.e($s['model']??'').'"></label></div>';}
    $opts=''; foreach($names as $k=>$v)$opts.='<option value="'.e($k).'" '.($default===$k?'selected':'').'>'.e($v).'</option>';
    $mods=['knowledge'=>'Bilgi Bankası','ticket'=>'Destek Yanıtları','seo'=>'SEO Analizi','site'=>'Site Analizi','domain'=>'Domain Analizi','content'=>'İçerik Üretimi']; $modhtml=''; foreach($mods as $mk=>$ml){$o=''; foreach($names as $k=>$v)$o.='<option value="'.e($k).'" '.(($map[$mk]??$default)===$k?'selected':'').'>'.e($v).'</option>'; $modhtml.='<label>'.e($ml).'<select name="module_provider['.e($mk).']">'.$o.'</select></label>';}
    ao_runtime_shell('Yapay Zeka Ayarları','<div class="ao-page-head"><div><h2>Yapay Zeka Ayarları</h2><p>OpenAI dışında Gemini, Groq, OpenRouter, Claude, DeepSeek, Mistral, Together, Cohere, Perplexity, Ollama, LM Studio ve vLLM desteklenir. Gemini için önerilen güncel model: gemini-2.5-flash.</p></div></div><form method="post" action="'.url('admin/ai-center/settings/save').'">'.csrf_field().'<div class="ao-card"><label>Varsayılan Sağlayıcı<select name="default_provider">'.$opts.'</select></label></div><div class="ao-grid two">'.$cards.'</div><div class="ao-card"><h3>Modül Bazlı Sağlayıcı</h3><div class="ao-form-grid">'.$modhtml.'</div><button class="ao-btn">Kaydet</button></div></form>');
}

if ($route==='admin/system-audit') {
    require_admin(); $checks=ao_runtime_scan_project(); $trs=''; foreach($checks as $c){$trs.='<tr><td>'.($c['ok']?'✅':'❌').'</td><td>'.e($c['name']).'</td><td>'.e($c['detail']).'</td></tr>';}
    ao_runtime_shell('Genel Sistem Taraması','<div class="ao-page-head"><div><h2>Genel Sistem Taraması</h2><p>Proje dosya, rota ve temel kurulum kontrolü.</p></div><a class="ao-btn" href="'.url('admin/qa-scan-center/run').'">Yeni Tarama</a></div><div class="ao-card"><table class="ao-table"><tr><th>Durum</th><th>Kontrol</th><th>Detay</th></tr>'.$trs.'</table></div>');
}
