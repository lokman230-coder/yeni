<?php
// v16.0.0 MobileBuilder Pro
function ao_schema_ensure_v1600() { static $done=false; if($done) return; $done=true;
    try {
        db()->exec("CREATE TABLE IF NOT EXISTS mobile_apps (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NULL, app_name VARCHAR(190) NOT NULL, sector VARCHAR(100) NULL, platform VARCHAR(40) DEFAULT 'pwa', primary_color VARCHAR(20) DEFAULT '#2563eb', status VARCHAR(40) DEFAULT 'draft', ai_prompt TEXT NULL, config_json LONGTEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        db()->exec("CREATE TABLE IF NOT EXISTS mobile_app_pages (id INT AUTO_INCREMENT PRIMARY KEY, app_id INT NOT NULL, page_name VARCHAR(190) NOT NULL, page_type VARCHAR(80) DEFAULT 'screen', sort_order INT DEFAULT 0, layout_json LONGTEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        db()->exec("CREATE TABLE IF NOT EXISTS mobile_app_exports (id INT AUTO_INCREMENT PRIMARY KEY, app_id INT NULL, export_type VARCHAR(40) NOT NULL, status VARCHAR(40) DEFAULT 'ready', file_path VARCHAR(255) NULL, notes TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        db()->exec("CREATE TABLE IF NOT EXISTS mobile_build_queue (id INT AUTO_INCREMENT PRIMARY KEY, app_id INT NULL, build_type VARCHAR(40) NOT NULL, status VARCHAR(40) DEFAULT 'pending', log_text LONGTEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, finished_at TIMESTAMP NULL DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) { error_log('v16 schema: '.$e->getMessage()); }
}
function ao_mobile_export_zip($type='pwa') {
    $dir = __DIR__.'/storage/exports/mobilebuilder'; if(!is_dir($dir)) mkdir($dir,0775,true);
    $file = $dir.'/ahost-mobile-'.$type.'-'.date('Ymd-His').'.zip';
    $zip = new ZipArchive();
    if ($zip->open($file, ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true) throw new Exception('ZIP oluşturulamadı');
    $zip->addFromString('README.md', "Ahost One MobileBuilder Export\nType: {$type}\nGenerated: ".date('c')."\n");
    if($type==='pwa'){
        $zip->addFromString('index.html','<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ahost Mobile</title><link rel="manifest" href="manifest.json"></head><body><h1>Ahost Mobile PWA</h1><p>MobileBuilder export.</p></body></html>');
        $zip->addFromString('manifest.json', json_encode(['name'=>'Ahost Mobile','short_name'=>'Ahost','start_url'=>'./index.html','display'=>'standalone'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        $zip->addFromString('sw.js','self.addEventListener("install",e=>self.skipWaiting());');
    } elseif($type==='flutter'){
        $zip->addFromString('pubspec.yaml', "name: ahost_mobile\ndescription: Ahost One MobileBuilder export\nversion: 1.0.0\n");
        $zip->addFromString('lib/main.dart', "void main(){ print('Ahost MobileBuilder Flutter export'); }\n");
    } else {
        $zip->addFromString('settings.gradle', "pluginManagement { repositories { google(); mavenCentral(); gradlePluginPortal() } }\ndependencyResolutionManagement { repositoriesMode.set(RepositoriesMode.FAIL_ON_PROJECT_REPOS); repositories{ google(); mavenCentral() } }\nrootProject.name='AhostMobile'\n");
        $zip->addFromString('README_ANDROID.md', 'Android Studio proje iskeleti. Gradle/SDK ile derlenmelidir.');
    }
    $zip->close();
    return $file;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/mobile-builder/app-save') { require_admin(); verify_csrf(); ao_schema_ensure_v1600();
    db()->prepare('INSERT INTO mobile_apps(app_name,sector,platform,primary_color,ai_prompt,config_json,status,updated_at) VALUES(?,?,?,?,?,?,"draft",NOW())')->execute([trim($_POST['app_name']??'Ahost Mobil'),trim($_POST['sector']??'Genel'),trim($_POST['platform']??'pwa'),trim($_POST['primary_color']??'#2563eb'),trim($_POST['ai_prompt']??''),json_encode($_POST,JSON_UNESCAPED_UNICODE)]);
    flash('success','Mobil uygulama taslağı kaydedildi.'); redirect_to('admin/mobile-builder/editor');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/mobile-builder/ai-generate') { require_admin(); verify_csrf(); ao_schema_ensure_v1600();
    $prompt=trim($_POST['prompt']??'AI mobil uygulama');
    db()->prepare('INSERT INTO mobile_apps(app_name,sector,platform,primary_color,ai_prompt,config_json,status) VALUES(?,?,?,?,?,?,"ai_draft")')->execute([mb_substr($prompt,0,80),'AI Tasarım','pwa','#2563eb',$prompt,json_encode(['pages'=>['Ana Sayfa','Hizmetler','Bildirimler','Profil']],JSON_UNESCAPED_UNICODE)]);
    flash('success','AI mobil tasarım taslağı oluşturuldu. Editörde manuel düzenleyebilirsin.'); redirect_to('admin/mobile-builder/editor');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='client/mobile-builder/ai-create') {
    require_customer(); verify_csrf(); ao_schema_ensure_v1600();
    $customer = current_customer(); $customerId = (int)($customer['id'] ?? 0);
    if (function_exists('ao_builder_trial_available') && !ao_builder_trial_available('mobilebuilder')) {
        ao_builder_trial_block('mobilebuilder', 'client/mobile-builder');
    }
    $appName = trim((string)($_POST['app_name'] ?? 'Mobil Uygulamam')) ?: 'Mobil Uygulamam';
    $sector = trim((string)($_POST['sector'] ?? 'Genel')) ?: 'Genel';
    $platform = trim((string)($_POST['platform'] ?? 'pwa')) ?: 'pwa';
    $provider = preg_replace('/[^a-z0-9_\-]/i', '', (string)($_POST['ai_provider'] ?? ''));
    $prompt = trim((string)($_POST['ai_prompt'] ?? ''));
    $aiEnabled = function_exists('admin_setting') ? admin_setting('mobilebuilder_ai_edit', '1') !== '0' : true;
    $spec = [
        'name'=>$appName,
        'sector'=>$sector,
        'platform'=>$platform,
        'screens'=>[
            ['name'=>'Ana Sayfa','description'=>'Marka, hızlı erişimler ve duyurular'],
            ['name'=>'Hizmetler','description'=>'Ürün veya hizmet listesi'],
            ['name'=>'İletişim','description'=>'WhatsApp, telefon ve form bağlantıları'],
        ],
        'features'=>array_values(array_filter(array_map('trim', explode(',', $prompt ?: 'Bildirimler, İletişim, Profil')))),
        'theme'=>['primary'=>'#2563eb','secondary'=>'#06b6d4'],
    ];
    $aiUsed = false;
    if ($aiEnabled && $prompt !== '' && function_exists('ao_ai_call_optional')) {
        $ai = ao_ai_call_optional("Türkçe MobileBuilder için sadece geçerli JSON object döndür. Markdown yazma. Alanlar: name, sector, platform, screens, navigation, features, theme. Uygulama adı: {$appName}. Sektör: {$sector}. Platform: {$platform}. İstek: {$prompt}.", $provider);
        if (is_string($ai) && trim($ai) !== '') {
            $clean = trim(preg_replace('/```(?:json)?|```/i', '', $ai));
            $decoded = json_decode($clean, true);
            if (is_array($decoded) && $decoded) {
                $spec = $decoded;
                $aiUsed = true;
            } else {
                $spec['ai_notes'] = mb_substr(strip_tags($ai), 0, 1200, 'UTF-8');
                $aiUsed = true;
            }
        }
    }
    try {
        db()->prepare('INSERT INTO mobile_apps(customer_id,app_name,sector,platform,primary_color,ai_prompt,config_json,status,updated_at) VALUES(?,?,?,?,?,?,?,"ai_draft",NOW())')->execute([$customerId,$appName,$sector,$platform,(string)($spec['theme']['primary'] ?? '#2563eb'),$prompt,json_encode($spec,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
        if (function_exists('ao_builder_trial_mark')) ao_builder_trial_mark('mobilebuilder');
        flash('success',$aiUsed ? 'MobileBuilder AI taslağı oluşturuldu.' : 'MobileBuilder güvenli taslağı oluşturuldu; seçilen AI sağlayıcısından cevap alınamadı.');
    } catch (Throwable $e) {
        flash('error','Mobil uygulama taslağı oluşturulamadı: '.$e->getMessage());
    }
    redirect_to('client/mobile-builder');
}
if ($route==='admin/mobile-builder/export') { require_admin(); ao_schema_ensure_v1600();
    $type = isset($_GET['flutter']) ? 'flutter' : (isset($_GET['android']) ? 'android' : 'pwa');
    try { $file=ao_mobile_export_zip($type); db()->prepare('INSERT INTO mobile_app_exports(export_type,status,file_path,notes) VALUES(?,"ready",?,?)')->execute([$type,$file,'Otomatik export']); header('Content-Type: application/zip'); header('Content-Disposition: attachment; filename="'.basename($file).'"'); readfile($file); exit; }
    catch(Throwable $e){ flash('error','Export hatası: '.$e->getMessage()); redirect_to('admin/mobile-builder/exports'); }
}
