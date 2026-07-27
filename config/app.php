<?php

use App\Middleware\AdminAuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\CurrencyMiddleware;
use App\Middleware\CustomerAuthMiddleware;
use App\Middleware\LocaleMiddleware;
use App\Middleware\MaintenanceMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\ReferralCaptureMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use App\Middleware\SetupMiddleware;

return [
    'name'     => env('APP_NAME', 'Ahost Bilişim'),
    'env'      => env('APP_ENV', 'local'),
    'debug'    => env('APP_DEBUG', false),
    'url'      => env('APP_URL', 'http://localhost:8000'),
    'key'      => env('APP_KEY', ''),
    'locale'   => env('DEFAULT_LOCALE', 'tr'),
    'currency' => env('DEFAULT_CURRENCY', 'TRY'),
    'theme'    => 'default',
    'timezone' => 'Europe/Istanbul',

    // Middleware alias'ları — route dosyalarında 'csrf' gibi kısa ad kullanılabilir
    'middleware_aliases' => [
        'csrf'         => CsrfMiddleware::class,
        'security'     => SecurityHeadersMiddleware::class,
        'locale'       => LocaleMiddleware::class,
        'currency'     => CurrencyMiddleware::class,
        'ratelimit'    => RateLimitMiddleware::class,
        'admin.auth'   => AdminAuthMiddleware::class,
        'customer.auth'=> CustomerAuthMiddleware::class,
    ],

    // Her istekte otomatik uygulanacak global middleware (sırayla)
    'global_middleware' => [
        SetupMiddleware::class,           // 1. Kurulum yapılmadıysa /kurulum'a yönlendir
        MaintenanceMiddleware::class,     // 2. Bakım modu → public site kapalı
        SecurityHeadersMiddleware::class, // 3. Güvenlik başlıkları
        ReferralCaptureMiddleware::class, // 4. ?ref=CODE cookie yakalama
    ],
];
