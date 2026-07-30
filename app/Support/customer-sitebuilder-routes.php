<?php
// v25.0.0 RC20: Müşteri SiteBuilder kaydetme/oluşturma akışları admin rotasından ayrıldı.
// Böylece merkezi admin guard müşteri builder kaydetme işlemini kırmaz; tüm işlemler customer_id sahiplik kontrolüyle çalışır.
if (!function_exists('ao_builder_kind_package_url')) {
    function ao_builder_kind_package_url(string $kind): string {
        return $kind === 'mobilebuilder' ? 'urun-grubu/mobilebuilder' : 'urun-grubu/sitebuilder';
    }

    function ao_customer_has_builder_package(string $kind, int $customerId = 0): bool {
        if ($customerId <= 0 && function_exists('current_customer')) {
            $customer = current_customer();
            $customerId = (int)($customer['id'] ?? 0);
        }
        if ($customerId <= 0) return false;
        try {
            $q = db()->prepare("SELECT COUNT(*) FROM services s LEFT JOIN products p ON p.id=s.product_id LEFT JOIN product_groups g ON g.id=p.group_id WHERE s.customer_id=? AND LOWER(s.status) IN('active','aktif','pending','beklemede') AND (LOWER(p.type)=? OR LOWER(p.slug) LIKE ? OR LOWER(g.slug)=? OR LOWER(g.type)=?)");
            $q->execute([$customerId, $kind, '%'.$kind.'%', $kind, $kind]);
            return (int)$q->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    function ao_builder_trial_session_key(string $kind): string {
        $customerId = 0;
        if (function_exists('current_customer')) {
            try { $customerId = (int)((current_customer() ?: [])['id'] ?? 0); } catch (Throwable $e) { $customerId = 0; }
        }
        return ($customerId > 0 ? 'customer_'.$customerId : 'guest').'_'.($kind === 'mobilebuilder' ? 'mobilebuilder' : 'sitebuilder');
    }

    function ao_builder_trial_available(string $kind): bool {
        if (ao_customer_has_builder_package($kind)) return true;
        $key = ao_builder_trial_session_key($kind);
        return empty($_SESSION['ao_builder_trial_used'][$key]);
    }

    function ao_builder_trial_mark(string $kind): void {
        if (ao_customer_has_builder_package($kind)) return;
        $key = ao_builder_trial_session_key($kind);
        if (!isset($_SESSION['ao_builder_trial_used']) || !is_array($_SESSION['ao_builder_trial_used'])) {
            $_SESSION['ao_builder_trial_used'] = [];
        }
        $_SESSION['ao_builder_trial_used'][$key] = date('c');
    }

    function ao_builder_trial_block(string $kind, string $returnRoute = ''): void {
        site_view('builders/gate', [
            'pageTitle'=>'Builder AI Paket Gerekli',
            'kind'=>$kind,
            'format'=>'AI',
            'gateMode'=>'ai_trial',
            'packageRoute'=>ao_builder_kind_package_url($kind),
            'continueRoute'=>$returnRoute ?: ($kind === 'mobilebuilder' ? 'mobilebuilder/create-demo' : 'sitebuilder/create-demo'),
        ]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='client/site-builder/project-create') {
    require_customer(); verify_csrf(); ao_schema_ensure_v1400();
    $customer = current_customer(); $customerId = (int)($customer['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? '')) ?: 'Web Sitem';
    $aiPrompt = trim((string)($_POST['ai_prompt'] ?? ''));
    if ($aiPrompt !== '' && !ao_builder_trial_available('sitebuilder')) {
        ao_builder_trial_block('sitebuilder', 'client/site-builder');
    }
    $aiProvider = preg_replace('/[^a-z0-9_\-]/i', '', (string)($_POST['ai_provider'] ?? ''));
    $aiEnabled = function_exists('admin_setting') ? admin_setting('sitebuilder_ai_edit', '1') !== '0' : true;
    $aiUsed = false;
    $json = json_encode([['id'=>'hero','type'=>'hero','cols'=>1,'content'=>[
        ['id'=>'h1','type'=>'heading','text'=>$name,'props'=>[]],
        ['id'=>'p1','type'=>'text','text'=>$aiPrompt !== '' ? mb_substr($aiPrompt, 0, 240, 'UTF-8') : 'Ahost One SiteBuilder ile hazırlanan yeni sayfanız.','props'=>[]],
        ['id'=>'b1','type'=>'button','text'=>'Hemen Başla','props'=>[]]
    ]]], JSON_UNESCAPED_UNICODE);
    if ($aiEnabled && $aiPrompt !== '' && function_exists('ao_ai_call_optional')) {
        $prompt = "Türkçe SiteBuilder için sadece geçerli JSON array döndür. Markdown yazma. Şema: her bölüm id,type,cols,content içerir; content içinde heading,text,button öğeleri id,type,text,props alanlarıyla olur. Site adı: {$name}. Müşteri isteği: {$aiPrompt}. En fazla 4 bölüm üret.";
        $ai = ao_ai_call_optional($prompt, $aiProvider);
        if (is_string($ai) && trim($ai) !== '') {
            $clean = trim(preg_replace('/```(?:json)?|```/i', '', $ai));
            $decoded = json_decode($clean, true);
            if (is_array($decoded) && $decoded) {
                $json = json_encode($decoded, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                $aiUsed = true;
            } else {
                $json = json_encode([['id'=>'hero','type'=>'hero','cols'=>1,'content'=>[
                    ['id'=>'h1','type'=>'heading','text'=>$name,'props'=>[]],
                    ['id'=>'p1','type'=>'text','text'=>mb_substr(strip_tags($ai), 0, 900, 'UTF-8'),'props'=>[]],
                    ['id'=>'b1','type'=>'button','text'=>'Teklif Al','props'=>[]]
                ]]], JSON_UNESCAPED_UNICODE);
                $aiUsed = true;
            }
        }
    }
    try{
        db()->prepare('INSERT INTO sitebuilder_projects(customer_id,name,type,theme_slug,status) VALUES(?,?,"site","default","active")')->execute([$customerId,$name]);
        $pid=(int)db()->lastInsertId();
        db()->prepare('INSERT INTO sitebuilder_pages(project_id,title,slug,page_type,builder_json,status) VALUES(?,?,?,?,?,"draft")')->execute([$pid,'Ana Sayfa','index','home',$json]);
        $pageId=(int)db()->lastInsertId();
        if ($aiPrompt !== '') ao_builder_trial_mark('sitebuilder');
        flash('success',$aiPrompt !== '' ? ($aiUsed ? 'Site Builder projeniz AI taslağıyla oluşturuldu.' : 'Site Builder projeniz güvenli taslakla oluşturuldu; seçilen AI sağlayıcısından cevap alınamadı.') : 'Site Builder projeniz oluşturuldu.');
        redirect_to('client/site-builder?project_id='.$pid.'&page_id='.$pageId);
    }catch(Throwable $e){ flash('error','Proje oluşturulamadı: '.$e->getMessage()); redirect_to('client/site-builder'); }
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='client/site-builder/page-create') {
    require_customer(); verify_csrf(); ao_schema_ensure_v1400();
    $customer = current_customer(); $customerId = (int)($customer['id'] ?? 0);
    $pid=(int)($_POST['project_id']??0); $title=trim((string)($_POST['title']??'Yeni Sayfa')) ?: 'Yeni Sayfa';
    $slug=trim((string)($_POST['slug']??'')) ?: strtolower(trim(preg_replace('/[^a-z0-9]+/i','-', $title), '-'));
    if ($slug==='') $slug='sayfa-'.time();
    $json=json_encode([['id'=>'sec1','type'=>'section','cols'=>1,'content'=>[['id'=>'h1','type'=>'heading','text'=>$title,'props'=>[]]]]], JSON_UNESCAPED_UNICODE);
    try{
        $q=db()->prepare('SELECT id FROM sitebuilder_projects WHERE id=? AND customer_id=? LIMIT 1'); $q->execute([$pid,$customerId]);
        if(!$q->fetchColumn()) throw new Exception('Bu projeye erişim yetkiniz yok.');
        db()->prepare('INSERT INTO sitebuilder_pages(project_id,title,slug,builder_json,status) VALUES(?,?,?,?,"draft")')->execute([$pid,$title,$slug,$json]);
        $pageId=(int)db()->lastInsertId();
        flash('success','Sayfa oluşturuldu.');
        redirect_to('client/site-builder?project_id='.$pid.'&page_id='.$pageId);
    }catch(Throwable $e){ flash('error','Sayfa oluşturulamadı: '.$e->getMessage()); redirect_to('client/site-builder?project_id='.$pid); }
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='client/site-builder/page-save') {
    require_customer(); verify_csrf(); ao_schema_ensure_v1400();
    $customer = current_customer(); $customerId = (int)($customer['id'] ?? 0);
    $id=(int)($_POST['id']??0); $json=$_POST['builder_json']??'[]'; $html=ao_sitebuilder_render_html($json);
    try{
        $q=db()->prepare('SELECT p.id, p.project_id FROM sitebuilder_pages p INNER JOIN sitebuilder_projects sp ON sp.id=p.project_id WHERE p.id=? AND sp.customer_id=? LIMIT 1');
        $q->execute([$id,$customerId]); $page=$q->fetch();
        if(!$page) throw new Exception('Bu sayfaya erişim yetkiniz yok.');
        db()->prepare('UPDATE sitebuilder_pages SET builder_json=?, html_cache=?, status="published" WHERE id=?')->execute([$json,$html,$id]);
        db()->prepare('INSERT INTO sitebuilder_revisions(page_id,builder_json,created_by) VALUES(?,?,?)')->execute([$id,$json,0]);
        flash('success','Sayfa kaydedildi.');
        redirect_to('client/site-builder?project_id='.(int)$page['project_id'].'&page_id='.$id);
    }catch(Throwable $e){ flash('error','Kaydedilemedi: '.$e->getMessage()); redirect_to('client/site-builder'); }
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='client/site-builder/export') {
    require_customer(); verify_csrf(); ao_schema_ensure_v1400();
    $customer = current_customer(); $customerId = (int)($customer['id'] ?? 0); $pid=(int)($_POST['project_id']??0);
    try{
        $q=db()->prepare('SELECT id FROM sitebuilder_projects WHERE id=? AND customer_id=? LIMIT 1'); $q->execute([$pid,$customerId]);
        if(!$q->fetchColumn()) throw new Exception('Bu projeye erişim yetkiniz yok.');
        $file=ao_sitebuilder_export_project($pid);
        flash('success','ZIP export hazırlandı: '.basename($file));
    }catch(Throwable $e){ flash('error','Export başarısız: '.$e->getMessage()); }
    redirect_to('client/site-builder?project_id='.$pid);
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/site-builder/export') { require_admin(); verify_csrf(); try{ $file=ao_sitebuilder_export_project((int)($_POST['project_id']??1)); flash('success','ZIP export hazırlandı: '.basename($file)); }catch(Throwable $e){ flash('error','Export başarısız: '.$e->getMessage()); } redirect_to('admin/site-builder/exports'); }
if ($route==='admin/site-builder/export-download') { require_admin(); $id=(int)($_GET['id']??0); $q=db()->prepare('SELECT * FROM sitebuilder_exports WHERE id=? LIMIT 1'); $q->execute([$id]); $ex=$q->fetch(); if(!$ex||!is_file($ex['file_path'])){ http_response_code(404); echo 'Export bulunamadı'; exit; } header('Content-Type: application/zip'); header('Content-Disposition: attachment; filename="'.basename($ex['file_path']).'"'); readfile($ex['file_path']); exit; }
if ($route==='sitebuilder/preview') { $id=(int)($_GET['id']??0); $q=db()->prepare('SELECT * FROM sitebuilder_pages WHERE id=? LIMIT 1'); $q->execute([$id]); $p=$q->fetch(); if(!$p){ http_response_code(404); echo 'Sayfa bulunamadı'; exit; } echo '<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($p['title']).'</title><style>body{font-family:Arial,sans-serif;background:#f8fafc;color:#0f172a;margin:0}.sbx-section{padding:60px 8%;background:white;margin:18px;border-radius:22px;box-shadow:0 12px 30px rgba(15,23,42,.08)}.sbx-row{display:grid;gap:20px}.sbx-cols-1{grid-template-columns:1fr}.sbx-cols-2{grid-template-columns:1fr 1fr}.sbx-cols-3{grid-template-columns:repeat(3,1fr)}.sbx-cols-4{grid-template-columns:repeat(4,1fr)}.sbx-btn{display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:12px 18px;border-radius:12px}@media(max-width:760px){.sbx-row{grid-template-columns:1fr!important}}</style></head><body>'.ao_sitebuilder_render_html($p['builder_json']).'</body></html>'; exit; }
