<?php

/**
 * Aktif modüller listesi.
 * Boş liste → sadece is_core=true olan modüller yüklenir.
 * Slug'lar burada listelenirse o modüller de yüklenir.
 */
return [
    'active' => [
        // Faz 1-2 (aktif)
        'header',
        'footer',
        'home',
        'admin',
        'pages',
        'blog',
        'announcements',
        'knowledge',
        'contact',
        'references',
        'cookie',
        'product',
        'customer',
        'theme',

        // Faz 3+
        'cart',
        'checkout',
        'payment',
        'invoice',

        // Faz 4+
        'registrar',
        'domain',
        'hosting',
        'sitetools',
        'health',

        // Faz 5+
        'builder',
        'ai',
        'ticket',
        'marketplace',
        'btk',

        // Faz 6b+
        'referral',
        'setup',
        'import',
        'license',

        // Faz 4+
        // 'domain', 'registrar', 'hosting', 'server', 'sitetools',

        // Faz 5+
        // 'ticket', 'sitebuilder', 'mobilebuilder', 'ai',
        // 'marketplace', 'cookieanalytics', 'btk',
    ],
];
