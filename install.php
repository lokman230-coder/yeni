<?php

declare(strict_types=1);

define('AHO_START', microtime(true));
define('AHO_ROOT', __DIR__);

require AHO_ROOT . '/app/Core/bootstrap.php';

use App\Modules\Setup\Services\InstallGate;

if (InstallGate::isInstalled()) {
    header('Location: /');
    exit;
}

header('Location: /kurulum');
exit;
