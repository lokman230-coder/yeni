<?php
// Güvenlik: Betiğe yetkisiz erişimi engellemek için anahtar
$securityKey = 'ahost2026_zip_secret'; 

if (!isset($_GET['key']) || $_GET['key'] !== $securityKey) {
    http_response_code(403);
    die('Erişim engellendi. Geçersiz güvenlik anahtarı.');
}

set_time_limit(0);
ini_set('memory_limit', '1024M');

$sourceDir = __DIR__; // Paketlenek dizin
$zipFileName = 'ahost_project_backup_' . date('Ymd_His') . '.zip';
$zipFilePath = $sourceDir . '/' . $zipFileName;

// ZipArchive Kontrolü
if (!class_exists('ZipArchive')) {
    die('Hata: PHP ZipArchive eklentisi sunucunuzda aktif değil.');
}

$zip = new ZipArchive();
if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die('Hata: ZIP dosyası oluşturulamadı.');
}

// Dizin Tarama Fonksiyonu
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $name => $file) {
    if (!$file->isDir()) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($sourceDir) + 1);

        // Oluşturulan ZIP dosyasının kendisini tekrar ZIP içine ekleme
        if ($relativePath === $zipFileName || strpos($relativePath, '.zip') !== false) {
            continue;
        }

        $zip->addFile($filePath, $relativePath);
    }
}

$zip->close();

if (file_exists($zipFilePath)) {
    echo "<h1>ZIP Paketleme Başarılı!</h1>";
    echo "<p>Dosya boyutu: " . round(filesize($zipFilePath) / 1024 / 1024, 2) . " MB</p>";
    echo "<p><a href='" . $zipFileName . "' style='padding:10px 20px;background:#0284c7;color:#fff;text-decoration:none;border-radius:5px;'>ZIP Dosyasını İndir (" . $zipFileName . ")</a></p>";
} else {
    echo "Hata: Dosya oluşturuldu ancak bulunamadı.";
}