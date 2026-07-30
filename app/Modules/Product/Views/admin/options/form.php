<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$isEdit = $option !== null;
$action = $isEdit
    ? '/admin/paket-opsiyonlari/' . (int)$option['id'] . '/guncelle'
    : '/admin/paket-opsiyonlari/kaydet';
$values = $option['values'] ?? [];
// En az 3 boş satır göster
while (count($values) < 3) {
    $values[] = ['id' => 0, 'label' => '', 'value_key' => '', 'price_delta' => 0, 'currency' => 'TRY', 'period' => 'monthly', 'is_default' => 0, 'is_active' => 1, 'sort_order' => 0];
}
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1><?= $isEdit ? '✏️ ' . e($option['name']) : '+ Yeni Paket Opsiyonu' ?></h1>
            <p>Örnek: Lokasyon, Panel, İşletim Sistemi, PHP Sürümü, Tema, Lisans Süresi</p>
        </div>
        <div class="aho-admin-page__actions">
            <a href="/admin/paket-opsiyonlari" class="aho-btn aho-btn--ghost">← Geri</a>
        </div>
    </div>

    <form method="post" action="<?= e($action) ?>" class="aho-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div class="aho-card">
            <div class="aho-card__body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div>
                    <label>Opsiyon Adı *</label>
                    <input type="text" name="name" required value="<?= e($option['name'] ?? '') ?>" placeholder="Lokasyon">
                </div>
                <div>
                    <label>Slug</label>
                    <input type="text" name="slug" value="<?= e($option['slug'] ?? '') ?>" placeholder="lokasyon (boş bırakılırsa otomatik)">
                </div>
                <div>
                    <label>Bağlı Ürün</label>
                    <select name="product_id">
                        <option value="">— Genel (tüm ürünlere uygulanabilir) —</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= isset($option['product_id']) && (int)$option['product_id'] === (int)$p['id'] ? 'selected' : '' ?>>
                                <?= e($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Giriş Tipi</label>
                    <select name="input_type">
                        <?php foreach (['select' => 'Açılır liste (Select)', 'radio' => 'Radyo düğmesi', 'checkbox' => 'Çoklu seçim'] as $k => $v): ?>
                            <option value="<?= $k ?>" <?= ($option['input_type'] ?? 'select') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="grid-column:1/-1">
                    <label>Açıklama (opsiyonel)</label>
                    <textarea name="description" rows="2" placeholder="Bu opsiyon ne için?"><?= e($option['description'] ?? '') ?></textarea>
                </div>
                <div style="display:flex;gap:16px;align-items:center">
                    <label><input type="checkbox" name="is_required" value="1" <?= !empty($option['is_required']) ? 'checked' : (!$isEdit ? 'checked' : '') ?>> Zorunlu</label>
                    <label><input type="checkbox" name="is_active" value="1" <?= !$isEdit || !empty($option['is_active']) ? 'checked' : '' ?>> Aktif</label>
                    <label>Sıra: <input type="number" name="sort_order" value="<?= (int)($option['sort_order'] ?? 0) ?>" style="width:80px"></label>
                </div>
            </div>
        </div>

        <div class="aho-card" style="margin-top:16px">
            <div class="aho-card__header">
                <h3>Değerler</h3>
                <button type="button" class="aho-btn aho-btn--sm aho-btn--outline" onclick="ahoAddOptionRow()">+ Satır Ekle</button>
            </div>
            <div class="aho-card__body">
                <table class="aho-table" id="aho-option-values">
                    <thead>
                        <tr>
                            <th>Etiket *</th>
                            <th>Anahtar</th>
                            <th>Fiyat Farkı</th>
                            <th>Para</th>
                            <th>Periyot</th>
                            <th>Varsayılan</th>
                            <th>Aktif</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($values as $i => $v): ?>
                            <tr>
                                <td>
                                    <input type="hidden" name="values[<?= $i ?>][id]" value="<?= (int)($v['id'] ?? 0) ?>">
                                    <input type="text" name="values[<?= $i ?>][label]" value="<?= e($v['label']) ?>" placeholder="İstanbul">
                                </td>
                                <td><input type="text" name="values[<?= $i ?>][value_key]" value="<?= e($v['value_key']) ?>" placeholder="istanbul"></td>
                                <td><input type="number" step="0.0001" name="values[<?= $i ?>][price_delta]" value="<?= (float)$v['price_delta'] ?>" style="width:100px"></td>
                                <td>
                                    <select name="values[<?= $i ?>][currency]">
                                        <?php foreach (['TRY','USD','EUR','GBP'] as $c): ?>
                                            <option value="<?= $c ?>" <?= ($v['currency'] ?? 'TRY') === $c ? 'selected' : '' ?>><?= $c ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="values[<?= $i ?>][period]">
                                        <?php foreach (['onetime' => 'Tek Sefer', 'monthly' => 'Aylık', 'quarterly' => '3 Ay', 'semiannually' => '6 Ay', 'annually' => 'Yıllık', 'biennially' => '2 Yıl', 'triennially' => '3 Yıl'] as $pk => $pl): ?>
                                            <option value="<?= $pk ?>" <?= ($v['period'] ?? 'monthly') === $pk ? 'selected' : '' ?>><?= $pl ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td style="text-align:center"><input type="checkbox" name="values[<?= $i ?>][is_default]" value="1" <?= !empty($v['is_default']) ? 'checked' : '' ?>></td>
                                <td style="text-align:center"><input type="checkbox" name="values[<?= $i ?>][is_active]" value="1" <?= !empty($v['is_active']) ? 'checked' : '' ?>></td>
                                <td><button type="button" class="aho-btn aho-btn--sm aho-btn--ghost" onclick="this.closest('tr').remove()">✕</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="color:var(--aho-muted);font-size:13px;margin-top:8px">
                    💡 Fiyat farkı 0 = ücretsiz seçenek. Boş etiketli satırlar kaydedilmez.
                </p>
            </div>
        </div>

        <div style="margin-top:16px;display:flex;gap:8px">
            <button type="submit" class="aho-btn aho-btn--primary">💾 Kaydet</button>
            <a href="/admin/paket-opsiyonlari" class="aho-btn aho-btn--ghost">İptal</a>
        </div>
    </form>
</div>

<script>
function ahoAddOptionRow() {
    var tbody = document.querySelector('#aho-option-values tbody');
    var i = tbody.querySelectorAll('tr').length;
    var tr = document.createElement('tr');
    tr.innerHTML =
        '<td><input type="hidden" name="values['+i+'][id]" value="0"><input type="text" name="values['+i+'][label]" placeholder="Yeni değer"></td>' +
        '<td><input type="text" name="values['+i+'][value_key]"></td>' +
        '<td><input type="number" step="0.0001" name="values['+i+'][price_delta]" value="0" style="width:100px"></td>' +
        '<td><select name="values['+i+'][currency]"><option>TRY</option><option>USD</option><option>EUR</option><option>GBP</option></select></td>' +
        '<td><select name="values['+i+'][period]"><option value="onetime">Tek Sefer</option><option value="monthly" selected>Aylık</option><option value="quarterly">3 Ay</option><option value="semiannually">6 Ay</option><option value="annually">Yıllık</option><option value="biennially">2 Yıl</option><option value="triennially">3 Yıl</option></select></td>' +
        '<td style="text-align:center"><input type="checkbox" name="values['+i+'][is_default]" value="1"></td>' +
        '<td style="text-align:center"><input type="checkbox" name="values['+i+'][is_active]" value="1" checked></td>' +
        '<td><button type="button" class="aho-btn aho-btn--sm aho-btn--ghost" onclick="this.closest(\'tr\').remove()">✕</button></td>';
    tbody.appendChild(tr);
}
</script>
<?php $view->endSection(); ?>
