<?php
// QA and Scan Center routes.
if (in_array($route, ['admin/qa-scan-center','admin/qa-visual-scan','admin/scan-report'], true)) {
    require_admin();
    require_once dirname(__DIR__).'/Services/QAScanCenterService.php';
    view('qa-scan-center/index', ['pageTitle'=>'QA & Scan Center Pro']); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/qa-scan-center/settings') {
    require_admin(); verify_csrf();
    foreach (($_POST['settings'] ?? []) as $k=>$v) {
        $key = preg_replace('/[^a-zA-Z0-9_\-]/','', (string)$k);
        if ($key !== '' && str_starts_with($key, 'qa_screenshot_')) {
            save_setting($key, is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : trim((string)$v));
        }
    }
    flash('success', 'QA Screenshot Bridge ayarları kaydedildi.');
    redirect_to('admin/qa-scan-center');
}
if (in_array($route, ['admin/qa-scan-center/run','admin/qa-visual-scan/run','admin/scan-report/run'], true)) {
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') verify_csrf();
    require_once dirname(__DIR__).'/Services/QAScanCenterService.php';
    $systemScan = ao_run_full_scan();
    $_SESSION['ao_last_scan'] = $systemScan;
    $base = rtrim(url(''), '/');
    try {
        $dir = QAScanCenterService::createReport($base, $systemScan);
        $summary = QAScanCenterService::readSummary($dir);
        $lines = ['Tarih: '.($summary['generated_at'] ?? date('Y-m-d H:i:s')), 'Genel Skor: '.($summary['score'] ?? 0).'/100', 'PASS: '.($summary['pass'] ?? 0), 'Warning: '.($summary['warning'] ?? 0), 'Error: '.($summary['error'] ?? 0), ''];
        foreach (($summary['routes'] ?? []) as $r) $lines[] = strtoupper($r['status']).' | '.$r['area'].' | '.$r['label'].' | '.$r['score'].'/100';
        foreach (($summary['system_rows'] ?? []) as $r) $lines[] = strtoupper($r['status'] ?? 'PASS').' | '.($r['category'] ?? 'Sistem').' | '.($r['name'] ?? '').' | '.($r['detail'] ?? '');
        file_put_contents($dir.'/report.pdf', ao_build_simple_pdf('Ahost One QA & Scan Center Pro', $lines));
        // PDF eklendikten sonra aynı klasörde ZIP paketini güncelle; ikinci/boş timestamp üretme.
        QAScanCenterService::rebuildPackage($dir);
        $real = (int)($summary['real_screenshots'] ?? 0);
        $fallback = (int)($summary['fallback_screenshots'] ?? 0);
        flash('success', 'QA & Scan tam tarama tamamlandı. Rapor paketi oluşturuldu: '.basename($dir).' — gerçek görsel: '.$real.', fallback: '.$fallback);
    } catch (Throwable $e) {
        flash('error', 'QA & Scan raporu oluşturulamadı: '.$e->getMessage());
    }
    redirect_to('admin/qa-scan-center');
}
if ($route === 'admin/qa-scan-center/download' || $route === 'admin/scan-report/pdf') {
    require_admin();
    require_once dirname(__DIR__).'/Services/QAScanCenterService.php';
    if ($route === 'admin/scan-report/pdf') { $fileType='pdf'; $reportId = (QAScanCenterService::latest()['id'] ?? ''); }
    else { $fileType = (string)($_GET['file'] ?? 'zip'); $reportId = preg_replace('~[^0-9-]~', '', (string)($_GET['report'] ?? '')); }
    $latest = QAScanCenterService::latest(); if ($reportId === '' && $latest) $reportId = $latest['id'];
    $dir = QAScanCenterService::rootDir().'/'.$reportId;
    $map = ['zip'=>['qa-scan-package.zip','application/zip'], 'html'=>['report.html','text/html; charset=utf-8'], 'pdf'=>['report.pdf','application/pdf'], 'json'=>['summary.json','application/json']];
    if (!isset($map[$fileType]) || !is_file($dir.'/'.$map[$fileType][0])) { flash('error','Rapor dosyası bulunamadı. Önce Tam Tarama Başlat.'); redirect_to('admin/qa-scan-center'); }
    [$file,$ctype] = $map[$fileType];
    header('Content-Type: '.$ctype);
    $disp = $fileType === 'html' ? 'inline' : 'attachment';
    header('Content-Disposition: '.$disp.'; filename="ahost-one-'.$reportId.'-'.$file.'"');
    readfile($dir.'/'.$file); exit;
}





