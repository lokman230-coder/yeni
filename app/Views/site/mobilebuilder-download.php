<?php
/**
 * MobileBuilder APK/AAB Download Handler
 * Ahost One v25.0.0 RC20
 * 
 * Güvenli dosya indirme - doğrudan public erişim yerine
 * Controller üzerinden indirme sağlar
 */

// Build ID kontrolü
$buildId = (int)($_GET['id'] ?? 0);

if ($buildId <= 0) {
    http_response_code(400);
    die('Geçersiz build ID');
}

// Build service yükle
$buildServicePath = dirname(__DIR__, 4) . '/modules/builder/mobilebuilder/Services/MobileBuildService.php';

if (!file_exists($buildServicePath)) {
    http_response_code(500);
    die('Build servisi bulunamadı');
}

require_once $buildServicePath;
$buildService = new \Ahost\Modules\Builder\MobileBuilder\MobileBuildService();

// Yetki kontrolü - kullanıcı kendi build'ini mi indiriyor?
$customerId = (int)($_SESSION['customer_id'] ?? 0);
$userId = $customerId;
$isAdmin = !empty($_SESSION['admin_id']);

if (!$isAdmin && $customerId <= 0) {
    http_response_code(401);
    die('Bu işlem için giriş yapmalısınız');
}

// Admin veya kullanıcı kendi build'ini indirebilir
if (!$buildService->canDownload($buildId, $userId)) {
    // Proje bilgilerini al ve yetki kontrolü yap
    $stmt = db()->prepare("
        SELECT b.*, COALESCE(p.customer_id, p.user_id) AS owner_id 
        FROM module_mobilebuilder_builds b
        LEFT JOIN module_mobilebuilder_projects p ON b.project_id = p.id
        WHERE b.id = ?
    ");
    $stmt->execute([$buildId]);
    $build = $stmt->fetch();
    
    if (!$build) {
        http_response_code(404);
        die('Build bulunamadı');
    }
    
    if (!$isAdmin && (int)$build['owner_id'] !== $customerId) {
        http_response_code(403);
        die('Bu build\'i indirme yetkiniz yok');
    }
    
    if ($build['status'] !== 'completed') {
        http_response_code(400);
        die('Build henüz tamamlanmadı');
    }
}

// Dosyayı indir
if (!$buildService->downloadBuild($buildId)) {
    http_response_code(404);
    die('Dosya bulunamadı veya indirilemiyor');
}

