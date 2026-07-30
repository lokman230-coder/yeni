<?php
// v24.11.6 Public Builder Access + gated export/build
function ao_builder_gate_page($kind='sitebuilder', $format='ZIP') {
    $title = $kind === 'mobilebuilder' ? 'Mobile Builder çıktı oluşturma' : 'Site Builder çıktı oluşturma';
    $productRoute = $kind === 'mobilebuilder' ? 'mobilebuilder' : 'sitebuilder';
    $packageRoute = $kind === 'mobilebuilder' ? 'urunler?group=mobilebuilder' : 'urunler?group=sitebuilder';
    site_view('builders/gate', [
        'pageTitle'=>$title,
        'kind'=>$kind,
        'format'=>$format,
        'productRoute'=>$productRoute,
        'packageRoute'=>$packageRoute
    ]);
    exit;
}
if ($route === 'sitebuilder/preview-public') {
    $isAiBuilderPreview = trim((string)($_GET['ai_prompt'] ?? '')) !== '' || (string)($_GET['ai_builder'] ?? '') === '1';
    if ($isAiBuilderPreview && function_exists('ao_builder_trial_available') && !ao_builder_trial_available('sitebuilder')) {
        ao_builder_trial_block('sitebuilder', 'sitebuilder/create-demo');
    }
    $template = preg_replace('/[^a-z0-9_-]/','', (string)($_GET['template'] ?? 'hosting'));
    if ($isAiBuilderPreview && function_exists('ao_builder_trial_mark')) ao_builder_trial_mark('sitebuilder');
    site_view('builders/sitebuilder-preview', ['pageTitle'=>'Site Builder Önizleme','template'=>$template]);
    exit;
}
if ($route === 'mobilebuilder/preview-public') {
    $isAiBuilderPreview = trim((string)($_GET['ai_prompt'] ?? '')) !== '' || (string)($_GET['ai_builder'] ?? '') === '1';
    if ($isAiBuilderPreview && function_exists('ao_builder_trial_available') && !ao_builder_trial_available('mobilebuilder')) {
        ao_builder_trial_block('mobilebuilder', 'mobilebuilder/create-demo');
    }
    $template = preg_replace('/[^a-z0-9_-]/','', (string)($_GET['template'] ?? 'business'));
    if ($isAiBuilderPreview && function_exists('ao_builder_trial_mark')) ao_builder_trial_mark('mobilebuilder');
    // Radio template shows special radio builder
    if ($template === 'radio') {
        site_view('builders/mobilebuilder-radio-demo', ['pageTitle'=>'Radyo Uygulaması Oluştur']);
    } else {
        site_view('builders/mobilebuilder-preview', ['pageTitle'=>'Mobile Builder Önizleme','template'=>$template]);
    }
    exit;
}
if ($route === 'sitebuilder/create-demo') {
    site_view('builders/sitebuilder-demo', ['pageTitle'=>'Site Builder Oluştur']);
    exit;
}
if ($route === 'mobilebuilder/create-demo') {
    // Check if template is radio
    $template = preg_replace('/[^a-z0-9_-]/','', (string)($_GET['template'] ?? ''));
    if ($template === 'radio') {
        site_view('builders/mobilebuilder-radio-demo', ['pageTitle'=>'Radyo Uygulaması Oluştur']);
    } else {
        site_view('builders/mobilebuilder-demo', ['pageTitle'=>'Mobile Builder Oluştur']);
    }
    exit;
}
if ($route === 'mobilebuilder/radio') {
    site_view('builders/mobilebuilder-radio-demo', ['pageTitle'=>'Radyo Uygulaması Oluştur']);
    exit;
}
// Ziyaretçi önizleme yapabilir; ZIP/APK/AAB/PWA/Android export için üyelik + paket şartı gösterilir.
if (in_array($route, ['sitebuilder/export','sitebuilder/download','sitebuilder/zip','mobilebuilder/export','mobilebuilder/build','mobilebuilder/apk','mobilebuilder/aab','mobilebuilder/zip'], true)) {
    $kind = str_starts_with($route, 'mobilebuilder') ? 'mobilebuilder' : 'sitebuilder';
    $format = 'ZIP';
    if (str_contains($route, 'apk')) $format = 'APK';
    elseif (str_contains($route, 'aab')) $format = 'AAB';
    elseif (str_contains($route, 'build')) $format = 'APK/AAB';
    ao_builder_gate_page($kind, $format);
}

if ($route === 'admin/site-tools/design') {
    require_admin();
    require_once dirname(__DIR__).'/Services/SiteToolsService.php';
    render_view('admin', 'site-tools/design', ['pageTitle'=>'Site Araçları Görünüm']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/site-tools/design-save') {
    require_admin();
    verify_csrf();
    require_once dirname(__DIR__).'/Services/SiteToolsService.php';
    $settings = $_POST['settings'] ?? [];
    if (!is_array($settings)) $settings = [];
    foreach (ao_site_tools_catalog() as $tool) {
        foreach (['site_tool_title_', 'site_tool_card_bg_', 'site_tool_card_color_'] as $prefix) {
            $key = $prefix.$tool['key'];
            save_setting($key, trim((string)($settings[$key] ?? '')));
        }
    }
    foreach (['site_tools_enabled','site_tools_guest_ip_limit'] as $key) {
        if (isset($settings[$key])) save_setting($key, trim((string)$settings[$key]));
    }
    flash('success','Site araçları görünüm ayarları kaydedildi.');
    redirect_to('admin/site-tools/design');
}


if ($_SERVER['REQUEST_METHOD']==='POST' && $route === 'contact/send') {
    verify_csrf();
    $name=trim((string)($_POST['name'] ?? '')); $email=trim((string)($_POST['email'] ?? '')); $subject=trim((string)($_POST['subject'] ?? '')); $message=trim((string)($_POST['message'] ?? ''));
    try{
        if($name==='' || $email==='' || $subject==='' || $message==='') throw new Exception('Lütfen tüm alanları doldurun.');
        db()->exec('CREATE TABLE IF NOT EXISTS contact_messages (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160) NOT NULL, email VARCHAR(190) NOT NULL, subject VARCHAR(190) NOT NULL, message TEXT NOT NULL, ip_address VARCHAR(80) NULL, status VARCHAR(40) DEFAULT "new", created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        db()->prepare('INSERT INTO contact_messages(name,email,subject,message,ip_address) VALUES(?,?,?,?,?)')->execute([$name,$email,$subject,$message,$_SERVER['REMOTE_ADDR'] ?? '']);
        try{ ao_send_email_notification(admin_setting('contact_form_target_email', admin_setting('company_email','')), 'İletişim Formu: '.$subject, $message, 'contact_form'); }catch(Throwable $x){}
        flash('success','Mesajınız alındı. En kısa sürede dönüş yapılacaktır.');
    }catch(Throwable $e){ flash('error','Mesaj gönderilemedi: '.$e->getMessage()); }
    redirect_to('iletisim');
}

