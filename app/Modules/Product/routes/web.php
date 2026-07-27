<?php

use App\Core\Http\Response;
use App\Core\Router;
use App\Core\View;
use App\Modules\Product\Controllers\AdminProductController;
use App\Modules\Product\Controllers\AdminProductOptionController;

/** @var Router $router */

// --- ADMIN: Ürün Merkezi CRUD ---
$router->group(['prefix' => 'admin', 'middleware' => ['locale', 'admin.auth']], function (Router $router) {
    $router->get('/urun-merkezi',                     [AdminProductController::class, 'index'])->name('admin.products.index');
    $router->get('/urun-merkezi/yeni',                [AdminProductController::class, 'create'])->name('admin.products.create');
    $router->post('/urun-merkezi/kaydet',             [AdminProductController::class, 'store'])->middleware(['csrf']);
    $router->get('/urun-merkezi/{id}/duzenle',        [AdminProductController::class, 'edit'])->name('admin.products.edit');
    $router->post('/urun-merkezi/{id}/guncelle',      [AdminProductController::class, 'update'])->middleware(['csrf']);
    $router->post('/urun-merkezi/{id}/sil',           [AdminProductController::class, 'destroy'])->middleware(['csrf']);

    // Paket Opsiyonları (Rapor 5.3)
    $router->get('/paket-opsiyonlari',                [AdminProductOptionController::class, 'index'])->name('admin.options.index');
    $router->get('/paket-opsiyonlari/yeni',           [AdminProductOptionController::class, 'create']);
    $router->post('/paket-opsiyonlari/kaydet',        [AdminProductOptionController::class, 'store'])->middleware(['csrf']);
    $router->get('/paket-opsiyonlari/{id}/duzenle',   [AdminProductOptionController::class, 'edit']);
    $router->post('/paket-opsiyonlari/{id}/guncelle', [AdminProductOptionController::class, 'update'])->middleware(['csrf']);
    $router->post('/paket-opsiyonlari/{id}/sil',      [AdminProductOptionController::class, 'destroy'])->middleware(['csrf']);
});

// --- PUBLIC ---
$router->group(['middleware' => ['locale', 'currency']], function (Router $router) {
    $products = [
        'hosting' => [
            'title' => 'Web Hosting',
            'subtitle' => 'NVMe SSD · LiteSpeed · Ücretsiz SSL · cPanel',
            'plans' => [
                ['name' => 'Başlangıç', 'price' => 39, 'features' => ['10 GB NVMe', '100 GB Trafik', '1 Web Sitesi', '5 E-posta Hesabı', 'Ücretsiz SSL', 'Günlük Yedek']],
                ['name' => 'Business', 'popular' => true, 'price' => 89, 'features' => ['50 GB NVMe', 'Sınırsız Trafik', 'Sınırsız Site', 'Sınırsız E-posta', 'Ücretsiz SSL Wildcard', 'Ücretsiz Domain (yıllık)', 'LiteSpeed']],
                ['name' => 'Kurumsal', 'price' => 189, 'features' => ['200 GB NVMe', 'Sınırsız Trafik', 'Sınırsız Site', 'Öncelikli Destek', 'Ücretsiz Domain + SSL', 'Yönetilen Sunucu']],
            ],
        ],
        'sunucular' => [
            'title' => 'VPS & Sunucu',
            'subtitle' => 'Tam kontrol, esnek kaynak, kurumsal performans.',
            'plans' => [
                ['name' => 'VPS S', 'price' => 249, 'features' => ['2 vCPU', '4 GB RAM', '60 GB NVMe', '4 TB Trafik', 'İstanbul DC']],
                ['name' => 'VPS M', 'popular' => true, 'price' => 449, 'features' => ['4 vCPU', '8 GB RAM', '120 GB NVMe', '8 TB Trafik', 'DDoS Koruma']],
                ['name' => 'VPS L', 'price' => 849, 'features' => ['8 vCPU', '16 GB RAM', '240 GB NVMe', 'Sınırsız Trafik', 'Yönetim Dahil']],
            ],
        ],
    ];

    foreach ($products as $slug => $productData) {
        (function () use ($router, $slug, $productData) {
            $router->get('/' . $slug, function () use ($productData) {
                $view = new View();
                return Response::html($view->render('product::plans', [
                    'title'     => $productData['title'],
                    'plan_data' => $productData,
                ]));
            });
        })();
    }

    // /domain → Domain modülü (Faz 4 gerçek sorgu)

    $router->get('/domain-transfer', function () {
        $view = new View();
        return Response::html($view->render('product::domain_transfer', ['title' => 'Domain Transfer']));
    });

    // /site-builder   → Builder modülü (Faz 5)
    // /mobile-builder → Builder modülü (Faz 5)

    // /site-araclari → SiteTools modülü (Faz 4 aktif entegrasyon)

    // /marketplace → Marketplace modülü (Faz 5)

    $router->get('/destek', function () {
        $view = new View();
        return Response::html($view->render('product::support', ['title' => 'Destek']));
    })->name('support.index');

    // Genel ürün detay: /urun/{slug}
    $router->get('/urun/{slug}', function (\App\Core\Http\Request $r) {
        $slug = (string) $r->param('slug');
        $product = \App\Modules\Product\Services\ProductRepository::findBySlug($slug);
        if (!$product) return \App\Core\Http\Response::notFound();
        $prices = \App\Modules\Product\Services\PricingService::activePrices((int) $product['id']);
        $productOptions = \App\Modules\Product\Services\OptionService::forProduct((int) $product['id']);
        $view = new View();
        return \App\Core\Http\Response::html($view->render('product::show', [
            'title'   => $product['name'],
            'description' => $product['seo_description'] ?: $product['short_description'],
            'product' => $product,
            'prices'  => $prices,
            'productOptions' => $productOptions,
        ]));
    })->name('product.show');
});


