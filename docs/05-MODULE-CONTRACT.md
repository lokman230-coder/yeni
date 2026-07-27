# 05 · Modül Sözleşmesi

Bir modülü Ahost Bilişim'a eklemek için bu dokümandaki kurallara uymanız gerekir.

---

## 1. Bir Modül Nedir?

Ahost Bilişim'da modül, **tek başına anlamlı bir iş alanını** temsil eder:
`Cart`, `Product`, `Domain`, `Hosting`, `SiteBuilder` gibi.

Bir modül:
- Kendi route'larını,
- Kendi controller'larını,
- Kendi servis/repository/model'lerini,
- Kendi migration'larını,
- Kendi view'larını,
- Kendi CSS/JS asset'lerini,
- Kendi çeviri dosyalarını,
- Kendi izinlerini içerir.

---

## 2. Zorunlu Dosya Yapısı

```
app/Modules/<PascalCaseName>/
├── module.php              ← MANIFEST (zorunlu)
├── routes/
│   ├── web.php             ← public route'lar
│   ├── admin.php           ← /admin/... route'ları
│   ├── customer.php        ← /panel/... route'ları
│   └── api.php             ← /api/v1/... route'ları
├── Controllers/
├── Services/
├── Repositories/
├── Models/
├── Migrations/
├── Views/
├── assets/
│   ├── css/<module>.css
│   └── js/<module>.js
├── lang/
│   ├── tr.php
│   └── en.php
├── permissions.php         ← modül izinleri (RBAC)
└── README.md               ← modül ne yapar
```

---

## 3. `module.php` Manifest

```php
<?php
return [
    'name'         => 'Cart',
    'slug'         => 'cart',
    'version'      => '1.0.0',
    'description'  => 'Sepet yönetimi, ek paket, kupon, vergi hesaplama.',
    'author'       => 'Ahost Bilişim Core Team',
    'requires'     => ['Product', 'Currency'],   // hangi modüller çalışıyor olmalı
    'provides'     => ['CartService'],
    'routes'       => ['web', 'customer', 'api'],
    'migrations'   => true,
    'assets'       => [
        'css' => ['cart.css'],
        'js'  => ['cart.js'],
    ],
    'permissions_file' => 'permissions.php',
    'is_core'      => true,      // core modüller kapatılamaz
    'auto_load'    => true,
    'sort_order'   => 30,
];
```

---

## 4. Modül Yükleme Akışı

```
ModuleLoader::boot()
   │
   ├── config/modules.php → aktif modül listesi
   ├── her modül için module.php oku
   ├── bağımlılık kontrolü (requires)
   ├── dependency injection container'a servisleri kaydet
   ├── route dosyalarını Router'a yükle
   ├── permissions.php'yi RBAC'a kaydet
   ├── migration'ları migration listesine ekle
   └── asset registry'ye CSS/JS ekle (route match'te enqueue edilir)
```

---

## 5. Asset Injection Kuralı (KRİTİK)

**Şartname:** *"Her modül kendi CSS/JS dosyalarına sahip olacak."*

Uygulama:
- Layout render'da `<head>`e tema CSS'i her zaman gelir.
- Modül CSS/JS'i **sadece** o route aktifken enqueue edilir.

```php
// app/Core/View.php içinde
public function render(string $template, array $data = []): string {
    $enqueuedCss = AssetRegistry::cssForCurrentRoute();
    $enqueuedJs  = AssetRegistry::jsForCurrentRoute();
    // ...
}
```

Bu sayede Cart CSS'i Product sayfasında yüklenmez; ne çakışma ne bloat.

**Ortak asset:** `themes/default/css/theme.css` ve `theme.js` her sayfada gelir.

---

## 6. View Yükleme

```php
// Bir controller içinde:
return $this->view('cart::index', ['items' => $items]);
//              ^^^^^^ modül namespace
```

`cart::index` → `app/Modules/Cart/Views/index.php`

Global layout:
```php
// index.php içinde:
<?php $this->extends('layouts.public'); ?>
<?php $this->section('content'); ?>
   <!-- sepet HTML -->
<?php $this->endSection(); ?>
```

---

## 7. Route Tanımı

```php
// app/Modules/Cart/routes/web.php
use App\Core\Router;

Router::group(['middleware' => ['locale', 'currency', 'csrf']], function() {
    Router::get('/sepet', [CartController::class, 'index'])->name('cart.index');
    Router::post('/sepet/ekle', [CartController::class, 'add'])->name('cart.add');
    Router::post('/sepet/sil/{id}', [CartController::class, 'remove'])->name('cart.remove');
    Router::post('/sepet/kupon', [CartController::class, 'applyCoupon'])->name('cart.coupon');
});
```

---

## 8. Servis Tanımı & DI

```php
// module.php içinde
'providers' => [
    \App\Modules\Cart\CartServiceProvider::class,
],
```

```php
// CartServiceProvider.php
public function register(Container $container): void {
    $container->singleton(CartService::class, function($c) {
        return new CartService(
            $c->get(CartRepository::class),
            $c->get(TaxService::class),
            $c->get(CurrencyService::class)
        );
    });
}
```

---

## 9. Migration

```php
// app/Modules/Cart/Migrations/0001_create_cart_items_table.php
class CreateCartItemsTable extends Migration {
    public function up(): void {
        $this->schema->create('cart_items', function($t) {
            $t->id();
            $t->string('session_id', 128)->nullable();
            // ...
        });
    }
    public function down(): void {
        $this->schema->dropIfExists('cart_items');
    }
}
```

Migration çalıştırma: `php console migrate` — hem çekirdek hem modül migration'ları toplanır, sıralanır (`{module}_{filename}` format), pending olanlar çalışır.

---

## 10. İzinler (permissions.php)

```php
return [
    ['key' => 'cart.view',       'label' => 'Sepet görüntüle',    'group' => 'Sepet'],
    ['key' => 'cart.manage',     'label' => 'Sepet yönet',        'group' => 'Sepet'],
    ['key' => 'coupon.create',   'label' => 'Kupon oluştur',      'group' => 'Sepet'],
];
```

Controller içinde:
```php
public function applyCoupon(Request $r): Response {
    $this->authorize('coupon.create');    // yoksa 403
    // ...
}
```

---

## 11. Çeviri

```php
// app/Modules/Cart/lang/tr.php
return [
    'title'       => 'Sepetim',
    'empty'       => 'Sepetiniz boş.',
    'add_coupon'  => 'Kupon uygula',
    'subtotal'    => 'Ara toplam',
    'tax'         => 'KDV',
    'total'       => 'Toplam',
];
```

Kullanım: `__('cart.title')` veya View içinde `<?= __('cart.title') ?>`.

---

## 12. Event Bus (opsiyonel ama önerilen)

Modüller birbirini çağırmak yerine event fırlatır:
```php
// Cart'ta:
Event::dispatch(new CouponApplied($coupon, $customer));

// Analytics modülü dinler:
Event::listen(CouponApplied::class, function($e) {
    AnalyticsService::track('coupon_used', $e->coupon->code);
});
```

Bu sayede Cart, Analytics'i tanımaz.

---

## 13. Test

Her modülün `tests/` altında:
- `Unit/` — servis testleri.
- `Feature/` — HTTP endpoint testleri.

CI PR'da bu testleri otomatik koşturur.

---

## 14. Modül Silme / Devre Dışı Bırakma

- **Devre dışı:** `config/modules.php` içinde `'cart' => false`. Route'lar düşer, migration'lar durur, veriler kalır.
- **Silme:** Admin panelinden "Uninstall". Migration `down()` çalışır, dosyalar `storage/removed-modules/` altına arşivlenir.
- **Core modüller:** `is_core => true` olanlar silinemez (Auth, RBAC, Settings, Product, Order, Invoice, Customer).

---

## 15. Bir Modül Yazma Checklist'i

- [ ] `module.php` manifest tam ve doğru
- [ ] Namespace PSR-4 uyumlu
- [ ] CSS class'ları `.aho-<slug>-*` prefix'li
- [ ] JS `AhostOne.modules.<Name>` altında
- [ ] Route'lar named
- [ ] Middleware doğru bağlanmış
- [ ] Migration up() ve down() test edilmiş
- [ ] Permission'lar tanımlı
- [ ] Çeviri tr + en tam
- [ ] Unit + feature test yazılmış
- [ ] README.md yazılmış
- [ ] Hiç `!important`, `global`, `eval` yok
