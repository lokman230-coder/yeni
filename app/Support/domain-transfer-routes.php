<?php
if (!function_exists('ao_domain_transfer_lock_info_v2711')) {
    function ao_domain_transfer_lock_info_v2711($domain): array {
        $domain = function_exists('ahost_domain_clean') ? ahost_domain_clean($domain) : strtolower(trim((string)$domain));
        if ($domain === '' || !str_contains($domain, '.')) return ['ok'=>false,'locked'=>false,'message'=>'Ge?erli bir domain yaz?n.'];
        $statusText = '';
        try {
            $q = db()->prepare('SELECT lock_status,status FROM domains WHERE domain_name=? LIMIT 1');
            $q->execute([$domain]);
            if ($r = $q->fetch(PDO::FETCH_ASSOC)) {
                if ((int)($r['lock_status'] ?? 0) === 1) return ['ok'=>true,'locked'=>true,'source'=>'local','message'=>'Bu domain transfer kilitli g?r?n?yor. Transfer i?in mevcut panelden transfer kilidini kapat?n ve EPP/Auth kodunu al?n.'];
                $statusText = (string)($r['status'] ?? '');
            }
        } catch (Throwable $e) {}
        try { if (function_exists('ao_raw_whois')) { $raw = (string)ao_raw_whois($domain); if ($raw !== '') $statusText .= ' ' . $raw; } } catch (Throwable $e) {}
        $locked = (bool)preg_match('/(client|server)(TransferProhibited|UpdateProhibited)|transfer\s*prohibited|registrar-lock|domain\s*locked/i', $statusText);
        return ['ok'=>true,'locked'=>$locked,'source'=>$statusText !== '' ? 'whois' : 'unknown','message'=>$locked ? 'Bu domain transfer kilitli görünüyor. Transfer için mevcut firmanızdan transfer kilidini kapatın, WHOIS e-posta erişimini doğrulayın, EPP/Auth kodunu alın ve domain süresinin bitmemiş olduğundan emin olun.' : 'Transfer kilidi görünmüyor. EPP/Auth kodunuz varsa transfer sepete eklenebilir.'];
    }
}
if ($route === 'api/domain-transfer-lock') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(ao_domain_transfer_lock_info_v2711($_GET['domain'] ?? ''), JSON_UNESCAPED_UNICODE);
    exit;
}
// Frontend domain transfer request - EPP kodlu transfer sepete ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'domain-transfer/start') {
    verify_csrf();
    $domain = function_exists('ahost_domain_clean') ? ahost_domain_clean($_POST['domain'] ?? '') : strtolower(trim((string)($_POST['domain'] ?? '')));
    $epp = trim((string)($_POST['epp_code'] ?? ''));
    if (!$domain || !str_contains($domain, '.')) {
        flash('error', 'Transfer edilecek domain adını yazın.');
        redirect_to('domain-transfer');
    }
    if ($epp === '') {
        flash('error', 'Domain transferi için EPP / Transfer kodu zorunludur.');
        redirect_to('domain-transfer?domain='.rawurlencode($domain));
    }
    if (empty($_SESSION['ao_cart']) || !is_array($_SESSION['ao_cart'])) $_SESSION['ao_cart'] = [];
    $key = 'domain-transfer-'.preg_replace('/[^a-z0-9._-]/i', '-', $domain);
    $_SESSION['ao_cart'][$key] = [
        'slug' => $key,
        'name' => 'Domain Transferi: '.$domain,
        'group' => 'Domain',
        'price' => 0,
        'currency' => 'TRY',
        'cycle' => 'yearly',
        'qty' => 1,
        'domain_action' => 'transfer',
        'domain_name' => $domain,
        'epp_code' => $epp,
        'addons' => []
    ];
    flash('success', $domain.' domain transferi sepete eklendi. Şimdi uygun hosting paketini seçebilirsiniz.');
    redirect_to('hosting?from=domain-transfer');
}
if ($route === 'api/site-tool-run') {
    require_once dirname(__DIR__).'/Services/SiteToolsService.php';
    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    if ((string)admin_setting('site_tools_enabled', '1') !== '1') {
        echo json_encode(['ok'=>false,'title'=>'Site Araçları','html'=>'<div class="ao-modal-error">Site araçları şu anda yönetici tarafından kapatıldı.</div>'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $toolKey = preg_replace('/[^a-z0-9_-]/i', '', (string)($_POST['tool'] ?? $_GET['tool'] ?? ''));
    $input = trim((string)($_POST['target'] ?? $_GET['target'] ?? ''));
    try {
        $tool = function_exists('ao_site_tools_item') ? ao_site_tools_item($toolKey) : null;
        if (!$tool) throw new Exception('Araç bulunamadı.');
        $consume = function_exists('ao_site_tools_consume') ? ao_site_tools_consume($toolKey, $input) : ['ok'=>true];
        if (empty($consume['ok'])) {
            $message = $consume['message'] ?? 'Ücretsiz kullanım limiti doldu.';
            $html = '<div class="ao-tool-result-card ao-tool-limit"><div class="ao-tool-result-head"><span>Limit</span><h3>Devam etmek için müşteri paketi gerekli</h3></div><p>'.e($message).'</p><div class="ao-tool-modal-actions"><a href="'.e(url('urunler')).'">Paketleri İncele</a><button type="button" data-site-tool-close>Vazgeç</button></div></div>';
            echo json_encode(['ok'=>false,'title'=>'Kullanım Limiti','html'=>$html], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $result = ao_site_tools_run($toolKey, $input);
        echo json_encode(['ok'=>true,'title'=>$result['title'] ?? ($tool['title'] ?? 'Araç Sonucu'),'html'=>ao_site_tools_render_result_html($result)], JSON_UNESCAPED_UNICODE);
    } catch(Throwable $e) {
        echo json_encode(['ok'=>false,'title'=>'Araç Sonucu','html'=>'<div class="ao-modal-error">'.e($e->getMessage()).'</div>'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'newsletter/subscribe') {
    verify_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    try {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Geçerli bir e-posta adresi yazın.');
        db()->exec("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NOT NULL UNIQUE,
            status VARCHAR(30) DEFAULT 'active',
            source VARCHAR(80) DEFAULT 'footer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        db()->prepare("INSERT INTO newsletter_subscribers(email,status,source) VALUES(?,'active','footer') ON DUPLICATE KEY UPDATE status='active'")->execute([$email]);
        flash('success','Bülten aboneliğiniz kaydedildi.');
    } catch(Throwable $e) {
        flash('error','Bülten aboneliği kaydedilemedi: '.$e->getMessage());
    }
    redirect_to('');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'blog/comment') {
    verify_csrf();
    $postId = (int)($_POST['post_id'] ?? 0);
    $name = trim((string)($_POST['author_name'] ?? ''));
    $email = trim((string)($_POST['author_email'] ?? ''));
    $content = trim((string)($_POST['content'] ?? ''));
    try {
        if ($postId <= 0 || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $content === '') throw new Exception('Yorum alanları eksik.');
        db()->exec("CREATE TABLE IF NOT EXISTS blog_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            author_name VARCHAR(160) NOT NULL,
            author_email VARCHAR(190) NOT NULL,
            content TEXT NOT NULL,
            status VARCHAR(30) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY post_id(post_id),
            KEY status(status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $q = db()->prepare('SELECT slug FROM blog_posts WHERE id=? LIMIT 1'); $q->execute([$postId]); $slug = (string)$q->fetchColumn();
        if ($slug === '') throw new Exception('Blog yazısı bulunamadı.');
        db()->prepare("INSERT INTO blog_comments(post_id,author_name,author_email,content,status) VALUES(?,?,?,?, 'pending')")->execute([$postId,$name,$email,$content]);
        flash('success','Yorumunuz onay için alındı.');
        redirect_to('blog/'.$slug);
    } catch(Throwable $e) {
        flash('error','Yorum gönderilemedi: '.$e->getMessage());
        redirect_to('blog');
    }
}
if (in_array($route, ['bilgi-bankasi/ask','knowledgebase/ask','knowledge-base/ask'], true)) {
    if (!function_exists('ao_kb_answer_question')) {
        require_once __DIR__ . '/local-ai-knowledge-routes.php';
    }
    $question = trim((string)($_GET['q'] ?? $_POST['q'] ?? ''));
    $result = null;
    if ($question !== '' && function_exists('ao_kb_answer_question')) {
        $result = ao_kb_answer_question($question);
    }
    site_view('knowledge-base/ask', ['pageTitle'=>'Bilgi Bankası Asistanı','question'=>$question,'result'=>$result]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'bilgi-bankasi/feedback') {
    header('Content-Type: application/json; charset=utf-8');
    if (!function_exists('ao_ai_ensure_schema')) { echo json_encode(['ok'=>false,'message'=>'Geri bildirim sistemi hazır değil.'], JSON_UNESCAPED_UNICODE); exit; }
    ao_ai_ensure_schema();
    $id = (int)($_POST['id'] ?? 0);
    $value = trim((string)($_POST['value'] ?? ''));
    if (!$id || !in_array($value, ['yes','no'], true)) { echo json_encode(['ok'=>false,'message'=>'Eksik geri bildirim.'], JSON_UNESCAPED_UNICODE); exit; }
    try {
        db()->prepare("UPDATE knowledge_research_queue SET feedback_value=?, feedback_at=NOW() WHERE id=?")->execute([$value,$id]);
        echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
    } catch(Throwable $e) {
        echo json_encode(['ok'=>false,'message'=>'Geri bildirim kaydedilemedi.'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if ($route === 'campaigns') {
    redirect_to('hosting');
}
if ($route === 'seo') {
    redirect_to('urun-grubu/seo');
}
if ($route === 'whois') {
    redirect_to('domain?tool=whois');
}

$siteMap = ['' => 'home/index','hakkimizda'=>'about/index','about'=>'about/index','kurumsal'=>'about/index','corporate'=>'about/index','misyon'=>'about/mission','misyonumuz'=>'about/mission','vizyon'=>'about/vision','vizyonumuz'=>'about/vision','gizlilik-politikasi'=>'legal/privacy-policy','kvkk'=>'legal/privacy-policy','kullanim-sartlari'=>'legal/terms','sartlar'=>'legal/terms','hizmet-politikasi'=>'legal/service-policy','iade-sartlari'=>'legal/refund-policy','iade-politikasi'=>'legal/refund-policy','iletisim'=>'contact/index','contact'=>'contact/index','support'=>'contact/index','destek'=>'contact/index','cart'=>'cart/index','checkout'=>'cart/index','blog'=>'blog/index','domain' => 'domain/index','domain-transfer'=>'domain/transfer','toplu-domain'=>'domain/bulk','toplu-domain-sorgulama'=>'domain/bulk','site-araclari'=>'tools/index','site-tools'=>'tools/index','hosting' => 'products/hosting','vps' => 'products/vps','web-tasarim' => 'products/web-design','web-design'=>'products/web-design','sitebuilder'=>'products/sitebuilder','mobilebuilder'=>'products/mobilebuilder','mobil-uygulama'=>'products/mobile-app','dijital-hizmetler'=>'products/digital-services','marketplace'=>'marketplace/index','referanslar'=>'references/index','references'=>'references/index','bilgi-bankasi'=>'knowledge-base/index','knowledge-base'=>'knowledge-base/index','knowledgebase'=>'knowledge-base/index','urunler'=>'products/index','products'=>'products/index','seo-analyzer'=>'seo-analyzer/index','domain-checker'=>'domain-checker/index','teklif'=>'quotation','mobilebuilder/download'=>'mobilebuilder-download','duyurular'=>'announcements/index'];
if (in_array($route, ['site-araclari','site-tools'], true) && (string)admin_setting('site_tools_enabled', '1') !== '1') {
    http_response_code(404);
    site_view('errors/404', ['pageTitle'=>'Sayfa bulunamadı']);
    exit;
}
if ($route === 'blog') {
    site_view('blog/index', ['pageTitle'=>'Blog']);
    exit;
}

