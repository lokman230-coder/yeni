<?php

use App\Core\Router;
use App\Modules\Admin\Controllers\AuthController;
use App\Modules\Admin\Controllers\DashboardController;
use App\Modules\Admin\Controllers\MenuController;
use App\Modules\Admin\Controllers\ModuleController;
use App\Modules\Admin\Controllers\ThemeBlocksController;
use App\Modules\Admin\Controllers\FinanceController;
use App\Modules\Admin\Controllers\CookieAnalyticsController;
use App\Modules\Admin\Controllers\AdminBuilderController;
use App\Modules\Admin\Controllers\SiteToolsController;
use App\Modules\Admin\Controllers\MobileBuildAdminController;

/** @var Router $router */

// Auth
$router->group(['prefix' => 'admin', 'middleware' => ['locale', 'ratelimit']], function (Router $router) {
    $router->get('/giris', [AuthController::class, 'showLogin'])->name('admin.login');
    $router->post('/giris', [AuthController::class, 'login'])->middleware(['csrf']);
    $router->get('/2fa', [AuthController::class, 'show2fa'])->name('admin.2fa');
    $router->post('/2fa', [AuthController::class, 'verify2fa'])->middleware(['csrf']);
    $router->post('/cikis', [AuthController::class, 'logout'])->middleware(['csrf', 'admin.auth'])->name('admin.logout');
});

// Korumalı admin sayfaları
$router->group(['prefix' => 'admin', 'middleware' => ['locale', 'admin.auth']], function (Router $router) {
    $router->get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    $router->get('', [DashboardController::class, 'index']);

    // 22 admin menüsü — hepsi stub controller ile açılır
    // NOT: 'urun-merkezi' Product modülünde gerçek CRUD ile karşılanıyor.
    $menus = [
        'musteriler'         => 'Müşteriler',
        'siparisler'         => 'Siparişler',
        // 'urun-merkezi'    → Product/AdminProductController
        // 'destek-merkezi'  → Ticket modülü
        // 'btk-raporu'      → Btk modülü
        'domain-center'      => 'Domain Center',
        'hosting-sunucu'     => 'Hosting & Sunucu',
        'finans'             => 'Finans',
        'destek-merkezi'     => 'Destek Merkezi',
        'sayfalar'           => 'Sayfalar',
        'blog'               => 'Blog',
        'duyurular'          => 'Duyurular',
        'tema-bloklari'      => 'Site Tema Blokları',
        'menu-yonetimi'      => 'Menü Yönetimi',
        'site-builder'       => 'Site Builder',
        'mobile-builder'     => 'Mobile Builder',
        'site-araclari'      => 'Site Araçları',
        'marketplace'        => 'Marketplace',
        'ayarlar'            => 'Ayarlar',
        'modul-merkezi'      => 'Modül Merkezi',
        'ai-center'          => 'AI Center',
        'ai-asistan'         => 'AI Asistan',
        'cerez-analizi'      => 'Çerez Analizi',
        'qa-scan-center'     => 'QA Scan Center',
        'loglar'             => 'Loglar',
        'cache-center'       => 'Cache Center',
        // 'health-center' → Health modülü
        // 'qa-scan-center'  → Health modülü
        // 'site-araclari'   → SiteTools modülü (admin görünümü Faz 5)
        // 'domain-center'   → Domain admin (Faz 5)
        // 'hosting-sunucu'  → Hosting admin (Faz 5)
    ];
    // Yukarıdaki listedeki bazı slug'lar başka modüller tarafından karşılanıyor.
    // Onları burada override etmeyelim (route çakışmasını engellemek için).
    unset($menus['site-araclari']);

    // Diğer modüllerin karşıladığı slug'ları çıkar
    unset($menus['destek-merkezi']); // Ticket
    unset($menus['marketplace']);    // Marketplace
    unset($menus['ayarlar']);        // SettingsController
    unset($menus['loglar']);         // LogsController
    unset($menus['cache-center']);   // CacheController
    unset($menus['hosting-sunucu']); // AdminServerController (Faz 6d)
    unset($menus['domain-center']);  // AdminDomainController (Faz 6c)
    unset($menus['ai-center']);      // AiCenterController (Faz 6m)
    unset($menus['blog']);           // AdminBlogController (Faz 6n)
    unset($menus['musteriler']);     // AdminCustomerController (rapor 5.4)
    unset($menus['siparisler']);     // AdminOrderController
    unset($menus['menu-yonetimi']);
    unset($menus['modul-merkezi']);
    unset($menus['tema-bloklari']);
    unset($menus['duyurular']);
    unset($menus['sayfalar']);
    unset($menus['finans']);
    unset($menus['cerez-analizi']);
    unset($menus['ai-asistan']);
    unset($menus['qa-scan-center']);
    unset($menus['site-araclari']);
    unset($menus['site-builder']);

    $router->get('/site-araclari', [SiteToolsController::class, 'index'])->name('admin.site_tools');
    $router->get('/mobile-buildler', [MobileBuildAdminController::class, 'index'])->name('admin.mobile_build_jobs');
    $router->post('/mobile-buildler/{id}/tekrar', [MobileBuildAdminController::class, 'retry'])->middleware(['csrf']);
    $router->get('/mobile-buildler/{id}/status', [MobileBuildAdminController::class, 'status']);
    $router->get('/mobile-buildler/worker-health', [MobileBuildAdminController::class, 'workerHealth']);
    $router->get('/mobile-buildler/github-health', [MobileBuildAdminController::class, 'githubHealth']);
    unset($menus['mobile-builder']);

    $router->get('/site-builder', [AdminBuilderController::class, 'index'])->name('admin.site_builder');
    $router->get('/mobile-builder', [AdminBuilderController::class, 'index'])->name('admin.mobile_builder');
    $router->get('/site-builder/{id}', [AdminBuilderController::class, 'show']);
    $router->get('/mobile-builder/{id}', [AdminBuilderController::class, 'show']);
    $router->post('/site-builder/{id}/durum', [AdminBuilderController::class, 'updateStatus'])->middleware(['csrf']);
    $router->post('/mobile-builder/{id}/durum', [AdminBuilderController::class, 'updateStatus'])->middleware(['csrf']);

    $router->get('/cerez-analizi', [CookieAnalyticsController::class, 'index'])->name('admin.cookie_analytics');

    $router->get('/finans', [FinanceController::class, 'index'])->name('admin.finance');
    $router->get('/finans/export', [FinanceController::class, 'export']);

    $router->get('/tema-bloklari', [ThemeBlocksController::class, 'index'])->name('admin.theme_blocks');
    $router->post('/tema-bloklari/toggle', [ThemeBlocksController::class, 'toggle'])->middleware(['csrf']);

    $router->get('/menu-yonetimi', [MenuController::class, 'index'])->name('admin.menu');
    $router->post('/menu-yonetimi/kaydet', [MenuController::class, 'save'])->middleware(['csrf']);
    $router->post('/menu-yonetimi/toggle', [MenuController::class, 'toggle'])->middleware(['csrf']);
    $router->post('/menu-yonetimi/sil', [MenuController::class, 'delete'])->middleware(['csrf']);
    $router->get('/modul-merkezi', [ModuleController::class, 'index'])->name('admin.modules');
    $router->post('/modul-merkezi/toggle', [ModuleController::class, 'toggle'])->middleware(['csrf']);

    // ---- Müşteriler CRUD + Impersonate (Rapor 5.4) ----
    $router->get('/musteriler',                        [\App\Modules\Admin\Controllers\AdminCustomerController::class, 'index'])->name('admin.customers.index');
    $router->get('/musteriler/yeni',                   [\App\Modules\Admin\Controllers\AdminCustomerController::class, 'create']);
    $router->post('/musteriler/kaydet',                [\App\Modules\Admin\Controllers\AdminCustomerController::class, 'store'])->middleware(['csrf']);
    $router->get('/musteriler/{id}',                   [\App\Modules\Admin\Controllers\AdminCustomerController::class, 'show'])->name('admin.customers.show');
    $router->get('/musteriler/{id}/duzenle',           [\App\Modules\Admin\Controllers\AdminCustomerController::class, 'edit']);
    $router->post('/musteriler/{id}/guncelle',         [\App\Modules\Admin\Controllers\AdminCustomerController::class, 'update'])->middleware(['csrf']);
    $router->post('/musteriler/{id}/askiya-al',        [\App\Modules\Admin\Controllers\AdminCustomerController::class, 'suspend'])->middleware(['csrf']);
    $router->post('/musteriler/{id}/sil',              [\App\Modules\Admin\Controllers\AdminCustomerController::class, 'destroy'])->middleware(['csrf']);
    $router->post('/musteriler/{id}/adina-giris',      [\App\Modules\Admin\Controllers\AdminCustomerController::class, 'impersonate'])->middleware(['csrf']);
    $router->post('/musteriler/{id}/bakiye-ekle',      [\App\Modules\Admin\Controllers\AdminCustomerController::class, 'addCredit'])->middleware(['csrf']);
    $router->post('/musteriler/{id}/hosting/{hostingId}/sifre-goster', [\App\Modules\Admin\Controllers\AdminCustomerController::class, 'revealHostingPassword'])->middleware(['csrf']);

    // Siparişler CRUD
    $router->get('/siparisler',                        [\App\Modules\Admin\Controllers\AdminOrderController::class, 'index'])->name('admin.orders.index');
    $router->get('/siparisler/{id}',                   [\App\Modules\Admin\Controllers\AdminOrderController::class, 'show'])->name('admin.orders.show');
    $router->post('/siparisler/{id}/durum',            [\App\Modules\Admin\Controllers\AdminOrderController::class, 'updateStatus'])->middleware(['csrf']);

    // Faturalar CRUD
    $router->get('/faturalar',                         [\App\Modules\Admin\Controllers\AdminInvoiceController::class, 'index'])->name('admin.invoices.index');
    $router->get('/faturalar/{id}',                    [\App\Modules\Admin\Controllers\AdminInvoiceController::class, 'show'])->name('admin.invoices.show');
    $router->post('/faturalar/{id}/odeme-kaydet',      [\App\Modules\Admin\Controllers\AdminInvoiceController::class, 'recordPayment'])->middleware(['csrf']);
    $router->post('/faturalar/{id}/iptal',             [\App\Modules\Admin\Controllers\AdminInvoiceController::class, 'cancel'])->middleware(['csrf']);

    // Ödemeler (geçmiş)
    $router->get('/odemeler',                          [\App\Modules\Admin\Controllers\AdminPaymentController::class, 'index'])->name('admin.payments.index');

    // Vendorlar (Marketplace satıcıları)
    $router->get('/vendorlar',                         [\App\Modules\Admin\Controllers\AdminVendorController::class, 'index'])->name('admin.vendors.index');
    $router->get('/vendorlar/{id}',                    [\App\Modules\Admin\Controllers\AdminVendorController::class, 'show']);
    $router->post('/vendorlar/{id}/onayla',            [\App\Modules\Admin\Controllers\AdminVendorController::class, 'approve'])->middleware(['csrf']);
    $router->post('/vendorlar/{id}/askiya-al',         [\App\Modules\Admin\Controllers\AdminVendorController::class, 'suspend'])->middleware(['csrf']);

    // TLD Yönetimi
    $router->get('/tld-yonetimi',                      [\App\Modules\Admin\Controllers\AdminTldController::class, 'index'])->name('admin.tlds.index');
    $router->get('/tld-yonetimi/{id}/duzenle',         [\App\Modules\Admin\Controllers\AdminTldController::class, 'edit']);
    $router->post('/tld-yonetimi/{id}/guncelle',       [\App\Modules\Admin\Controllers\AdminTldController::class, 'update'])->middleware(['csrf']);
    $router->post('/tld-yonetimi/sync',                [\App\Modules\Admin\Controllers\AdminTldController::class, 'syncFromRegistrar'])->middleware(['csrf']);

    // Portfolio
    $router->get('/portfolio',                         [\App\Modules\Admin\Controllers\AdminPortfolioController::class, 'index'])->name('admin.portfolio.index');
    $router->get('/portfolio/yeni',                    [\App\Modules\Admin\Controllers\AdminPortfolioController::class, 'create']);
    $router->post('/portfolio/kaydet',                 [\App\Modules\Admin\Controllers\AdminPortfolioController::class, 'store'])->middleware(['csrf']);
    $router->get('/portfolio/{id}/duzenle',            [\App\Modules\Admin\Controllers\AdminPortfolioController::class, 'edit']);
    $router->post('/portfolio/{id}/guncelle',          [\App\Modules\Admin\Controllers\AdminPortfolioController::class, 'update'])->middleware(['csrf']);
    $router->post('/portfolio/{id}/sil',               [\App\Modules\Admin\Controllers\AdminPortfolioController::class, 'destroy'])->middleware(['csrf']);

    // ---- Impersonation'ı sonlandır (müşteri paneliyle admin arasında) ----
    $router->post('/adina-giris/cik',                  [\App\Modules\Admin\Controllers\ImpersonationController::class, 'stop'])->middleware(['csrf'])->name('admin.impersonate.stop');


    // ---- Faz 6h: Global search ----
    $router->get('/api/arama', [\App\Modules\Admin\Controllers\GlobalSearchController::class, 'search']);

    // ---- Faz 6: Bildirim API ----
    $router->get('/api/bildirimler',            [\App\Modules\Admin\Controllers\NotificationApiController::class, 'listAdmin']);
    $router->post('/api/bildirimler/{id}/okundu', [\App\Modules\Admin\Controllers\NotificationApiController::class, 'markRead'])->middleware(['csrf']);
    $router->post('/api/bildirimler/hepsi-okundu', [\App\Modules\Admin\Controllers\NotificationApiController::class, 'markAllRead'])->middleware(['csrf']);

    // ---- Faz 6: Admin ayarlar / loglar / cache CRUD ----
    $router->get('/ayarlar',       [\App\Modules\Admin\Controllers\SettingsController::class, 'index'])->name('admin.settings');
    $router->post('/ayarlar/kaydet',   [\App\Modules\Admin\Controllers\SettingsController::class, 'save'])->middleware(['csrf']);
    $router->post('/ayarlar/temizle',  [\App\Modules\Admin\Controllers\SettingsController::class, 'clearField'])->middleware(['csrf']);
    $router->post('/ayarlar/smtp-test',[\App\Modules\Admin\Controllers\SmtpTestController::class, 'send'])->middleware(['csrf']);
    $router->get('/loglar',        [\App\Modules\Admin\Controllers\LogsController::class, 'index'])->name('admin.logs');
    $router->get('/cache-center',  [\App\Modules\Admin\Controllers\CacheController::class, 'index'])->name('admin.cache');
    $router->post('/cache-center/temizle', [\App\Modules\Admin\Controllers\CacheController::class, 'clear'])->middleware(['csrf']);

    // ---- Faz 6e: Admin Güvenlik / 2FA ----
    $sc = \App\Modules\Admin\Controllers\SecurityController::class;
    $router->get('/guvenlik',              [$sc, 'index'])->name('admin.security');
    $router->post('/guvenlik/2fa-baslat',  [$sc, 'setupStart'])->middleware(['csrf']);
    $router->post('/guvenlik/2fa-onayla',  [$sc, 'setupConfirm'])->middleware(['csrf']);
    $router->post('/guvenlik/2fa-kapat',   [$sc, 'disable'])->middleware(['csrf']);

    // ---- Faz 6m: Backup ----
    $bc = \App\Modules\Admin\Controllers\BackupController::class;
    $router->get('/yedekleme',                [$bc, 'index'])->name('admin.backup');
    $router->post('/yedekleme/db',            [$bc, 'createDb'])->middleware(['csrf']);
    $router->post('/yedekleme/storage',       [$bc, 'createStorage'])->middleware(['csrf']);
    $router->get('/yedekleme/indir/{name}',   [$bc, 'download']);
    $router->post('/yedekleme/sil/{name}',    [$bc, 'delete'])->middleware(['csrf']);

    // ---- Faz 6e: Kupon CRUD ----
    $cc = \App\Modules\Admin\Controllers\CouponController::class;
    $router->get('/kuponlar',              [$cc, 'index'])->name('admin.coupons');
    $router->get('/kuponlar/yeni',         [$cc, 'createForm']);
    $router->post('/kuponlar/kaydet',      [$cc, 'store'])->middleware(['csrf']);
    $router->get('/kuponlar/{id}',         [$cc, 'editForm']);
    $router->post('/kuponlar/{id}/kaydet', [$cc, 'store'])->middleware(['csrf']);
    $router->post('/kuponlar/{id}/sil',    [$cc, 'delete'])->middleware(['csrf']);

    // ---- Faz 6b: Kur yönetimi ----
    $router->get('/kur-yonetimi',            [\App\Modules\Admin\Controllers\CurrencyController::class, 'index'])->name('admin.currency');
    $router->post('/kur-yonetimi/kaydet',    [\App\Modules\Admin\Controllers\CurrencyController::class, 'save'])->middleware(['csrf']);
    $router->post('/kur-yonetimi/refresh',   [\App\Modules\Admin\Controllers\CurrencyController::class, 'refresh'])->middleware(['csrf']);
    $router->post('/kur-yonetimi/ekle',      [\App\Modules\Admin\Controllers\CurrencyController::class, 'add'])->middleware(['csrf']);
    $router->post('/kur-yonetimi/{id}/sil',  [\App\Modules\Admin\Controllers\CurrencyController::class, 'delete'])->middleware(['csrf']);

});
