<?php

return [
    'cookie_name'     => 'aho_session',
    'lifetime'        => (int) env('SESSION_LIFETIME', 7200),
    'cookie_secure'   => (bool) env('SESSION_SECURE', false),
    'cookie_samesite' => env('SESSION_SAMESITE', 'Lax'),
    'driver'          => env('SESSION_DRIVER', 'file'),
];
