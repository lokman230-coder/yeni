# 04 · Kodlama Standartları

> Amaç: Şartname madde 41'in ("yama yasağı") kod düzeyindeki karşılığı.
> Bu kurallar tavsiye değil, PR kabul kriteridir.

---

## 1. PHP

### 1.1 Genel
- **PHP sürüm:** 8.2+ zorunlu.
- Her dosyanın başında `declare(strict_types=1);`
- **PSR-12** kod stili.
- **PSR-4** autoload (namespace = dizin).
- Fonksiyon dönüş tipi ve parametre tipi **her zaman** yazılır.
- `null` dönüş açık belirtilir (`?string`).
- `mixed` sadece gerçekten gerektiğinde.

### 1.2 Namespace
```
App\Core\...
App\Support\...
App\Services\<Area>\...
App\Middleware\...
App\Modules\<Module>\Controllers\...
App\Modules\<Module>\Services\...
App\Integrations\<Vendor>\...
```

### 1.3 Yasaklar
- ❌ `mysql_*`, `mysqli_*` — sadece PDO.
- ❌ `eval()`, `create_function()`.
- ❌ Global değişkenler (`$GLOBALS`, `global`).
- ❌ `extract()` kullanıcı verisi üzerinde.
- ❌ `@` operator (hataları bastırma).
- ❌ Kısa açılış etiketleri `<?` — sadece `<?php`.
- ❌ Controller içinde SQL — Repository katmanı zorunlu.
- ❌ View içinde iş mantığı — sadece görüntü.
- ❌ Aynı if/switch bloğunu birden fazla dosyada tekrar — refactor et.

### 1.4 Zorunlular
- ✅ Tüm giriş `Request::input()` üzerinden — asla ham `$_POST`, `$_GET`.
- ✅ Tüm çıkış `View::render()` üzerinden auto-escape.
- ✅ Tüm DB PDO prepared statements.
- ✅ Tüm exception yakalanır ve `Logger::error()` ile loglanır.
- ✅ Tüm public method PHPDoc.
- ✅ Test edilmesi zor kod = kötü kod. Refactor et.

### 1.5 Dosya Uzunluğu
- Class ≤ 300 satır (hedef 150).
- Method ≤ 40 satır (hedef 20).
- Bir dosya = bir class.

---

## 2. CSS

### 2.1 Namespace (KRİTİK)
Şartname: *"CSS bir modülden diğerini zorla ezmeyecek."*

Kural: **Her class `.aho-<module>-*` öneki taşır.**

```css
/* app/Modules/Cart/assets/css/cart.css */
.aho-cart-list { ... }
.aho-cart-item { ... }
.aho-cart-item__name { ... }
.aho-cart-item__price { ... }
.aho-cart-item--removed { ... }   /* BEM state */
```

Ortak temaya ait olanlar `.aho-<component>` — modül öneki olmadan, `themes/default/css/components/`:
```css
.aho-btn { ... }
.aho-btn--primary { ... }
.aho-card { ... }
.aho-form-input { ... }
```

### 2.2 Yasaklar
- ❌ `!important` — sadece utility class'larda ve tema token override'ında kabul edilir.
- ❌ ID selector'a stil (`#header { ... }`) — sadece hook için ID.
- ❌ Universal selector `* { ... }` (reset dışında).
- ❌ `body` altı serbest tag selector (`body h1 { ... }`).
- ❌ Inline `<style>` bloğu — sadece kritik above-fold CSS için (bir yerde, `<head>`).
- ❌ Element'e `style="..."` — sadece runtime dinamik değer için.

### 2.3 Zorunlular
- ✅ Mobile-first: default = mobil, `@media (min-width: 768px)` = tablet, `@media (min-width: 992px)` = desktop.
- ✅ CSS Custom Properties (design tokens) `:root`'ta:
  ```css
  :root {
    --aho-color-primary: #0f172a;
    --aho-color-accent: #06b6d4;
    --aho-radius-md: 12px;
    --aho-shadow-sm: 0 1px 2px rgb(0 0 0 / .05);
    --aho-space-4: 1rem;
    --aho-font-sans: 'Inter', system-ui, sans-serif;
  }
  ```
- ✅ Dark mode `[data-theme="dark"]` ile.
- ✅ `prefers-reduced-motion` respect.

### 2.4 Layout Sıçraması Önlemi (şartname madde 2)
- Görsel: `width` + `height` attribute'lu.
- Font: `font-display: swap` + `preload`.
- Header yüksekliği kritik CSS'te sabit.
- Skeleton loader kullan (spinner değil).

---

## 3. JavaScript

### 3.1 Namespace
```js
// Tek global: window.AhostOne
window.AhostOne = window.AhostOne || { modules: {}, config: {}, utils: {} };

// Modül tanımı (IIFE)
(function(App) {
  'use strict';
  const Cart = {
    init() {
      document.querySelectorAll('[data-aho-cart-add]').forEach(btn => {
        btn.addEventListener('click', this.handleAdd.bind(this));
      });
    },
    handleAdd(e) { ... },
    add(productId, period, addons) { ... }
  };
  App.modules.Cart = Cart;
  document.addEventListener('DOMContentLoaded', () => Cart.init());
})(window.AhostOne);
```

### 3.2 Yasaklar
- ❌ `document.write`.
- ❌ jQuery (isteğe bağlı: builder'da bile Vanilla).
- ❌ `eval`, `new Function(str)`.
- ❌ Global function tanımı (`function foo() {...}` global scope'ta).
- ❌ Inline `onclick="..."`.
- ❌ `alert`, `confirm`, `prompt` — kendi modal.

### 3.3 Zorunlular
- ✅ `'use strict'` her modülde.
- ✅ Event delegation: `data-aho-*` attribute'a bind.
- ✅ `fetch` + `AbortController` (jQuery ajax yok).
- ✅ Loading state ve error state UI'da gösterilir.
- ✅ `defer` attribute — asla `async` (sıra bozulur).
- ✅ Console.log production'da otomatik kırpılır (build step).

### 3.4 Event İsimlendirme
Custom event'ler `aho:` prefix'li:
```js
document.dispatchEvent(new CustomEvent('aho:cart:updated', { detail: {...} }));
```

---

## 4. HTML

- Semantik: `<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<aside>`, `<footer>`.
- Her form input `<label for="">` ile eşleşir.
- Buton kullanılacaksa `<button>`, link için `<a>`. Div'e onclick bağlanmaz.
- Icon-only butonlar `aria-label` alır.
- `<img>` her zaman `alt` — süsleme ise `alt=""`.
- `lang` attribute `<html>`.

---

## 5. Git & Commit

### 5.1 Branch
- `main` — production.
- `develop` — geliştirme.
- `feat/<modul>-<kisa>` — özellik.
- `fix/<modul>-<kisa>` — hata.
- `chore/...`, `docs/...`.

### 5.2 Commit (Conventional)
```
feat(cart): add coupon validation
fix(header): mobile menu z-index over sticky
docs(architecture): add module contract section
refactor(product): extract PricingService
test(currency): cover margin calculation edge cases
```

### 5.3 PR Kuralları
- Her PR = tek özellik / tek fix.
- CI yeşil olmadan merge yok (PHPUnit + PHP CS Fixer + Psalm/PHPStan).
- Screenshot ekle (UI değişikliği).
- Migration varsa `rollback`i test edilmiş olmalı.

---

## 6. Test

- **Unit:** service ve helper'lar. Coverage hedefi %60.
- **Feature:** controller endpoint'leri (HTTP → DB).
- **E2E:** Playwright ile kritik user flow'ları:
  - Ziyaretçi → ürün seç → sepet → kayıt → ödeme → fatura
  - Admin → login → ürün oluştur → fiyat ekle → yayına al
  - Müşteri → ticket aç → yanıt bekle → kapat

---

## 7. Dokümantasyon

- Her modülün `README.md`'si var (ne yapar, hangi tabloları kullanır, hangi servisi çağırır).
- Her public method PHPDoc.
- Karmaşık algoritma başında `// WHY:` yorumu (ne değil, neden).

---

## 8. "Yama" tespiti — CI kontrolleri

Otomatik denetim:
1. `!important` sayısı sınırın üstünde ise uyarı.
2. Aynı dosyada `TODO`/`FIXME` sayısı ≥ 3 ise uyarı.
3. Class > 300 satır → hata.
4. Method > 40 satır → uyarı.
5. Duplicate code (phpcpd) ≥ 20 satır → hata.
6. Psalm/PHPStan level ≥ 6.
