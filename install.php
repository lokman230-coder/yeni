<?php
/**
 * Ahost Bilişim - Kurulum Giriş Noktası (alias)
 *
 * index.php ile birebir aynı ön denetleyiciyi (front controller) çalıştırır.
 * Kurulum henüz tamamlanmadıysa SetupMiddleware, /kurulum, /assets, /themes
 * dışındaki tüm istekleri zaten otomatik olarak /kurulum sihirbazına
 * yönlendirir — bu dosya sadece alışılmış "install.php" adresini de
 * çalışır hale getirmek için bir takma ad (alias) görevi görür.
 */

declare(strict_types=1);

define('AHO_START', microtime(true));
define('AHO_ROOT', __DIR__);

require AHO_ROOT . '/app/Core/bootstrap.php';

use App\Core\Application;
use App\Modules\Setup\Services\InstallGate;

// Kurulum zaten tamamlanmışsa install.php'de dolaşmaya gerek yok.
if (InstallGate::isInstalled()) {
    header('Location: /');
    exit;
}

$app = Application::boot(AHO_ROOT);
$app->run();
