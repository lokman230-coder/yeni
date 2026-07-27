<?php
/**
 * Ahost Bilişim - Front Controller
 * Tüm HTTP istekleri buraya düşer.
 * (public_html kökünde çalışacak şekilde düzenlendi — AHO_ROOT aynı dizin)
 */

declare(strict_types=1);

define('AHO_START', microtime(true));
define('AHO_ROOT', __DIR__);

require AHO_ROOT . '/app/Core/bootstrap.php';

use App\Core\Application;

$app = Application::boot(AHO_ROOT);
$app->run();
