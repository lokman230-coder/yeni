<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$reqDocs = $tld['required_documents_json'] ? json_decode((string)$tld['required_documents_json'], true) : [];
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div><h1>🌐 .<?= e($tld['tld']) ?> Ayarları</h1></div>
        <div class="aho-admin-page__actions"><a href="/admin/tld-yonetimi" class="aho-btn aho-btn--ghost">← Liste</a></div>
    </div>

    <form method="post" action="/admin/tld-yonetimi/<?= (int)$tld['id'] ?>/guncelle" class="aho-form">
        <?= csrf() ?>
        <div class="aho-card">
            <div class="aho-card__header"><h3>💰 Fiyatlandırma</h3></div>
            <div class="aho-card__body" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px">
                <div>
                    <label>Registrar Maliyet Register (TL)</label>
                    <input type="number" step="0.01" name="cost_register" value="<?= (float)($pricing['register_price'] ?? 0) ?>">
                </div>
                <div>
                    <label>Yenileme Maliyeti (TL)</label>
                    <input type="number" step="0.01" name="cost_renew" value="<?= (float)($pricing['renew_price'] ?? 0) ?>">
                </div>
                <div>
                    <label>Kar Marjı Tipi</label>
                    <select name="markup_type">
                        <option value="percent" <?= $tld['markup_type'] === 'percent' ? 'selected' : '' ?>>Yüzde (%)</option>
                        <option value="fixed" <?= $tld['markup_type'] === 'fixed' ? 'selected' : '' ?>>Sabit (TL)</option>
                    </select>
                </div>
                <div>
                    <label>Kar Marjı Değeri</label>
                    <input type="number" step="0.01" name="markup_value" value="<?= (float)$tld['markup_value'] ?>">
                </div>
                <div>
                    <label>Minimum Satış Fiyatı (opsiyonel)</label>
                    <input type="number" step="0.01" name="min_price" value="<?= (float)($tld['min_price'] ?? 0) ?>">
                </div>
            </div>
        </div>

        <div class="aho-card" style="margin-top:16px">
            <div class="aho-card__header"><h3>📄 Belge Gereksinimi</h3></div>
            <div class="aho-card__body">
                <label style="display:flex;gap:8px;align-items:center;margin-bottom:12px">
                    <input type="checkbox" name="requires_documents" value="1" <?= (int)$tld['requires_documents'] ? 'checked' : '' ?>>
                    <span><strong>Belge iste</strong> (örn: .com.tr için TCKN + vergi belgesi)</span>
                </label>
                <label>Gerekli Belgeler (virgülle ayır)</label>
                <input type="text" name="required_documents" value="<?= e(implode(',', $reqDocs)) ?>" placeholder="tckn,tax_id,trademark_cert,id_card,company_reg">
                <small style="color:#6b7280">Seçenekler: tckn, tax_id, trademark_cert, id_card, company_reg, domain_owner_doc</small>
            </div>
        </div>

        <div class="aho-card" style="margin-top:16px">
            <div class="aho-card__header"><h3>⚙️ Genel Ayarlar</h3></div>
            <div class="aho-card__body" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:14px">
                <label><input type="checkbox" name="allow_transfer" value="1" <?= (int)$tld['allow_transfer'] ? 'checked' : '' ?>> Transfer İzin</label>
                <label><input type="checkbox" name="allow_backorder" value="1" <?= (int)$tld['allow_backorder'] ? 'checked' : '' ?>> Backorder</label>
                <label><input type="checkbox" name="is_popular" value="1" <?= (int)$tld['is_popular'] ? 'checked' : '' ?>> Popüler (öne çıkar)</label>
                <label><input type="checkbox" name="is_active" value="1" <?= (int)$tld['is_active'] ? 'checked' : '' ?>> Aktif (satışta)</label>
            </div>
        </div>

        <div style="margin-top:16px;text-align:right">
            <button type="submit" class="aho-btn aho-btn--primary">💾 Kaydet</button>
        </div>
    </form>
</div>
<?php $view->endSection(); ?>
