<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div><h1>+ Yeni Lisans</h1><p>Manuel lisans oluştur (CodeCanyon satışı, özel müşteri vb.).</p></div>
        <div class="aho-admin-page__actions">
            <a href="/admin/lisanslar" class="aho-btn aho-btn--ghost">← Liste</a>
        </div>
    </div>

    <form method="post" action="/admin/lisanslar/kaydet" class="aho-form">
        <?= csrf() ?>
        <div class="aho-card">
            <div class="aho-card__body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div>
                    <label>Müşteri *</label>
                    <select name="customer_id" required>
                        <option value="">— Seçiniz —</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= e($c['email']) ?> (#<?= (int)$c['id'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Ürün (opsiyonel)</label>
                    <select name="product_id">
                        <option value="">— Yok —</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Ürün Adı (görünen) *</label>
                    <input type="text" name="product_name" required placeholder="Örn: Ahost Site Builder v2">
                </div>
                <div>
                    <label>Lisans Tipi *</label>
                    <select name="license_type" onchange="document.getElementById('maxD').style.display=this.value==='unlimited'?'none':'block'">
                        <option value="single_domain">Tek Domain</option>
                        <option value="multi_domain">Çoklu Domain</option>
                        <option value="unlimited">Sınırsız</option>
                        <option value="single_package">Tek Paket (Android)</option>
                        <option value="multi_package">Çoklu Paket</option>
                        <option value="trial">Deneme</option>
                    </select>
                </div>
                <div id="maxD">
                    <label>Maks Domain/Paket</label>
                    <input type="number" name="max_domains" value="1" min="1" max="9999">
                </div>
                <div>
                    <label>Bitiş Tarihi (boş = süresiz)</label>
                    <input type="date" name="expires_at">
                </div>
                <div>
                    <label>Kaynak</label>
                    <select name="source">
                        <option value="ahost">Ahost (kendi)</option>
                        <option value="codecanyon">CodeCanyon (Envato)</option>
                        <option value="manual">Manuel</option>
                    </select>
                </div>
                <div>
                    <label>Envato Purchase Code (opsiyonel)</label>
                    <input type="text" name="purchase_code" placeholder="12345678-1234-1234-1234-123456789012">
                </div>
                <div style="grid-column:1/-1">
                    <label>Not</label>
                    <textarea name="notes" rows="2" placeholder="İç not..."></textarea>
                </div>
            </div>
        </div>
        <div style="margin-top:16px;text-align:right">
            <button type="submit" class="aho-btn aho-btn--primary">🔑 Lisans Üret</button>
        </div>
    </form>
</div>
<?php $view->endSection(); ?>
