<?php
// v24.6.5 customer notification sync routes
if ($route === 'client/notifications') {
    require_customer();
    $c = current_customer();
    $search = trim((string)($_GET['search'] ?? ''));

    // get customer notifications (panel notifications)
    $rows = ao_customer_notifications((int)($c['id'] ?? 0), true, 200);
    if ($search !== '') {
        $s = mb_strtolower($search);
        $rows = array_values(array_filter($rows, function($r) use($s){
            $hay = mb_strtolower(($r['title'] ?? '').' '.($r['message'] ?? ''));
            return $hay !== '' && str_contains($hay, $s) || $s === '' ? true : false;
        }));
    }

    // collect message logs (email/sms/whatsapp)
    $emailLogs = $smsLogs = $whatsappLogs = [];
    try {
        $email = trim((string)($c['email'] ?? ''));
        $phone = preg_replace('/\D/', '', trim((string)($c['phone'] ?? '')));
        try{ db()->exec('CREATE TABLE IF NOT EXISTS customer_message_visibility (id BIGINT AUTO_INCREMENT PRIMARY KEY, customer_id INT NOT NULL, channel_type VARCHAR(30) NOT NULL, log_id BIGINT NOT NULL, hidden_at DATETIME NULL, read_at DATETIME NULL, UNIQUE KEY uniq_customer_message(customer_id,channel_type,log_id), KEY customer_id(customer_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        $sql = 'SELECT nl.* FROM notification_logs nl LEFT JOIN customer_message_visibility cmv ON cmv.log_id=nl.id AND cmv.channel_type=nl.channel_type AND cmv.customer_id='.(int)$c['id'].' WHERE nl.channel_type IN ("mail","sms","whatsapp") AND cmv.hidden_at IS NULL';
        $params = [];
        if ($email !== '' && $phone !== '') {
            $sql .= ' AND (nl.recipient=? OR nl.recipient=?)';
            $params = [$email, $phone];
        } elseif ($email !== '') {
            $sql .= ' AND nl.recipient=?';
            $params = [$email];
        } elseif ($phone !== '') {
            $sql .= ' AND nl.recipient=?';
            $params = [$phone];
        } else {
            $sql .= ' AND 1=0';
        }
        $sql .= ' ORDER BY id DESC LIMIT 200';
        if ($params) {
            $q = db()->prepare($sql);
            $q->execute($params);
            $messageLogs = $q->fetchAll() ?: [];
        } else {
            $messageLogs = [];
        }
        // split by channel and optionally filter by search
        foreach($messageLogs as $m){
            $hay = mb_strtolower(($m['subject'] ?? '').' '.($m['message'] ?? '').' '.($m['recipient'] ?? ''));
            if ($search !== '' && !str_contains($hay, mb_strtolower($search))) continue;
            $ch = strtolower((string)($m['channel_type'] ?? ''));
            if ($ch === 'mail' || $ch === 'email') $emailLogs[] = $m;
            elseif ($ch === 'sms') $smsLogs[] = $m;
            elseif ($ch === 'whatsapp') $whatsappLogs[] = $m;
        }
    } catch(Throwable $e) {
        $emailLogs = $smsLogs = $whatsappLogs = [];
    }

    // announcements (active, in-range) for panel
    $announcements = [];
    try {
        $sql = 'SELECT * FROM announcements WHERE is_active=1 AND (starts_at IS NULL OR starts_at<=NOW()) AND (ends_at IS NULL OR ends_at>=NOW()) AND (target IN ("all","customers") OR target LIKE ?) ORDER BY id DESC LIMIT 50';
        $q = db()->prepare($sql);
        $q->execute(['%customer%']);
        $announcements = $q->fetchAll() ?: [];
        if ($search !== '') {
            $s = mb_strtolower($search);
            $announcements = array_values(array_filter($announcements, function($a) use($s){
                $hay = mb_strtolower(($a['title'] ?? '').' '.($a['body'] ?? ''));
                return str_contains($hay, $s);
            }));
        }
    } catch(Throwable $e) {
        $announcements = [];
    }

    customer_view('notifications/index', ['pageTitle'=>'Bildirimlerim','notifications'=>$rows,'emailLogs'=>$emailLogs,'smsLogs'=>$smsLogs,'whatsappLogs'=>$whatsappLogs,'announcements'=>$announcements,'search'=>$search]);
    exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route === 'client/notifications/read') {
    require_customer(); verify_csrf(); $c=current_customer();
    ao_customer_notification_mark_read((int)($c['id'] ?? 0),(int)($_POST['id'] ?? 0));
    redirect_to('client/notifications'.ao_tab_hash('notifications'));
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route === 'client/notifications/read-all') {
    require_customer(); verify_csrf(); $c=current_customer();
    ao_customer_notification_mark_all_read((int)($c['id'] ?? 0));
    redirect_to('client/notifications'.ao_tab_hash('notifications'));
}

// AJAX: toggle read/unread
if ($_SERVER['REQUEST_METHOD']==='POST' && $route === 'client/notifications/toggle-read') {
    require_customer(); verify_csrf(); header('Content-Type: application/json'); $c=current_customer();
    $id = (int)($_POST['id'] ?? 0); if(!$id){ echo json_encode(['ok'=>false,'message'=>'Geçersiz id']); exit; }
    try {
        $row = db()->prepare('SELECT read_at FROM customer_notifications WHERE id=? AND customer_id=? LIMIT 1'); $row->execute([$id,(int)$c['id']]); $r=$row->fetch();
        if(!$r) { echo json_encode(['ok'=>false,'message'=>'Bildirim bulunamadı']); exit; }
        if (empty($r['read_at'])) { $ok = ao_customer_notification_mark_read((int)$c['id'],$id); }
        else { $ok = ao_customer_notification_mark_unread((int)$c['id'],$id); }
        echo json_encode(['ok'=> (bool)$ok, 'read'=> empty($r['read_at']) ? true : false]); exit;
    } catch(Throwable $e) { echo json_encode(['ok'=>false,'message'=>'Sunucu hatası']); exit; }
}

// AJAX: toggle pin
if ($_SERVER['REQUEST_METHOD']==='POST' && $route === 'client/notifications/toggle-pin') {
    require_customer(); verify_csrf(); header('Content-Type: application/json'); $c=current_customer();
    $id = (int)($_POST['id'] ?? 0); if(!$id){ echo json_encode(['ok'=>false,'message'=>'Geçersiz id']); exit; }
    try {
        $row = db()->prepare('SELECT pinned FROM customer_notifications WHERE id=? AND customer_id=? LIMIT 1'); $row->execute([$id,(int)$c['id']]); $r=$row->fetch();
        if(!$r) { echo json_encode(['ok'=>false,'message'=>'Bildirim bulunamadı']); exit; }
        $new = empty($r['pinned']) ? 1 : 0;
        $ok = ao_customer_notification_set_pinned((int)$c['id'],$id,(bool)$new);
        echo json_encode(['ok'=>(bool)$ok,'pinned'=> (bool)$new]); exit;
    } catch(Throwable $e) { echo json_encode(['ok'=>false,'message'=>'Sunucu hatası']); exit; }
}

// AJAX: toggle hide (customer-side soft-delete)
if ($_SERVER['REQUEST_METHOD']==='POST' && $route === 'client/notifications/toggle-hide') {
    require_customer(); verify_csrf(); header('Content-Type: application/json'); $c=current_customer();
    $id = (int)($_POST['id'] ?? 0); if(!$id){ echo json_encode(['ok'=>false,'message'=>'Geçersiz id']); exit; }
    try {
        $row = db()->prepare('SELECT hidden FROM customer_notifications WHERE id=? AND customer_id=? LIMIT 1'); $row->execute([$id,(int)$c['id']]); $r=$row->fetch();
        if(!$r) { echo json_encode(['ok'=>false,'message'=>'Bildirim bulunamadı']); exit; }
        $new = empty($r['hidden']) ? 1 : 0;
        $ok = false; try { $ok = db()->prepare('UPDATE customer_notifications SET hidden=? WHERE id=? AND customer_id=?')->execute([$new,$id,(int)$c['id']]); } catch(Throwable $e) { $ok = false; }
        echo json_encode(['ok'=> (bool)$ok, 'hidden'=> (bool)$new]); exit;
    } catch(Throwable $e) { echo json_encode(['ok'=>false,'message'=>'Sunucu hatası']); exit; }
}

// AJAX: hide customer message log from customer panel only; admin records remain.
if ($_SERVER['REQUEST_METHOD']==='POST' && $route === 'client/notifications/hide-message') {
    require_customer(); verify_csrf(); header('Content-Type: application/json'); $c=current_customer();
    $id=(int)($_POST['id'] ?? 0); $type=preg_replace('/[^a-z]/','', (string)($_POST['type'] ?? ''));
    if(!$id || !in_array($type,['mail','email','sms','whatsapp'],true)){ echo json_encode(['ok'=>false,'message'=>'Geçersiz kayıt']); exit; }
    $type = $type==='email' ? 'mail' : $type;
    try{
        db()->exec('CREATE TABLE IF NOT EXISTS customer_message_visibility (id BIGINT AUTO_INCREMENT PRIMARY KEY, customer_id INT NOT NULL, channel_type VARCHAR(30) NOT NULL, log_id BIGINT NOT NULL, hidden_at DATETIME NULL, read_at DATETIME NULL, UNIQUE KEY uniq_customer_message(customer_id,channel_type,log_id), KEY customer_id(customer_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        db()->prepare('INSERT INTO customer_message_visibility(customer_id,channel_type,log_id,hidden_at) VALUES(?,?,?,NOW()) ON DUPLICATE KEY UPDATE hidden_at=NOW()')->execute([(int)$c['id'],$type,$id]);
        echo json_encode(['ok'=>true]); exit;
    }catch(Throwable $e){ echo json_encode(['ok'=>false,'message'=>'Sunucu hatası']); exit; }
}

// AJAX: mark a customer message log as read for this customer only.
if ($_SERVER['REQUEST_METHOD']==='POST' && $route === 'client/notifications/read-message') {
    require_customer(); verify_csrf(); header('Content-Type: application/json'); $c=current_customer();
    $id=(int)($_POST['id'] ?? 0); $type=preg_replace('/[^a-z]/','', (string)($_POST['type'] ?? ''));
    $type = $type==='email' ? 'mail' : $type;
    if(!$id || !in_array($type,['mail','sms','whatsapp'],true)){ echo json_encode(['ok'=>false,'message'=>'Geçersiz kayıt']); exit; }
    try{
        db()->exec('CREATE TABLE IF NOT EXISTS customer_message_visibility (id BIGINT AUTO_INCREMENT PRIMARY KEY, customer_id INT NOT NULL, channel_type VARCHAR(30) NOT NULL, log_id BIGINT NOT NULL, hidden_at DATETIME NULL, read_at DATETIME NULL, UNIQUE KEY uniq_customer_message(customer_id,channel_type,log_id), KEY customer_id(customer_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        db()->prepare('INSERT INTO customer_message_visibility(customer_id,channel_type,log_id,read_at) VALUES(?,?,?,NOW()) ON DUPLICATE KEY UPDATE read_at=NOW()')->execute([(int)$c['id'],$type,$id]);
        echo json_encode(['ok'=>true]); exit;
    }catch(Throwable $e){ echo json_encode(['ok'=>false,'message'=>'Sunucu hatası']); exit; }
}

// AJAX: load paginated items for notifications and message logs
if ($route === 'client/notifications/load') {
    require_customer(); header('Content-Type: application/json');
    $c = current_customer(); $type = trim((string)($_GET['type'] ?? 'notifications'));
    $page = max(1,(int)($_GET['page']??1)); $per = max(10,min(100,(int)($_GET['per']??20)));
    $out = ['ok'=>true,'items'=>[],'next_page'=>null];
    try {
        if ($type === 'notifications') {
            $offset = ($page-1)*$per;
            $q = db()->prepare('SELECT * FROM customer_notifications WHERE customer_id=? AND (hidden IS NULL OR hidden=0) ORDER BY id DESC LIMIT ? OFFSET ?');
            $q->execute([(int)$c['id'],$per,$offset]); $rows = $q->fetchAll() ?: [];
            $out['items'] = $rows; if(count($rows)==$per) $out['next_page']=$page+1;
        } elseif (in_array($type,['email','sms','whatsapp'],true)) {
            $channel = $type==='email' ? 'mail' : $type;
            $offset = ($page-1)*$per;
            $sql = 'SELECT * FROM notification_logs WHERE channel_type=? AND (customer_id=? OR recipient=? OR recipient=?) ORDER BY id DESC LIMIT ? OFFSET ?';
            $q = db()->prepare($sql);
            $q->execute([$channel,(int)$c['id'], $c['email'] ?? '', preg_replace('/\D/','',($c['phone']??'')) , $per, $offset]);
            $rows = $q->fetchAll() ?: [];
            $out['items'] = $rows; if(count($rows)==$per) $out['next_page']=$page+1;
        } elseif ($type === 'announcements') {
            $offset = ($page-1)*$per;
            $q = db()->prepare('SELECT * FROM announcements WHERE is_active=1 AND (starts_at IS NULL OR starts_at<=NOW()) AND (ends_at IS NULL OR ends_at>=NOW()) ORDER BY id DESC LIMIT ? OFFSET ?');
            $q->execute([$per,$offset]); $rows = $q->fetchAll() ?: [];
            $out['items']=$rows; if(count($rows)==$per) $out['next_page']=$page+1;
        } else { $out=['ok'=>false,'message'=>'Invalid type']; }
    } catch(Throwable $e) { $out=['ok'=>false,'message'=>'Server error']; }
    echo json_encode($out);
    exit;
}


if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='client/services/password-update') {
    require_customer(); verify_csrf();
    $c=current_customer(); $serviceId=(int)($_POST['service_id']??0); $pass=trim((string)($_POST['panel_password']??''));
    try{
        if(!$serviceId) throw new Exception('Hizmet seçimi zorunlu.');
        if($pass==='') $pass=ao_random_hosting_password();
        $q=db()->prepare('SELECT h.*, s.customer_id FROM hosting_accounts h LEFT JOIN services s ON s.id=h.service_id WHERE h.service_id=? AND s.customer_id=? LIMIT 1');
        $q->execute([$serviceId,(int)$c['id']]); $h=$q->fetch();
        if(!$h) throw new Exception('Hosting hesabı bulunamadı.');
        $sync=ao_hosting_panel_change_password($h,$pass);
        if(empty($sync['ok'])) throw new Exception($sync['message'] ?? 'Sunucu şifre değişikliğini kabul etmedi.');
        db()->prepare('UPDATE hosting_accounts SET panel_password=? WHERE service_id=?')->execute([$pass,$serviceId]);
        ao_hosting_log((int)$h['id'],$serviceId,'customer.password.changed','Müşteri panelinden şifre değiştirildi ve sunucu senkronu çalıştı.','***','***');
        flash('success','Hosting şifresi güncellendi.');
    }catch(Throwable $e){ flash('error','�?ifre güncellenemedi: '.$e->getMessage()); }
    redirect_to('client/services/view?id='.$serviceId);
}


if ($route==='admin/site-slider') { require_admin(); ao_prism_slider_ensure_schema(); view('site-slider/index', ['pageTitle'=>'Slider Yönetimi']); exit; }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/site-slider/settings') { require_admin(); verify_csrf(); save_setting('site_slider_enabled', (($_POST['enabled'] ?? '1') === '1') ? '1':'0'); flash('success','Slider durumu güncellendi.'); redirect_to('admin/site-slider'); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/site-slider/save') { require_admin(); verify_csrf(); ao_prism_slider_ensure_schema();
    $id=(int)($_POST['id'] ?? 0);
    $title=trim((string)($_POST['title'] ?? ''));
    if($title===''){ flash('error','Slider başlığı zorunlu.'); redirect_to('admin/site-slider'); }

    try {
        $image=trim((string)($_POST['image_url'] ?? ''));
        $video=trim((string)($_POST['video_url'] ?? ''));
        $bgImage=trim((string)($_POST['background_image_url'] ?? ''));
        $bgVideo=trim((string)($_POST['background_video_url'] ?? ''));

        $up=ao_prism_slider_file_upload('image_file','image'); if($up) $image=$up;
        $up=ao_prism_slider_file_upload('video_file','video'); if($up) $video=$up;
        $up=ao_prism_slider_file_upload('background_image_file','image'); if($up) $bgImage=$up;
        $up=ao_prism_slider_file_upload('background_video_file','video'); if($up) $bgVideo=$up;

        $mediaType=trim((string)($_POST['media_type'] ?? 'image'));
        if(!in_array($mediaType,['image','video','abstract'],true)) $mediaType='image';

        $data=[
            trim((string)($_POST['kicker'] ?? '')),
            $title,
            trim((string)($_POST['description'] ?? '')),
            $image,
            $mediaType,
            $video,
            $bgImage,
            $bgVideo,
            trim((string)($_POST['background_color'] ?? '')),
            trim((string)($_POST['text_color'] ?? '')),
            trim((string)($_POST['accent_color'] ?? '')),
            trim((string)($_POST['max_width'] ?? '')),
            trim((string)($_POST['height_value'] ?? '')),
            trim((string)($_POST['padding_value'] ?? '')),
            trim((string)($_POST['title_size'] ?? '')),
            trim((string)($_POST['title_weight'] ?? '')),
            trim((string)($_POST['body_size'] ?? '')),
            trim((string)($_POST['radius_value'] ?? '')),
            trim((string)($_POST['align_value'] ?? '')),
            trim((string)($_POST['button_text'] ?? '')),
            trim((string)($_POST['button_url'] ?? '')),
            (int)($_POST['sort_order'] ?? 10),
            (int)(($_POST['is_active'] ?? '1') === '1'),
            ao_prism_slider_datetime($_POST['starts_at'] ?? ''),
            ao_prism_slider_datetime($_POST['ends_at'] ?? '')
        ];

        if($id>0){
            db()->prepare('UPDATE site_sliders SET kicker=?, title=?, description=?, image_url=?, media_type=?, video_url=?, background_image_url=?, background_video_url=?, background_color=?, text_color=?, accent_color=?, max_width=?, height_value=?, padding_value=?, title_size=?, title_weight=?, body_size=?, radius_value=?, align_value=?, button_text=?, button_url=?, sort_order=?, is_active=?, starts_at=?, ends_at=? WHERE id=?')->execute([...$data,$id]);
            flash('success','Slider güncellendi.');
        } else {
            db()->prepare('INSERT INTO site_sliders(kicker,title,description,image_url,media_type,video_url,background_image_url,background_video_url,background_color,text_color,accent_color,max_width,height_value,padding_value,title_size,title_weight,body_size,radius_value,align_value,button_text,button_url,sort_order,is_active,starts_at,ends_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($data);
            flash('success','Slider eklendi.');
        }
    } catch(Throwable $e){
        flash('error','Slider kaydedilemedi: '.$e->getMessage());
    }
    redirect_to('admin/site-slider');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/site-slider/delete') { require_admin(); verify_csrf(); ao_prism_slider_ensure_schema(); $id=(int)($_POST['id'] ?? 0); if($id>0){ try{ db()->prepare('DELETE FROM site_sliders WHERE id=?')->execute([$id]); flash('success','Slider silindi.'); }catch(Throwable $e){ flash('error','Slider silinemedi: '.$e->getMessage()); } } redirect_to('admin/site-slider'); }

$customerMap = ['client' => 'dashboard/index','client/dashboard'=>'dashboard/index','client/notifications'=>'notifications/index','client/services' => 'services/index','client/services/view' => 'services/view','client/credit' => 'credit/index','client/domains' => 'domains/index','client/domains/view' => 'domains/view','client/invoices' => 'invoices/index','client/invoices/view'=>'invoices/view','client/support' => 'support/index','client/assistant'=>'assistant','client/security'=>'security/index','client/profile' => 'profile/index','client/contact'=>'contact/index','client/account-users'=>'account-users/index','client/billable'=>'billable/index','client/quotes'=>'quotes/index','client/accounting-history'=>'accounting-history/index','client/emails'=>'emails/index','client/seo-tools'=>'seo-tools/index','client/theme'=>'theme','client/builder'=>'builder','client/modules'=>'modules','client/site-builder'=>'site-builder','client/mobile-builder'=>'mobile-builder'];
$authMap = ['client/login'=>'login','client/register'=>'register','client/forgot-password'=>'forgot-password','client/reset-password'=>'reset-password','admin/login'=>'admin-login','admin/forgot-password'=>'admin-forgot-password','admin/security-question'=>'admin-security-question','admin/reset-password'=>'admin-reset-password'];
if (isset($authMap[$route])) {
    auth_view($authMap[$route], ['pageTitle' => $route === 'client/login' ? 'Müşteri Girişi' : 'Giriş']);
    exit;
}
if (isset($customerMap[$route])) {
    require_customer();
    client_view($customerMap[$route], ['pageTitle' => 'Müşteri Paneli']);
    exit;
}
if (preg_match('#^blog/([^/]+)$#', $route, $m)) {
    $blogMeta=['pageTitle'=>'Ahost One Blog', 'slug'=>$m[1]];
    try {
        $st=db()->prepare("SELECT p.*, c.name AS category_name FROM blog_posts p LEFT JOIN blog_categories c ON c.id=p.category_id WHERE p.slug=? AND p.status='published' LIMIT 1");
        $st->execute([$m[1]]);
        $bp=$st->fetch() ?: null;
        if($bp){
            $desc=trim((string)($bp['meta_description'] ?? '')) ?: trim((string)($bp['excerpt'] ?? '')) ?: mb_substr(strip_tags((string)($bp['content'] ?? '')),0,160,'UTF-8');
            $articleSchema=[
                '@context'=>'https://schema.org',
                '@type'=>'Article',
                'headline'=>(string)($bp['title'] ?? ''),
                'description'=>mb_substr(strip_tags($desc),0,160,'UTF-8'),
                'url'=>url('blog/'.$bp['slug']),
                'datePublished'=>date('c', strtotime($bp['published_at'] ?? $bp['created_at'] ?? 'now')),
                'dateModified'=>date('c', strtotime($bp['updated_at'] ?? $bp['published_at'] ?? $bp['created_at'] ?? 'now')),
                'author'=>['@type'=>'Organization','name'=>(string)admin_setting('site_name','Ahost One')]
            ];
            if(!empty($bp['featured_image'])) $articleSchema['image']=$bp['featured_image'];
            $blogMeta += [
                'pageTitle'=>(string)($bp['title'] ?? 'Ahost One Blog'),
                'metaTitle'=>trim((string)($bp['meta_title'] ?? '')) ?: (($bp['title'] ?? 'Blog').' - Ahost One Blog'),
                'metaDescription'=>mb_substr(strip_tags($desc),0,160,'UTF-8'),
                'metaKeywords'=>(string)($bp['meta_keywords'] ?? ''),
                'ogImage'=>(string)($bp['featured_image'] ?? ''),
                'canonicalUrl'=>url('blog/'.$bp['slug']),
                'schemaJsonLd'=>json_encode($articleSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ];
        }
    } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    site_view('blog/post', $blogMeta);
    exit;
}
if (preg_match('#^duyurular/(\d+)$#', $route, $m)) {
    $announcementId = (int)$m[1];
    try {
        $q = db()->prepare('SELECT * FROM announcements WHERE id=? AND is_active=1 AND (starts_at IS NULL OR starts_at<=NOW()) AND (ends_at IS NULL OR ends_at>=NOW()) AND channel IN ("site","all") LIMIT 1');
        $q->execute([$announcementId]);
        $announcement = $q->fetch() ?: null;
    } catch(Throwable $e) {
        $announcement = null;
    }
    if (!$announcement) {
        http_response_code(404);
        site_view('errors/404', ['pageTitle'=>'Duyuru bulunamadı']);
        exit;
    }
    site_view('announcements/detail', ['pageTitle'=>$announcement['title'] ?: 'Duyuru', 'announcement'=>$announcement]);
    exit;
}
if ($route === 'duyurular') {
    site_view('announcements/index', ['pageTitle'=>'Duyurular']);
    exit;
}
if (preg_match('#^bilgi-bankasi/([a-z0-9\-_]+)$#', $route, $m) || preg_match('#^knowledge-base/([a-z0-9\-_]+)$#', $route, $m)) {
    if (in_array($m[1], ['ask', 'feedback'], true)) {
        return;
    }
    try {
        if (function_exists('ao_v23_ensure_schema')) ao_v23_ensure_schema();
        $q = db()->prepare("SELECT * FROM knowledge_articles WHERE slug=? AND audience='customer' AND status='published' LIMIT 1");
        $q->execute([$m[1]]);
        $article = $q->fetch() ?: null;
    } catch(Throwable $e) {
        $article = null;
    }
    if (!$article) {
        http_response_code(404);
        site_view('errors/404', ['pageTitle'=>'Makale bulunamadı']);
        exit;
    }
    require_once dirname(__DIR__) . '/Views/site/shared/content-renderer.php';
    $body = trim((string)($article['content'] ?? ''));
    $excerpt = trim((string)($article['meta_description'] ?? '')) ?: trim((string)($article['excerpt'] ?? '')) ?: ao_site_content_excerpt($body, 155);
    ob_start();
    echo '<article class="ao-content-panel">';
    if (!empty($article['category'])) echo '<div class="ao-content-meta"><strong>'.e($article['category']).'</strong><span>•</span><span>Bilgi Bankası</span></div>';
    echo $body !== '' ? $body : '<p>'.e($excerpt).'</p>';
    echo '</article>';
    $content = ob_get_clean();
    ao_site_content_page([
        'content' => $content,
        'heroTitle' => (string)($article['title'] ?? 'Bilgi Bankası'),
        'kicker' => 'Bilgi Bankası',
        'summary' => $excerpt,
        'breadcrumbs' => [
            ['label'=>'Ana Sayfa','href'=>url('')],
            ['label'=>'Bilgi Bankası','href'=>url('bilgi-bankasi')],
            ['label'=>(string)($article['title'] ?? 'Makale')],
        ],
    ]);
    exit;
}
if ($route === 'admin/settings/site-features' || $route === 'admin/site-features') {
    require_admin();
    render_view('admin', 'settings/site-features', ['pageTitle' => 'Site Özellikleri']);
    exit;
}
if (preg_match('#^admin/settings/([a-z0-9_-]+)$#', $route, $m)) {
    require_admin();
    if (($m[1] ?? '') === 'inline') { redirect_to('admin/settings'); }
    view('settings/section', ['pageTitle'=>'Ayarlar Merkezi', 'section'=>$m[1]]);
    exit;
}
