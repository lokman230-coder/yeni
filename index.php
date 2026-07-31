<?php
/**
 * Ahost Bilişim - Front Controller
 * Tüm HTTP istekleri buraya düşer.
 * Hangi klasöre kurulursa kurulsun (kök veya alt klasör) çalışır:
 * AHO_BASE_PATH, sistemin kendi app_base_path() tespitini kullanır.
 */

declare(strict_types=1);

define('AHO_START', microtime(true));
define('AHO_ROOT', __DIR__);

require AHO_ROOT . '/app/Core/bootstrap.php';

if (!defined('AHO_BASE_PATH')) {
    define('AHO_BASE_PATH', function_exists('app_base_path') ? app_base_path() : '');
}
if (!isset($_SERVER['AHOST_ROUTE_RESOLVED']) && function_exists('ao_request_path_no_base')) {
    $_SERVER['AHOST_ROUTE_RESOLVED'] = trim(ao_request_path_no_base(), '/');
}

use App\Core\Application;

$app = Application::boot(AHO_ROOT);
$app->run();
