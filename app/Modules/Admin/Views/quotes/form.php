<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$isEdit = $quote !== null;
$customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?: ($customer['email'] ?? '');
$action = $isEdit
    ? '/admin/teklifler/' . (int)$quote['id'] . '/guncelle'
    : '/admin/musteriler/' . (int)$customer['id'] . '/teklif-kaydet';
$items = $items ?: [['description' => '', 'quantity' => 1, 'unit_price' => '', 'tax_rate' => 0]];
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1><?= $isEdit ? '✏️ Teklif Düzenle — ' . e($quote['quote_number']) : '+ Yeni Teklif' ?></h1>
            <p>Müşteri: <strong><?= e($customerName) ?></strong> (<?= e($customer['email']) ?>)</p>
        </div>
        <div class="aho-admin-page__actions">
            <a href="/admin/musteriler/<?= (int)$customer['id'] ?>#teklif" class="aho-btn aho-btn--ghost">← Müşteri Profili</a>
        </div>
    </div>

    <?php if ($error = flash('error')): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <form method="post" action="<?= e($action) ?>" class="aho-form" id="ahoQuoteForm">
        <?= csrf() ?>
        <div class="aho-card">
            <div class="aho-card__header"><h3>📋 Teklif Bilgisi</h3></div>
            <div class="aho-card__body" style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:14px">
                <div>
                    <label>Konu *</label>
                    <input type="text" name="subject" required value="<?= e($quote['subject'] ?? '') ?>" placeholder="Örn: Kurumsal Hosting Paketi Teklifi">
                </div>
                <div>
                    <label>Geçerlilik Tarihi</label>
                    <input type="date" name="valid_until" value="<?= e($quote['valid_until'] ?? '') ?>">
                </div>
                <div>
                    <label>Para Birimi</label>
                    <select name="currency">
                        <?php foreach (['TRY','USD','EUR','GBP'] as $c): ?>
                            <option value="<?= $c ?>" <?= ($quote['currency'] ?? 'TRY') === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="aho-card" style="margin-top:16px">
            <div class="aho-card__header"><h3>🧾 Kalemler</h3></div>
            <div class="aho-card__body">
                <div id="ahoQuoteItems">
                    <?php foreach ($items as $it): ?>
                        <div class="aho-price-row" data-quote-row style="display:grid;grid-template-columns:3fr 1fr 1fr 1fr auto;gap:10px;align-items:end;margin-bottom:10px">
                            <div><label>Açıklama</label><input type="text" name="item_description[]" value="<?= e($it['description'] ?? '') ?>" placeholder="Ürün / hizmet açıklaması"></div>
                            <div><label>Adet</label><input type="number" min="1" name="item_quantity[]" value="<?= e((string)($it['quantity'] ?? 1)) ?>"></div>
                            <div><label>Birim Fiyat</label><input type="number" step="0.01" name="item_unit_price[]" value="<?= e((string)($it['unit_price'] ?? '')) ?>"></div>
                            <div><label>KDV %</label><input type="number" step="0.01" name="item_tax_rate[]" value="<?= e((string)($it['tax_rate'] ?? 0)) ?>"></div>
                            <button type="button" class="aho-btn aho-btn--ghost aho-btn--sm" data-quote-row-remove aria-label="Sil">🗑️</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="aho-btn aho-btn--outline aho-btn--sm" id="ahoQuoteAddRow">+ Kalem Ekle</button>
            </div>
        </div>

        <div class="aho-card" style="margin-top:16px">
            <div class="aho-card__header"><h3>📝 Notlar</h3></div>
            <div class="aho-card__body">
                <textarea name="notes" rows="3" placeholder="Müşteriye görünecek not (opsiyonel)"><?= e($quote['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="aho-admin-form-actions">
            <button type="submit" class="aho-btn aho-btn--primary">💾 <?= $isEdit ? 'Güncelle' : 'Teklifi Kaydet' ?></button>
        </div>
    </form>
</div>
<template id="ahoQuoteRowTpl">
    <div class="aho-price-row" data-quote-row style="display:grid;grid-template-columns:3fr 1fr 1fr 1fr auto;gap:10px;align-items:end;margin-bottom:10px">
        <div><label>Açıklama</label><input type="text" name="item_description[]" placeholder="Ürün / hizmet açıklaması"></div>
        <div><label>Adet</label><input type="number" min="1" name="item_quantity[]" value="1"></div>
        <div><label>Birim Fiyat</label><input type="number" step="0.01" name="item_unit_price[]"></div>
        <div><label>KDV %</label><input type="number" step="0.01" name="item_tax_rate[]" value="0"></div>
        <button type="button" class="aho-btn aho-btn--ghost aho-btn--sm" data-quote-row-remove aria-label="Sil">🗑️</button>
    </div>
</template>
<script>
(function () {
    const wrap = document.getElementById('ahoQuoteItems');
    const tpl = document.getElementById('ahoQuoteRowTpl');
    document.getElementById('ahoQuoteAddRow').addEventListener('click', () => {
        const node = tpl.content.cloneNode(true);
        wrap.appendChild(node);
    });
    wrap.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-quote-row-remove]');
        if (!btn) return;
        const rows = wrap.querySelectorAll('[data-quote-row]');
        if (rows.length > 1) btn.closest('[data-quote-row]').remove();
    });
})();
</script>
<?php $view->endSection(); ?>
