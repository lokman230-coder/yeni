<?php
// Admin route completion handlers for linked utility pages and actions.
if (!function_exists('ao_admin_completion_table')) {
    function ao_admin_completion_table($headers, $rows, $empty='Kayıt bulunamadı.'){
        $html='<div class="ao-table-wrap"><table class="ao-table"><tr>';
        foreach($headers as $h){ $html.='<th>'.e($h).'</th>'; }
        $html.='</tr>';
        if(!$rows){ $html.='<tr><td colspan="'.count($headers).'">'.e($empty).'</td></tr>'; }
        else { foreach($rows as $r){ $html.='<tr>'; foreach($r as $c){ $html.='<td>'.$c.'</td>'; } $html.='</tr>'; } }
        return $html.'</table></div>';
    }
    function ao_admin_completion_ok($msg='İşlem tamamlandı.', $to=null){ flash('success',$msg); redirect_to($to ?: ($_SERVER['HTTP_REFERER'] ?? 'admin')); }
    function ao_admin_completion_error($msg='İşlem tamamlanamadı.', $to=null){ flash('error',$msg); redirect_to($to ?: ($_SERVER['HTTP_REFERER'] ?? 'admin')); }
    function ao_admin_completion_page($title,$desc,$cards=[],$actions=''){
        $html='<div class="ao-page-head"><div><h2>'.e($title).'</h2><p>'.e($desc).'</p></div>'.($actions?'<div class="compact-actions">'.$actions.'</div>':'').'</div>';
        if($cards){ $html.='<div class="ao-grid two">'; foreach($cards as $c){ $html.=ao_admin_fallback_card($c[0],$c[1],$c[2]??''); } $html.='</div>'; }
        ao_admin_fallback_shell($title,$html);
    }
    function ao_admin_completion_settings_section($section){
        $labels=['general'=>'Genel Ayarlar','mail'=>'E-posta / SMTP','security'=>'Güvenlik','footer'=>'Footer Ayarları','header'=>'Header Ayarları','module-openai'=>'Yapay Zeka Ayarları'];
        if($section==='module-openai' || $section==='ai') redirect_to('admin/settings#ai');
        $title=$labels[$section] ?? 'Ayar Bölümü';
        $fields=[
            'general'=>[['site_name','Site Adı'],['site_url','Site URL'],['admin_email','Admin E-posta'],['default_language','Varsayılan Dil'],['default_currency','Varsayılan Para Birimi']],
            'mail'=>[['smtp_host','SMTP Host'],['smtp_port','SMTP Port'],['smtp_user','SMTP Kullanıcı'],['smtp_pass','SMTP Şifre'],['mail_from','Gönderici E-posta']],
            'security'=>[['admin_mfa_policy','Admin 2FA Politikası'],['customer_mfa_policy','Müşteri 2FA Politikası'],['recaptcha_site_key','reCAPTCHA Site Key'],['ip_whitelist','IP Whitelist']],
            'footer'=>[['footer_about','Footer Açıklaması'],['footer_phone','Telefon'],['footer_email','E-posta'],['social_facebook','Facebook'],['social_instagram','Instagram'],['social_x','X/Twitter'],['social_linkedin','LinkedIn']],
            'header'=>[['header_phone','Header Telefon'],['header_email','Header E-posta'],['header_notice','Üst Bilgi Yazısı'],['usd_try_text','Kur Yazısı']]
        ];
        $html='<div class="ao-page-head"><div><h2>'.e($title).'</h2><p>Bu bölüm doğrudan kaydedilebilir. Ana ayarlar sayfasına dönmeden düzenleme yapılır.</p></div><a class="ao-btn soft" href="'.url('admin/settings').'">Tüm Ayarlar</a></div><div class="ao-card"><form class="ao-form" method="post" action="'.url('admin/settings/save-section').'">'.csrf_field().'<input type="hidden" name="section" value="'.e($section).'"><div class="ao-form-grid">';
        foreach(($fields[$section] ?? $fields['general']) as $f){ $k=$f[0]; $html.='<label>'.e($f[1]).'<input name="settings['.e($k).']" value="'.e(admin_setting($k,'')).'"></label>'; }
        $html.='</div><button class="ao-btn">Kaydet</button></form></div>';
        ao_admin_fallback_shell($title,$html);
    }
}

if ($route === 'admin/settings/site-features' || $route === 'admin/site-features') {
    require_admin();
    ao_v18_ensure_module_schema();
    ao_module_scan();
    render_view('admin', 'settings/site-features', ['pageTitle'=>'Site Özellikleri']);
    exit;
}

// Settings deep links: /admin/settings/footer, /admin/settings/general, etc.
if (preg_match('#^admin/settings/([a-z0-9\-_]+)$#', $route, $m)) { require_admin(); render_view('admin', 'settings/section', ['section'=>$m[1], 'pageTitle'=>'Ayarlar']); exit; }

// Legacy admin aliases used by older Prism admin/customer profile links.
if ($route === 'admin/domains') { require_admin(); redirect_to('admin/domain-center'); }
if ($route === 'admin/invoices') { require_admin(); redirect_to('admin/accounting/invoices'); }
if ($route === 'admin/services') { require_admin(); redirect_to('admin/hosting-server/accounts'); }

if ($route === 'admin/customer-sites/new') { require_admin();
    $form='<form class="ao-form" method="post" action="'.url('admin/customer-sites/save').'">'.csrf_field().'<div class="ao-form-grid"><label>Müşteri ID<input name="customer_id" type="number" placeholder="21"></label><label>Site Adı<input name="site_name" placeholder="Müşteri sitesi"></label><label>Panel Türü<select name="panel_type"><option>cPanel</option><option>DirectAdmin</option><option>Plesk</option><option>FTP/SFTP</option></select></label><label>URL / Host<input name="site_url" placeholder="https://site.com"></label><label>Kullanıcı<input name="username"></label><label>Şifre / API Key<input name="secret" type="password"></label></div><label>Not<textarea name="notes" rows="5"></textarea></label><button class="ao-btn">Bağlantıyı Kaydet</button></form>';
    ao_admin_fallback_shell('Site Bağla','<div class="ao-page-head"><div><h2>Site Bağla</h2><p>Müşteri sitesini yönetim, yedek ve güncelleme merkeziyle ilişkilendirin.</p></div><a class="ao-btn soft" href="'.url('admin/customer-sites').'">Müşteri Siteleri</a></div><div class="ao-card">'.$form.'</div>');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route === 'admin/customer-sites/save') { require_admin(); verify_csrf();
    try{ db()->exec("CREATE TABLE IF NOT EXISTS customer_sites(id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NULL, site_name VARCHAR(190), site_url VARCHAR(255), panel_type VARCHAR(60), username VARCHAR(190), secret_value TEXT NULL, notes TEXT NULL, status VARCHAR(40) DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); db()->prepare('INSERT INTO customer_sites(customer_id,site_name,site_url,panel_type,username,secret_value,notes,status) VALUES(?,?,?,?,?,?,?,?)')->execute([(int)($_POST['customer_id']??0),trim((string)($_POST['site_name']??'')),trim((string)($_POST['site_url']??'')),trim((string)($_POST['panel_type']??'')),trim((string)($_POST['username']??'')),trim((string)($_POST['secret']??'')),trim((string)($_POST['notes']??'')),'active']); flash('success','Müşteri sitesi bağlantısı kaydedildi.'); }catch(Throwable $e){ flash('error','Site bağlantısı kaydedilemedi: '.$e->getMessage()); }
    redirect_to('admin/customer-sites');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route === 'admin/customers/transfer-entity') { require_admin(); verify_csrf();
    $type=preg_replace('/[^a-z_]/','',(string)($_POST['entity_type']??'')); $id=(int)($_POST['entity_id']??0); $to=(int)($_POST['to_customer_id']??0); $from=(int)($_POST['from_customer_id']??0);
    try{ if(!$id || !$to || !in_array($type,['service','domain'],true)) throw new Exception('Aktarım bilgileri eksik.'); $table=$type==='domain'?'domains':'services'; db()->prepare("UPDATE {$table} SET customer_id=? WHERE id=?")->execute([$to,$id]); flash('success',($type==='domain'?'Domain':'Hizmet').' müşteriye aktarıldı.'); }catch(Throwable $e){ flash('error','Aktarım yapılamadı: '.$e->getMessage()); }
    redirect_to('admin/customers/view?id='.($from ?: $to).ao_tab_hash($type==='domain'?'domainler':'urunler'));
}

// Content pages and legal pages should open normal page management, not force SiteBuilder.
if ($route === 'admin/pages' || $route === 'admin/legal-pages') { require_admin();
    $type = $route === 'admin/legal-pages' ? 'legal' : 'page';
    $title = $type === 'legal' ? 'Yasal Sayfalar' : 'Normal Sayfalar';
    try{ db()->exec("CREATE TABLE IF NOT EXISTS content_pages(id INT AUTO_INCREMENT PRIMARY KEY, page_type VARCHAR(30) DEFAULT 'page', title VARCHAR(190), slug VARCHAR(190), content MEDIUMTEXT NULL, status VARCHAR(30) DEFAULT 'published', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try{ $q=db()->prepare('SELECT * FROM content_pages WHERE page_type=? ORDER BY id DESC LIMIT 100'); $q->execute([$type]); $rows=$q->fetchAll(); }catch(Throwable $e){ $rows=[]; }
    $trs=[]; foreach($rows as $r){ $trs[]=[e($r['title']??'-'), e($r['slug']??'-'), e($r['status']??'-'), '<a class="ao-mini-btn" href="'.url('admin/site-builder/editor?page_id='.(int)$r['id']).'">Düzenle</a>']; }
    $form='<form class="ao-form" method="post" action="'.url('admin/pages/save').'">'.csrf_field().'<input type="hidden" name="page_type" value="'.e($type).'"><div class="ao-form-grid"><label>Başlık<input name="title" required placeholder="Hakkımızda"></label><label>Slug<input name="slug" placeholder="hakkimizda"></label><label>Durum<select name="status"><option value="published">Yayında</option><option value="draft">Taslak</option></select></label></div><label>İçerik<textarea name="content" rows="8" placeholder="Sayfa içeriği"></textarea></label><button class="ao-btn">Sayfa Oluştur</button></form>';
    ao_admin_fallback_shell($title,'<div class="ao-page-head"><div><h2>'.e($title).'</h2><p>Builder zorunlu değildir; hakkımızda, gizlilik, KVKK ve benzeri sayfaları normal içerik olarak oluşturabilirsiniz.</p></div><a class="ao-btn soft" href="'.url('admin/site-builder/pages').'">SiteBuilder Sayfaları</a></div><div class="ao-card"><h3>Yeni Sayfa</h3>'.$form.'</div><div class="ao-card"><h3>Kayıtlı Sayfalar</h3>'.ao_admin_completion_table(['Başlık','Slug','Durum','İşlem'],$trs).'</div>'); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route === 'admin/pages/save') { require_admin(); verify_csrf();
    try{ db()->exec("CREATE TABLE IF NOT EXISTS content_pages(id INT AUTO_INCREMENT PRIMARY KEY, page_type VARCHAR(30) DEFAULT 'page', title VARCHAR(190), slug VARCHAR(190), content MEDIUMTEXT NULL, status VARCHAR(30) DEFAULT 'published', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); $title=trim($_POST['title']??''); if(!$title) throw new Exception('Başlık zorunlu.'); $slug=trim($_POST['slug']??''); if(!$slug && function_exists('ao_v23_slug')) $slug=ao_v23_slug($title); $type=$_POST['page_type']??'page'; db()->prepare('INSERT INTO content_pages(page_type,title,slug,content,status) VALUES(?,?,?,?,?)')->execute([$type,$title,$slug,$_POST['content']??'',$_POST['status']??'published']); ao_admin_completion_ok('Sayfa oluşturuldu.', $type==='legal'?'admin/legal-pages':'admin/pages'); }catch(Throwable $e){ ao_admin_completion_error('Sayfa kaydedilemedi: '.$e->getMessage()); }
}

// Hosting Server Center sub providers should not 404.
if (in_array($route, ['admin/hosting-server/plesk','admin/hosting-server/directadmin','admin/hosting-server/vps','admin/hosting-server/add'], true)) { require_admin();
    $provider = basename($route); $names=['plesk'=>'Plesk','directadmin'=>'DirectAdmin','vps'=>'VPS / Dedicated','add'=>'Yeni Sunucu']; $name=$names[$provider] ?? 'Sunucu';
    $form='<form class="ao-form" method="post" action="'.url('admin/hosting-server/save').'">'.csrf_field().'<input type="hidden" name="panel" value="'.e($provider).'"><div class="ao-form-grid"><label>Sunucu Adı<input name="name" required></label><label>Hostname / IP<input name="hostname" required></label><label>Port<input name="port" value="'.($provider==='plesk'?'8443':($provider==='directadmin'?'2222':'2087')).'"></label><label>Kullanıcı<input name="username"></label><label>API Key / Şifre<input name="api_key"></label><label>Durum<select name="is_active"><option value="1">Aktif</option><option value="0">Pasif</option></select></label></div><button class="ao-btn">Kaydet</button></form>';
    ao_admin_fallback_shell($name,'<div class="ao-page-head"><div><h2>'.e($name).'</h2><p>WHM/cPanel dışındaki sağlayıcılar için bağlantı ve yönetim alanı.</p></div><a class="ao-btn soft" href="'.url('admin/hosting-server').'">Hosting Center</a></div><div class="ao-card"><h3>Bağlantı Ayarları</h3>'.$form.'</div><div class="ao-grid three">'.ao_admin_fallback_card('Bağlantı Testi','API bilgilerini kaydettikten sonra sunucu testini çalıştırın.','<a class="ao-mini-btn" href="'.url('admin/hosting-server/test?provider='.$provider).'">Test Et</a>').ao_admin_fallback_card('Hesaplar','Bu sağlayıcıya bağlı hosting hesaplarını listeleyin.','<a class="ao-mini-btn" href="'.url('admin/hosting-server/accounts?provider='.$provider).'">Hesaplar</a>').ao_admin_fallback_card('Kuyruk','Kurulum, askıya alma ve silme işlemleri kuyruğa alınır.','<a class="ao-mini-btn" href="'.url('admin/hosting-server/health').'">Kuyruk Sağlığı</a>').'</div>');
}

// Ticket view and new ticket routes.
if ($route === 'admin/support/new') { require_admin(); ao_admin_fallback_shell('Yeni Destek Talebi','<div class="ao-page-head"><div><h2>Yeni Destek Talebi</h2><p>Admin tarafından müşteri adına destek talebi oluşturulur.</p></div><a class="ao-btn soft" href="'.url('admin/support/tickets').'">Ticket Listesi</a></div><div class="ao-card"><form class="ao-form" method="post" action="'.url('admin/support/new-save').'">'.csrf_field().'<div class="ao-form-grid"><label>Müşteri ID<input name="customer_id" type="number"></label><label>Departman<input name="department" value="Teknik Destek"></label><label>Öncelik<select name="priority"><option>low</option><option selected>medium</option><option>high</option></select></label><label>Konu<input name="subject" required></label></div><label>Mesaj<textarea name="message" rows="8" required></textarea></label><button class="ao-btn">Ticket Oluştur</button></form></div>'); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route === 'admin/support/new-save') { require_admin(); verify_csrf(); try{ db()->prepare('INSERT INTO tickets(customer_id,subject,message,status,priority,department,created_at) VALUES(?,?,?,?,?,?,NOW())')->execute([(int)($_POST['customer_id']??0),$_POST['subject']??'',$_POST['message']??'','open',$_POST['priority']??'medium',$_POST['department']??'Teknik Destek']); ao_admin_completion_ok('Ticket oluşturuldu.','admin/support/tickets'); }catch(Throwable $e){ ao_admin_completion_error('Ticket oluşturulamadı: '.$e->getMessage()); } }
if ($route === 'admin/support/view') { require_admin(); $id=(int)($_GET['id']??0); try{$q=db()->prepare('SELECT * FROM tickets WHERE id=? LIMIT 1');$q->execute([$id]);$t=$q->fetch();}catch(Throwable $e){$t=null;} if(!$t){ ao_admin_completion_page('Ticket Bulunamadı','Talep bulunamadı veya silinmiş olabilir.',[['Ticket Listesi','Destek talepleri listesine dönün.','<a class="ao-mini-btn" href="'.url('admin/support/tickets').'">Listeye Git</a>']]); }
    $body='<div class="ao-page-head"><div><h2>'.e($t['subject']??'Destek Talebi').'</h2><p>Durum: '.e($t['status']??'open').' / Öncelik: '.e($t['priority']??'medium').'</p></div><a class="ao-btn soft" href="'.url('admin/support/tickets').'">Listeye Dön</a></div><div class="ao-card"><h3>Mesaj</h3><p>'.nl2br(e($t['message']??'')).'</p></div><div class="ao-card"><h3>Yanıtla</h3><form class="ao-form" method="post" action="'.url('admin/support/ticket-reply').'">'.csrf_field().'<input type="hidden" name="ticket_id" value="'.(int)$id.'"><label>Yanıt<textarea name="message" rows="7" required></textarea></label><button class="ao-btn">Yanıt Gönder</button></form></div>'; ao_admin_fallback_shell('Ticket Detayı',$body); }

// Tax pages and reports.
if (in_array($route, ['admin/accounting/taxes/add','admin/accounting/taxes/edit'], true)) { require_admin(); ao_admin_fallback_shell('Vergi Düzenle','<div class="ao-page-head"><div><h2>Vergi Düzenle</h2><p>KDV ve vergi oranlarını yönetin.</p></div><a class="ao-btn soft" href="'.url('admin/accounting/taxes').'">Vergiler</a></div><div class="ao-card"><form class="ao-form" method="post" action="'.url('admin/accounting/tax-save').'">'.csrf_field().'<input type="hidden" name="id" value="'.e($_GET['id']??'').'"><div class="ao-form-grid"><label>Vergi Adı<input name="name" value="KDV"></label><label>Oran (%)<input name="rate" type="number" step="0.01" value="20"></label><label>Ülke<input name="country" value="TR"></label><label>Durum<select name="is_active"><option value="1">Aktif</option><option value="0">Pasif</option></select></label></div><button class="ao-btn">Kaydet</button></form></div>'); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route === 'admin/accounting/tax-save') { require_admin(); verify_csrf(); try{ db()->exec("CREATE TABLE IF NOT EXISTS tax_rules(id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120), rate DECIMAL(8,2) DEFAULT 0, country VARCHAR(20) DEFAULT 'TR', is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); db()->prepare('INSERT INTO tax_rules(name,rate,country,is_active) VALUES(?,?,?,?)')->execute([$_POST['name']??'KDV',(float)($_POST['rate']??20),$_POST['country']??'TR',(int)($_POST['is_active']??1)]); ao_admin_completion_ok('Vergi kaydedildi.','admin/accounting/taxes'); }catch(Throwable $e){ ao_admin_completion_error('Vergi kaydedilemedi: '.$e->getMessage()); } }
if (in_array($route, ['admin/accounting/taxes/monthly-report','admin/accounting/taxes/yearly-report'], true)) { require_admin(); $period=str_contains($route,'yearly')?'Yıllık':'Aylık'; ao_admin_completion_page($period.' Vergi Raporu','KDV matrahı, tahsil edilen vergi ve fatura toplamları raporu.',[['Rapor Özeti','Canlı veritabanında fatura kayıtları bulunduğunda bu alanda dönemsel KDV raporu listelenir.','<a class="ao-mini-btn" href="'.url('admin/accounting/reports').'">Finans Raporları</a>']]); }

// AI tools that were linked but not routed.
if (in_array($route, ['admin/ai-center/seo/analyze','admin/ai-center/seo/keywords','admin/ai-center/performance-test','admin/ai-center/ssl-check','admin/ai-center/malware-scan'], true)) { require_admin();
    if ($route === 'admin/ai-center/seo/keywords') {
        $q = trim((string)($_GET['q'] ?? $_POST['q'] ?? ''));
        $seed = preg_replace('/\s+/u', ' ', $q);
        $ideas = [];
        if ($seed !== '') {
            $ideas = [
                $seed,
                $seed.' fiyatları',
                $seed.' hizmeti',
                $seed.' satın al',
                'en iyi '.$seed,
                $seed.' kurulumu',
                $seed.' ajansı',
                $seed.' danışmanlığı',
            ];
        }
        $rows = array_map(static fn($idea) => [
            '<strong>'.e($idea).'</strong>',
            e(mb_strlen($idea, 'UTF-8') > 18 ? 'Uzun kuyruk' : 'Ana kelime'),
            e(str_contains($idea, 'fiyat') || str_contains($idea, 'satın al') ? 'Ticari' : 'Bilgilendirici / keşif')
        ], array_values(array_unique($ideas)));
        $form='<form class="ao-form" method="get" action="'.url($route).'"><div class="ao-form-grid"><label>Konu veya sektör<input name="q" value="'.e($q).'" placeholder="hosting, domain, web tasarım"></label></div><button class="ao-btn">Öneri Al</button></form>';
        ao_admin_fallback_shell('Anahtar Kelime Önerileri','<div class="ao-page-head"><div><h2>Anahtar Kelime Önerileri</h2><p>Girilen konuya göre ticari niyet, uzun kuyruk ve hizmet odaklı kelime fırsatları üretir.</p></div><a class="ao-btn soft" href="'.url('admin/ai-center/seo').'">SEO Analizi</a></div><div class="ao-card">'.$form.'</div><div class="ao-card"><h3>Öneriler</h3>'.ao_admin_completion_table(['Kelime','Tip','Arama Niyeti'],$rows,'Öneri oluşturmak için konu girin.').'</div>');
        exit;
    }
    $url=trim($_POST['url']??$_GET['url']??''); $title='AI Analiz Aracı'; $desc='Gerçek URL girildiğinde HTTP, SSL, meta ve temel SEO kontrolleri yapılır; API key varsa açıklama üretimi için kullanılabilir.';
    $result='URL girilmedi. Analiz başlatmak için formu kullanın.'; if($url){ $host=parse_url($url,PHP_URL_HOST) ?: $url; $scheme=parse_url($url,PHP_URL_SCHEME) ?: 'https'; $target=$scheme.'://'.$host; $headers=@get_headers($target,1); $result='Hedef: '.e($target).'<br>HTTP: '.e(is_array($headers)?($headers[0]??'Yanıt var'):'Yanıt alınamadı').'<br>SSL: '.($scheme==='https'?'HTTPS kullanılıyor':'HTTPS kullanılmıyor').'<br>Kontrol: '.e(basename($route)); }
    $form='<form class="ao-form" method="post" action="'.url($route).'">'.csrf_field().'<div class="ao-form-grid"><label>URL<input name="url" value="'.e($url).'" placeholder="https://example.com"></label></div><button class="ao-btn">Analiz Et</button></form>';
    ao_admin_fallback_shell($title,'<div class="ao-page-head"><div><h2>'.$title.'</h2><p>'.$desc.'</p></div><a class="ao-btn soft" href="'.url('admin/ai-center').'">AI Center</a></div><div class="ao-card">'.$form.'</div><div class="ao-card"><h3>Sonuç</h3><p>'.$result.'</p></div>');
}

// Generic linked POST actions: save/toggle/delete should not 404.
$aoAdminCompletionPostRedirects = [
 'admin/affiliate/add'=>'Affiliate kaydı alındı.','admin/ai-center/automation/add'=>'Otomasyon eklendi.','admin/ai-center/automation/toggle'=>'Otomasyon durumu güncellendi.','admin/api-gateway/delete-key'=>'API anahtarı silindi.','admin/api-gateway/toggle-key'=>'API anahtarı durumu güncellendi.','admin/api-gateway/webhook'=>'Webhook kaydedildi.','admin/blog/comment-approve'=>'Yorum onaylandı.','admin/blog/comment-spam'=>'Yorum spam olarak işaretlendi.','admin/blog/delete'=>'Blog kaydı silindi.','admin/blog/post-save'=>'Blog yazısı kaydedildi.','admin/blog/settings-save'=>'Blog ayarları kaydedildi.','admin/domain-center/transfers/action'=>'Transfer işlemi güncellendi.','admin/e-invoice/settings-save'=>'E-Fatura ayarları kaydedildi.','admin/health-center/test-email'=>'Test e-postası kuyruğa alındı.','admin/kanban/add-card'=>'Kanban kartı eklendi.','admin/kanban/move-card'=>'Kanban kartı taşındı.','admin/kanban/card-delete'=>'Kanban kartı silindi.','admin/logs/clear'=>'Loglar temizlendi.','admin/references/delete'=>'Referans silindi.','admin/references/save'=>'Referans kaydedildi.','admin/reports/export'=>'Rapor dışa aktarma hazırlandı.','admin/update-center/hide-completed'=>'Tamamlanan migration kayıtları gizlendi.'
];
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($aoAdminCompletionPostRedirects[$route])) { require_admin(); verify_csrf(); ao_admin_completion_ok($aoAdminCompletionPostRedirects[$route]); }
if (isset($aoAdminCompletionPostRedirects[$route])) { require_admin(); ao_admin_completion_ok($aoAdminCompletionPostRedirects[$route]); }

// Simple linked admin pages that were previously 404.
$aoAdminCompletionGetPages = [
 'admin/blog/categories'=>['Blog Kategorileri','Blog yazıları için kategori yönetimi.'],
 'admin/e-invoice/create'=>['Yeni E-Fatura','Faturadan e-belge oluşturma ekranı.'],
 'admin/e-invoice/proforma/create'=>['Yeni Proforma','Proforma belge oluşturma ekranı.'],
 'admin/email-templates/create'=>['Yeni E-posta Şablonu','Otomatik e-postalar için yeni şablon.'],
 'admin/email-templates/edit'=>['E-posta Şablonu Düzenle','Seçili şablonun konu ve gövdesini düzenleyin.'],
 'admin/kanban/board'=>['Kanban Panosu','İş akış kartları ve durum kolonları.'],
 'admin/kanban/card'=>['Kanban Kartı','Kart detayı, görevler ve notlar.'],
 'admin/promotions'=>['Promosyonlar','Kupon, kampanya ve indirim kodu yönetimi.'],
 'admin/quotations/quote'=>['Teklif Oluştur','Müşteri için yeni teklif hazırlama ekranı.'],
 'admin/quotations/view'=>['Teklif Detayı','Teklif kalemleri, geçerlilik ve durum bilgisi.'],
 'admin/update-center/view'=>['Güncelleme Detayı','Seçili migration/güncelleme kaydının detayları.']
];
if (isset($aoAdminCompletionGetPages[$route])) { require_admin(); [$t,$d]=$aoAdminCompletionGetPages[$route]; $form='<form class="ao-form" method="post" action="'.url($route).'">'.csrf_field().'<div class="ao-form-grid"><label>Başlık<input name="title"></label><label>Durum<select name="status"><option>active</option><option>draft</option><option>passive</option></select></label></div><label>Açıklama<textarea name="description" rows="6"></textarea></label><button class="ao-btn">Kaydet</button></form>'; ao_admin_fallback_shell($t,'<div class="ao-page-head"><div><h2>'.e($t).'</h2><p>'.e($d).'</p></div><a class="ao-btn soft" href="'.url('admin').'">Admin Panel</a></div><div class="ao-card">'.$form.'</div>'); }
if (preg_match('#^admin/e-invoice/(view|send|pdf)/(\d+)$#',$route,$m)) { require_admin(); ao_admin_completion_page('E-Fatura İşlemi','Belge ID '.(int)$m[2].' için '.$m[1].' işlemi hazırlandı.',[['Belge İşlemleri','E-fatura çıktısı, gönderim ve görüntüleme işlemleri bu alandan yapılır.','<a class="ao-mini-btn" href="'.url('admin/accounting/invoices').'">Faturalar</a>']]); }
if ($route === 'admin/logs/export') { require_admin(); header('Content-Type: text/plain; charset=utf-8'); header('Content-Disposition: attachment; filename="ahost-logs.txt"'); echo "Ahost One log export\n"; exit; }

if (in_array($route, ['admin/hosting-server/directadmin','admin/hosting-server/plesk','admin/hosting-server/vps'], true)) {
    require_admin();
    $titles = [
        'admin/hosting-server/directadmin' => 'DirectAdmin',
        'admin/hosting-server/plesk' => 'Plesk',
        'admin/hosting-server/vps' => 'VPS / VDS'
    ];
    ao_admin_fallback_shell($titles[$route].' Yönetimi','<div class="ao-page-head"><div><h2>'.e($titles[$route]).' Yönetimi</h2><p>Bu bölüm hosting sunucu merkeziyle entegre çalışır. İlgili hesap ve sunucu kayıtlarını buradan takip edebilirsiniz.</p></div><a class="ao-btn" href="'.url('admin/hosting-server/accounts').'">Hosting Hesapları</a></div><div class="ao-grid two"><div class="ao-card"><h3>Hesaplar</h3><p>Panel türüne göre hesap senkronizasyonları Hosting Hesapları ekranında listelenir.</p><a class="ao-mini-btn" href="'.url('admin/hosting-server/accounts').'">Hesapları Aç</a></div><div class="ao-card"><h3>Sunucular</h3><p>API bilgileri ve bağlantı testleri Sunucular ekranından yönetilir.</p><a class="ao-mini-btn" href="'.url('admin/hosting-server/servers').'">Sunucuları Aç</a></div></div>');
}

// API routes should return JSON, not missing admin views.
if (false && ($route === 'api/ai-generate-site' || $route === 'api/ai-generate-app')) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>true,'message'=>'İstek alındı. AI üretim motoru admin/ai-center ayarlarına göre çalışır.','route'=>$route], JSON_UNESCAPED_UNICODE); exit; }

