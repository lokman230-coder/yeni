<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$product = $product ?? null;
$error = flash('error');
$success = flash('success');
$isEdit = $product !== null;
$actionUrl = $isEdit ? '/admin/urun-merkezi/' . (int)$product['id'] . '/guncelle' : '/admin/urun-merkezi/kaydet';
$existingPrices = $prices ?? [];
$servers = $servers ?? [];
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1><?= $isEdit ? 'Ürünü Düzenle' : 'Yeni Ürün' ?></h1>
            <p><?= $isEdit ? e($product['name']) : 'Ürün bilgilerini doldurun ve fiyatlandırmayı ekleyin.' ?></p>
        </div>
        <a href="/admin/urun-merkezi" class="aho-btn aho-btn--ghost aho-btn--sm">← Listeye Dön</a>
    </div>

    <?php if ($success): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <form method="post" action="<?= e($actionUrl) ?>" id="ahoProductForm">
        <?= csrf() ?>

        <div class="aho-product-editor-shell">
            <div class="aho-product-editor-tabs" role="tablist" aria-label="Urun duzenleme sekmeleri">
                <button type="button" class="is-active" data-product-tab="0">Temel</button>
                <button type="button" data-product-tab="1">Kurulum</button>
                <button type="button" data-product-tab="2">Fiyatlar</button>
                <button type="button" data-product-tab="3">SEO</button>
                <button type="button" data-product-tab="4">Ek Paketler</button>
                <button type="button" data-product-tab="5">Ozel Alanlar</button>
                <?php if (!empty($product['id'])): ?>
                    <button type="button" data-product-tab="6">Opsiyonlar</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="aho-card" style="margin-bottom:var(--aho-space-4)">
            <h3 class="aho-card__title" style="margin-bottom:var(--aho-space-4)">Temel Bilgiler</h3>

            <div class="aho-admin-form-row aho-admin-form-row--3">
                <div class="aho-form-group">
                    <label class="aho-form-label aho-form-label--required">Ürün Adı</label>
                    <input type="text" name="name" class="aho-form-input"
                           value="<?= e($product['name'] ?? old('name')) ?>" required>
                </div>
                <div class="aho-form-group">
                    <label class="aho-form-label">Grup</label>
                    <select name="group_id" class="aho-form-select">
                        <option value="">— Grup seçin —</option>
                        <?php foreach ($groups as $g): ?>
                            <option value="<?= (int)$g['id'] ?>" <?= ((int)($product['group_id'] ?? 0) === (int)$g['id']) ? 'selected' : '' ?>>
                                <?= e($g['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="aho-admin-form-row aho-admin-form-row--3">
                <div class="aho-form-group">
                    <label class="aho-form-label aho-form-label--required">Tip</label>
                    <select name="type" class="aho-form-select" required>
                        <?php foreach ($types as $k => $v): ?>
                            <option value="<?= e($k) ?>" <?= ($product['type'] ?? 'hosting') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="aho-form-group">
                    <label class="aho-form-label">Durum</label>
                    <select name="status" class="aho-form-select">
                        <?php foreach (['active'=>'Aktif','hidden'=>'Gizli','disabled'=>'Pasif'] as $k => $v): ?>
                            <option value="<?= $k ?>" <?= ($product['status'] ?? 'hidden') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="aho-form-group">
                    <label class="aho-form-label">Sıralama</label>
                    <input type="number" name="sort_order" class="aho-form-input"
                           value="<?= (int)($product['sort_order'] ?? 0) ?>">
                </div>
            </div>

            <div class="aho-form-group">
                <label class="aho-form-label" style="display:flex;justify-content:space-between;align-items:center">
                    <span>Kısa Açıklama</span>
                    <button type="button" onclick="aiFillProduct()" style="padding:4px 10px;background:linear-gradient(135deg,#8b5cf6,#ec4899);color:#fff;border:0;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer">🤖 AI ile Doldur</button>
                </label>
                <input type="text" name="short_description" id="shortDesc" class="aho-form-input"
                       value="<?= e($product['short_description'] ?? '') ?>"
                       placeholder="Ürün kartlarında görünecek kısa açıklama">
            </div>
            <div class="aho-form-group">
                <label class="aho-form-label">Uzun Açıklama</label>
                <textarea name="description" id="longDesc" class="aho-form-textarea" rows="8"><?= e($product['description'] ?? '') ?></textarea>
                <div id="aiFeatures" style="margin-top:8px"></div>
            </div>
        </div>

        <script>
        async function aiFillProduct() {
            const name = document.querySelector('[name="name"]')?.value?.trim();
            const type = document.querySelector('[name="type"]')?.value || 'hosting';
            if (!name) { alert('Önce ürün adını girin.'); return; }
            const btn = event.target;
            btn.disabled = true; const orig = btn.textContent; btn.textContent = '⏳ Üretiliyor...';
            try {
                const r = await fetch('/admin/api/ai/product', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':'<?= csrf_token() ?>','Accept':'application/json'},
                    body: '_csrf=<?= csrf_token() ?>&name=' + encodeURIComponent(name) + '&type=' + encodeURIComponent(type)
                });
                const d = await r.json();
                if (!d.ok) { alert('Hata: ' + (d.error || 'bilinmeyen')); return; }
                document.getElementById('shortDesc').value = d.short || '';
                document.getElementById('longDesc').value  = d.long || '';
                if (Array.isArray(d.features) && d.features.length) {
                    document.getElementById('aiFeatures').innerHTML = '<div style="font-size:12px;color:#6b7280;margin-bottom:6px">Önerilen özellikler:</div><div style="display:flex;flex-wrap:wrap;gap:6px">' +
                        d.features.map(f => '<span style="padding:3px 10px;background:#ede9fe;color:#6d28d9;border-radius:12px;font-size:12px">✓ ' + f + '</span>').join('') + '</div>';
                }
            } catch(e) { alert('İstek hatası: ' + e.message); }
            finally { btn.disabled = false; btn.textContent = orig; }
        }
        </script>

        <!-- Ödeme & Kurulum -->
        <div class="aho-card" style="margin-bottom:var(--aho-space-4)">
            <h3 class="aho-card__title" style="margin-bottom:var(--aho-space-4)">Ödeme & Kurulum</h3>
            <div class="aho-admin-form-row aho-admin-form-row--3">
                <div class="aho-form-group">
                    <label class="aho-form-label">Ödeme Tipi</label>
                    <select name="payment_type" class="aho-form-select">
                        <?php foreach (['recurring'=>'Yinelenen','onetime'=>'Tek Seferlik','free'=>'Ücretsiz'] as $k=>$v): ?>
                            <option value="<?= $k ?>" <?= ($product['payment_type'] ?? 'recurring') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="aho-form-group">
                    <label class="aho-form-label">Kurulum Ücreti</label>
                    <input type="number" step="0.01" name="setup_fee" class="aho-form-input"
                           value="<?= (float)($product['setup_fee'] ?? 0) ?>">
                </div>
                <div class="aho-form-group">
                    <label class="aho-form-label">Kurulum Para Birimi</label>
                    <select name="setup_fee_currency" class="aho-form-select">
                        <?php foreach ($currencies as $c): ?>
                            <option value="<?= $c ?>" <?= ($product['setup_fee_currency'] ?? 'TRY') === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="aho-admin-form-row aho-admin-form-row--2">
                <div class="aho-form-group">
                    <label class="aho-form-label">Otomasyon Modülü</label>
                    <select name="automation_module" class="aho-form-select">
                        <option value="">— Yok —</option>
                        <option value="cpanel"     <?= ($product['automation_module'] ?? '') === 'cpanel' ? 'selected' : '' ?>>cPanel</option>
                        <option value="da"         <?= ($product['automation_module'] ?? '') === 'da' ? 'selected' : '' ?>>DirectAdmin</option>
                        <option value="plesk"      <?= ($product['automation_module'] ?? '') === 'plesk' ? 'selected' : '' ?>>Plesk</option>
                        <option value="manual"     <?= ($product['automation_module'] ?? '') === 'manual' ? 'selected' : '' ?>>Manuel</option>
                    </select>
                </div>
                <div class="aho-form-group">
                    <label class="aho-form-label">Sunucu</label>
                    <select name="server_id" class="aho-form-select">
                        <option value="">— Otomatik / en az yüklü sunucu —</option>
                        <?php foreach ($servers as $sv): ?>
                            <option value="<?= (int)$sv['id'] ?>" <?= (int)($product['server_id'] ?? 0) === (int)$sv['id'] ? 'selected' : '' ?> <?= empty($sv['is_active']) ? 'disabled' : '' ?>>
                                <?= e($sv['name']) ?><?= !empty($sv['hostname']) ? ' (' . e($sv['hostname']) . ')' : '' ?><?= empty($sv['is_active']) ? ' — pasif' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="aho-form-help">Kurulumda hesap doğrudan bu sunucuda açılır.</small>
                </div>
                <div class="aho-form-group">
                    <label class="aho-form-label">Stok Tipi</label>
                    <select name="stock_type" class="aho-form-select">
                        <option value="unlimited" <?= ($product['stock_type'] ?? 'unlimited') === 'unlimited' ? 'selected' : '' ?>>Sınırsız</option>
                        <option value="limited"   <?= ($product['stock_type'] ?? 'unlimited') === 'limited' ? 'selected' : '' ?>>Sınırlı</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Fiyatlandırma -->
        <div class="aho-card" style="margin-bottom:var(--aho-space-4)">
            <div class="aho-card__header">
                <h3 class="aho-card__title">Fiyatlandırma</h3>
                <button type="button" class="aho-btn aho-btn--primary aho-btn--sm" id="ahoAddPriceBtn">+ Fiyatlandırma Ekle</button>
            </div>

            <div id="ahoPricesContainer">
                <?php if (empty($existingPrices)): ?>
                    <div class="aho-empty-state" style="padding:var(--aho-space-6)" id="ahoPricesEmpty">
                        <div class="aho-empty-state__icon">💰</div>
                        <div class="aho-empty-state__title">Henüz fiyat eklenmemiş</div>
                        <div class="aho-empty-state__text">"Fiyatlandırma Ekle" butonuyla periyot bazlı fiyat ekleyebilirsiniz.</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($existingPrices as $i => $pr): ?>
                        <div class="aho-price-row" data-price-row>
                            <div class="aho-form-group" style="margin:0">
                                <label class="aho-form-label">Periyot</label>
                                <select name="prices[<?= $i ?>][period]" class="aho-form-select">
                                    <?php foreach ($periods as $per): ?>
                                        <option value="<?= $per ?>" <?= $pr['period'] === $per ? 'selected' : '' ?>>
                                            <?= \App\Modules\Product\Services\PricingService::periodLabel($per) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="aho-form-group" style="margin:0">
                                <label class="aho-form-label">Para Birimi</label>
                                <select name="prices[<?= $i ?>][source_currency]" class="aho-form-select">
                                    <?php foreach ($currencies as $c): ?>
                                        <option value="<?= $c ?>" <?= $pr['source_currency'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="aho-form-group" style="margin:0">
                                <label class="aho-form-label">Fiyat</label>
                                <input type="number" step="0.01" name="prices[<?= $i ?>][source_price]"
                                       class="aho-form-input" value="<?= e($pr['source_price']) ?>">
                            </div>
                            <div class="aho-form-group" style="margin:0">
                                <label class="aho-form-check">
                                    <input type="checkbox" name="prices[<?= $i ?>][is_active]" value="1" <?= (int)$pr['is_active'] === 1 ? 'checked' : '' ?>>
                                    Aktif
                                </label>
                            </div>
                            <button type="button" class="aho-btn aho-btn--ghost aho-btn--sm" data-price-remove aria-label="Sil">🗑️</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <p style="margin-top:var(--aho-space-3);font-size:var(--aho-text-xs);color:var(--aho-color-ink-500)">
                💡 Fiyat boş bırakılırsa veya "Aktif" işaretlenmezse sitede görünmez. Kaynak para birimi seçilir; diğer para birimlerine kur + kar marjı ile otomatik dönüştürülür.
            </p>
        </div>

        <!-- SEO -->
        <div class="aho-card" style="margin-bottom:var(--aho-space-4)">
            <h3 class="aho-card__title" style="margin-bottom:var(--aho-space-4);display:flex;justify-content:space-between;align-items:center">
                <span>SEO</span>
                <button type="button" onclick="aiFillSeo()" style="padding:4px 10px;background:linear-gradient(135deg,#8b5cf6,#ec4899);color:#fff;border:0;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer">🤖 AI ile Öner</button>
            </h3>
            <div class="aho-form-group">
                <label class="aho-form-label">SEO Başlık</label>
                <input type="text" name="seo_title" id="seoTitle" class="aho-form-input" value="<?= e($product['seo_title'] ?? '') ?>">
            </div>
            <div class="aho-form-group">
                <label class="aho-form-label">SEO Açıklama</label>
                <textarea name="seo_description" id="seoDesc" class="aho-form-textarea" rows="2"><?= e($product['seo_description'] ?? '') ?></textarea>
            </div>
            <div class="aho-form-group">
                <label class="aho-form-label">Anahtar Kelimeler</label>
                <input type="text" name="seo_keywords" id="seoKeywords" class="aho-form-input" value="<?= e($product['seo_keywords'] ?? '') ?>" placeholder="virgülle ayırın">
            </div>
        </div>

        <!-- Ek Paketler (Rapor 5.3) -->
        <?php
        $addonRows = $addons ?? [];
        while (count($addonRows) < 2) {
            $addonRows[] = ['name'=>'','slug'=>'','description'=>'','price'=>0,'currency'=>'TRY','period'=>'monthly','addon_type'=>'','automation_code'=>'','is_active'=>1];
        }
        ?>
        <div class="aho-card" style="margin-bottom:var(--aho-space-4)">
            <h3 class="aho-card__title" style="margin-bottom:var(--aho-space-4);display:flex;justify-content:space-between;align-items:center">
                <span>📦 Ek Paketler</span>
                <button type="button" class="aho-btn aho-btn--sm aho-btn--outline" onclick="ahoAddAddonRow()">+ Satır Ekle</button>
            </h3>
            <p style="color:var(--aho-muted);margin:0 0 12px;font-size:13px">
                Ürünle birlikte satılabilecek disk, trafik, e-posta, yedekleme, CDN, ek IP vb. paketler.
            </p>
            <div class="aho-product-matrix">
            <table class="aho-table aho-product-config-table" id="aho-addon-rows">
                <thead>
                    <tr>
                        <th>Ad *</th>
                        <th>Açıklama</th>
                        <th>Fiyat</th>
                        <th>Para</th>
                        <th>Periyot</th>
                        <th>Tip</th>
                        <th>Aktif</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($addonRows as $i => $a): ?>
                        <tr>
                            <td><input type="text" name="addons[<?= $i ?>][name]" value="<?= e($a['name']) ?>" placeholder="Örn: Ek 10 GB Disk" style="min-width:150px"></td>
                            <td><input type="text" name="addons[<?= $i ?>][description]" value="<?= e($a['description'] ?? '') ?>" placeholder="Kısa açıklama"></td>
                            <td><input type="number" step="0.01" name="addons[<?= $i ?>][price]" value="<?= (float)$a['price'] ?>" style="width:90px"></td>
                            <td>
                                <select name="addons[<?= $i ?>][currency]">
                                    <?php foreach (['TRY','USD','EUR','GBP'] as $c): ?>
                                        <option value="<?= $c ?>" <?= ($a['currency'] ?? 'TRY') === $c ? 'selected' : '' ?>><?= $c ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="addons[<?= $i ?>][period]">
                                    <?php foreach (['onetime'=>'Tek Sefer','monthly'=>'Aylık','quarterly'=>'3 Ay','semiannually'=>'6 Ay','annually'=>'Yıllık','biennially'=>'2 Yıl','triennially'=>'3 Yıl'] as $pk => $pl): ?>
                                        <option value="<?= $pk ?>" <?= ($a['period'] ?? 'monthly') === $pk ? 'selected' : '' ?>><?= $pl ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="addons[<?= $i ?>][addon_type]">
                                    <option value="">—</option>
                                    <?php foreach (['disk'=>'Disk','bandwidth'=>'Trafik','email'=>'E-posta','ssl'=>'SSL','backup'=>'Yedekleme','ip'=>'Ek IP','cdn'=>'CDN','migration'=>'Site Taşıma','other'=>'Diğer'] as $tk => $tl): ?>
                                        <option value="<?= $tk ?>" <?= ($a['addon_type'] ?? '') === $tk ? 'selected' : '' ?>><?= $tl ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="text-align:center"><input type="checkbox" name="addons[<?= $i ?>][is_active]" value="1" <?= !empty($a['is_active']) ? 'checked' : '' ?>></td>
                            <td><button type="button" class="aho-btn aho-btn--sm aho-btn--ghost" onclick="this.closest('tr').remove()">✕</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <!-- Özel Alanlar (Rapor 5.3) -->
        <?php
        $cfRows = $customFields ?? [];
        while (count($cfRows) < 2) {
            $cfRows[] = ['label'=>'','field_key'=>'','field_type'=>'text','options'=>'','is_required'=>0,'is_active'=>1,'show_on'=>'order'];
        }
        ?>
        <div class="aho-card" style="margin-bottom:var(--aho-space-4)">
            <h3 class="aho-card__title" style="margin-bottom:var(--aho-space-4);display:flex;justify-content:space-between;align-items:center">
                <span>📝 Özel Sipariş Alanları</span>
                <button type="button" class="aho-btn aho-btn--sm aho-btn--outline" onclick="ahoAddCfRow()">+ Satır Ekle</button>
            </h3>
            <p style="color:var(--aho-muted);margin:0 0 12px;font-size:13px">
                Müşteriden sipariş sırasında alınacak bilgiler: metin, IP, URL, e-posta, telefon, dosya, dropdown vb.
            </p>
            <div class="aho-product-matrix">
            <table class="aho-table aho-product-config-table" id="aho-cf-rows">
                <thead>
                    <tr>
                        <th>Etiket *</th>
                        <th>Anahtar</th>
                        <th>Tip</th>
                        <th>Seçenekler <small>(| ile)</small></th>
                        <th>Nerede</th>
                        <th>Zorunlu</th>
                        <th>Aktif</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cfRows as $i => $f):
                        $opts = '';
                        if (!empty($f['options'])) {
                            $decoded = is_string($f['options']) ? json_decode($f['options'], true) : $f['options'];
                            if (is_array($decoded)) $opts = implode('|', $decoded);
                        }
                    ?>
                        <tr>
                            <td><input type="text" name="custom_fields[<?= $i ?>][label]" value="<?= e($f['label']) ?>" placeholder="Örn: WordPress Kullanıcı Adı" style="min-width:150px"></td>
                            <td><input type="text" name="custom_fields[<?= $i ?>][field_key]" value="<?= e($f['field_key']) ?>" placeholder="wp_username"></td>
                            <td>
                                <select name="custom_fields[<?= $i ?>][field_type]">
                                    <?php foreach (['text'=>'Metin','textarea'=>'Uzun Metin','number'=>'Sayı','ip'=>'IP','url'=>'URL','email'=>'E-posta','phone'=>'Telefon','select'=>'Dropdown','radio'=>'Radyo','checkbox'=>'Onay Kutusu','file'=>'Dosya','image'=>'Görsel','password'=>'Şifre'] as $tk => $tl): ?>
                                        <option value="<?= $tk ?>" <?= ($f['field_type'] ?? 'text') === $tk ? 'selected' : '' ?>><?= $tl ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="custom_fields[<?= $i ?>][options]" value="<?= e($opts) ?>" placeholder="Seçenek1|Seçenek2|Seçenek3"></td>
                            <td>
                                <select name="custom_fields[<?= $i ?>][show_on]">
                                    <option value="order" <?= ($f['show_on'] ?? 'order') === 'order' ? 'selected' : '' ?>>Sipariş</option>
                                    <option value="profile" <?= ($f['show_on'] ?? '') === 'profile' ? 'selected' : '' ?>>Profil</option>
                                    <option value="admin_only" <?= ($f['show_on'] ?? '') === 'admin_only' ? 'selected' : '' ?>>Sadece Admin</option>
                                </select>
                            </td>
                            <td style="text-align:center"><input type="checkbox" name="custom_fields[<?= $i ?>][is_required]" value="1" <?= !empty($f['is_required']) ? 'checked' : '' ?>></td>
                            <td style="text-align:center"><input type="checkbox" name="custom_fields[<?= $i ?>][is_active]" value="1" <?= !empty($f['is_active']) ? 'checked' : '' ?>></td>
                            <td><button type="button" class="aho-btn aho-btn--sm aho-btn--ghost" onclick="this.closest('tr').remove()">✕</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <script>
        function ahoAddAddonRow() {
            var tbody = document.querySelector('#aho-addon-rows tbody');
            var i = tbody.querySelectorAll('tr').length;
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td><input type="text" name="addons['+i+'][name]" placeholder="Yeni ek paket" style="min-width:150px"></td>' +
                '<td><input type="text" name="addons['+i+'][description]"></td>' +
                '<td><input type="number" step="0.01" name="addons['+i+'][price]" value="0" style="width:90px"></td>' +
                '<td><select name="addons['+i+'][currency]"><option>TRY</option><option>USD</option><option>EUR</option><option>GBP</option></select></td>' +
                '<td><select name="addons['+i+'][period]"><option value="onetime">Tek Sefer</option><option value="monthly" selected>Aylık</option><option value="annually">Yıllık</option></select></td>' +
                '<td><select name="addons['+i+'][addon_type]"><option value="">—</option><option value="disk">Disk</option><option value="bandwidth">Trafik</option><option value="email">E-posta</option><option value="ssl">SSL</option><option value="backup">Yedekleme</option><option value="ip">Ek IP</option><option value="cdn">CDN</option><option value="other">Diğer</option></select></td>' +
                '<td style="text-align:center"><input type="checkbox" name="addons['+i+'][is_active]" value="1" checked></td>' +
                '<td><button type="button" class="aho-btn aho-btn--sm aho-btn--ghost" onclick="this.closest(\'tr\').remove()">✕</button></td>';
            tbody.appendChild(tr);
        }
        function ahoAddCfRow() {
            var tbody = document.querySelector('#aho-cf-rows tbody');
            var i = tbody.querySelectorAll('tr').length;
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td><input type="text" name="custom_fields['+i+'][label]" placeholder="Yeni özel alan" style="min-width:150px"></td>' +
                '<td><input type="text" name="custom_fields['+i+'][field_key]"></td>' +
                '<td><select name="custom_fields['+i+'][field_type]"><option value="text">Metin</option><option value="textarea">Uzun Metin</option><option value="number">Sayı</option><option value="ip">IP</option><option value="url">URL</option><option value="email">E-posta</option><option value="phone">Telefon</option><option value="select">Dropdown</option><option value="radio">Radyo</option><option value="checkbox">Onay Kutusu</option><option value="file">Dosya</option><option value="password">Şifre</option></select></td>' +
                '<td><input type="text" name="custom_fields['+i+'][options]" placeholder="Seçenek1|Seçenek2"></td>' +
                '<td><select name="custom_fields['+i+'][show_on]"><option value="order">Sipariş</option><option value="profile">Profil</option><option value="admin_only">Sadece Admin</option></select></td>' +
                '<td style="text-align:center"><input type="checkbox" name="custom_fields['+i+'][is_required]" value="1"></td>' +
                '<td style="text-align:center"><input type="checkbox" name="custom_fields['+i+'][is_active]" value="1" checked></td>' +
                '<td><button type="button" class="aho-btn aho-btn--sm aho-btn--ghost" onclick="this.closest(\'tr\').remove()">✕</button></td>';
            tbody.appendChild(tr);
        }
        </script>

        <!-- Paket Opsiyonları (Rapor 5.3) -->
        <?php if (!empty($product['id'])):
            $productOptions = \App\Modules\Product\Services\OptionService::forProduct((int)$product['id']);
        ?>
        <div class="aho-card" style="margin-bottom:var(--aho-space-4)">
            <h3 class="aho-card__title" style="margin-bottom:var(--aho-space-4);display:flex;justify-content:space-between;align-items:center">
                <span>🎛 Paket Opsiyonları</span>
                <a href="/admin/paket-opsiyonlari?product_id=<?= (int)$product['id'] ?>"
                   class="aho-btn aho-btn--sm aho-btn--outline" target="_blank">
                    + Opsiyon Ekle / Düzenle
                </a>
            </h3>
            <?php if (!$productOptions): ?>
                <p style="color:var(--aho-muted);margin:0">
                    Henüz opsiyon yok. Lokasyon, panel, PHP sürümü gibi çoktan seçmeli alanlar ekleyebilirsin.
                </p>
            <?php else: ?>
                <div class="aho-product-matrix">
                <table class="aho-table aho-product-config-table" style="margin-top:8px">
                    <thead>
                        <tr><th>Ad</th><th>Tip</th><th>Değer sayısı</th><th>Zorunlu</th><th>Durum</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productOptions as $po): ?>
                            <tr>
                                <td>
                                    <a href="/admin/paket-opsiyonlari/<?= (int)$po['id'] ?>/duzenle" target="_blank">
                                        <?= e($po['name']) ?>
                                    </a>
                                    <?php if ($po['product_id'] === null): ?>
                                        <span class="aho-badge">Genel</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($po['input_type']) ?></td>
                                <td><?= count($po['values']) ?></td>
                                <td><?= (int)$po['is_required'] === 1 ? '✓' : '—' ?></td>
                                <td><?= (int)$po['is_active'] === 1 ? '<span class="aho-badge aho-badge--success">Aktif</span>' : '<span class="aho-badge">Pasif</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <script>
        async function aiFillSeo() {
            const name = document.querySelector('[name="name"]')?.value?.trim();
            const desc = document.getElementById('longDesc')?.value || document.getElementById('shortDesc')?.value || '';
            if (!name) { alert('Önce ürün adını girin.'); return; }
            const btn = event.target;
            btn.disabled = true; const orig = btn.textContent; btn.textContent = '⏳';
            try {
                const r = await fetch('/admin/api/ai/seo', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':'<?= csrf_token() ?>','Accept':'application/json'},
                    body: '_csrf=<?= csrf_token() ?>&title=' + encodeURIComponent(name) + '&content=' + encodeURIComponent(desc)
                });
                const d = await r.json();
                if (!d.ok) { alert('Hata: ' + (d.error || '')); return; }
                document.getElementById('seoTitle').value = d.title || '';
                document.getElementById('seoDesc').value = d.description || '';
                document.getElementById('seoKeywords').value = d.keywords || '';
            } catch(e) { alert('İstek hatası: ' + e.message); }
            finally { btn.disabled = false; btn.textContent = orig; }
        }
        </script>

        <div class="aho-admin-form-actions">
            <a href="/admin/urun-merkezi" class="aho-btn aho-btn--ghost">İptal</a>
            <button type="submit" class="aho-btn aho-btn--primary">Kaydet</button>
        </div>
    </form>
</div>

<template id="ahoPriceRowTemplate">
    <div class="aho-price-row" data-price-row>
        <div class="aho-form-group" style="margin:0">
            <label class="aho-form-label">Periyot</label>
            <select name="prices[__i__][period]" class="aho-form-select">
                <?php foreach ($periods as $per): ?>
                    <option value="<?= $per ?>"><?= \App\Modules\Product\Services\PricingService::periodLabel($per) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="aho-form-group" style="margin:0">
            <label class="aho-form-label">Para Birimi</label>
            <select name="prices[__i__][source_currency]" class="aho-form-select">
                <?php foreach ($currencies as $c): ?><option value="<?= $c ?>"><?= $c ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="aho-form-group" style="margin:0">
            <label class="aho-form-label">Fiyat</label>
            <input type="number" step="0.01" name="prices[__i__][source_price]" class="aho-form-input" placeholder="99.90">
        </div>
        <div class="aho-form-group" style="margin:0">
            <label class="aho-form-check">
                <input type="checkbox" name="prices[__i__][is_active]" value="1" checked> Aktif
            </label>
        </div>
        <button type="button" class="aho-btn aho-btn--ghost aho-btn--sm" data-price-remove aria-label="Sil">🗑️</button>
    </div>
</template>

<style>
.aho-price-row {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr 1fr 100px 40px;
    gap: var(--aho-space-3);
    align-items: end;
    padding: var(--aho-space-3);
    background: var(--aho-color-bg-soft);
    border-radius: var(--aho-radius-md);
    margin-bottom: var(--aho-space-2);
}
</style>

<script>
(function () {
    let index = <?= count($existingPrices) ?>;
    const container = document.getElementById('ahoPricesContainer');
    const empty     = document.getElementById('ahoPricesEmpty');
    const addBtn    = document.getElementById('ahoAddPriceBtn');
    const tmpl      = document.getElementById('ahoPriceRowTemplate');

    addBtn.addEventListener('click', () => {
        if (empty) empty.remove();
        const html = tmpl.innerHTML.replace(/__i__/g, index++);
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        container.appendChild(wrap.firstElementChild);
    });

    container.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-price-remove]');
        if (!btn) return;
        btn.closest('[data-price-row]').remove();
    });
})();

(function () {
    const form = document.getElementById('ahoProductForm');
    if (!form) return;
    const tabs = Array.from(form.querySelectorAll('[data-product-tab]'));
    const cards = Array.from(form.children).filter(el => el.classList && el.classList.contains('aho-card'));
    const storageKey = 'ahoProductEditorTab';

    cards.forEach((card, index) => {
        card.classList.add('aho-product-tab-panel');
        card.dataset.productPanel = String(index);
    });

    function activate(index) {
        if (!cards[index]) index = 0;
        tabs.forEach(tab => tab.classList.toggle('is-active', Number(tab.dataset.productTab) === index));
        cards.forEach((card, cardIndex) => {
            card.classList.toggle('is-active', cardIndex === index);
            card.hidden = cardIndex !== index;
        });
        try { localStorage.setItem(storageKey, String(index)); } catch (e) {}
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => activate(Number(tab.dataset.productTab || 0)));
    });

    const saved = Number(localStorage.getItem(storageKey) || 0);
    activate(Number.isFinite(saved) ? saved : 0);
})();
</script>
<?php $view->endSection(); ?>
