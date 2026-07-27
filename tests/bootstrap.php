<?php

declare(strict_types=1);

define('AHO_START', microtime(true));
define('AHO_ROOT', dirname(__DIR__));
define('AHO_TESTING', true);

require AHO_ROOT . '/vendor/autoload.php';

use App\Core\Config;
use App\Core\Env;

Env::load(AHO_ROOT . '/.env');
Config::load(AHO_ROOT . '/config');
