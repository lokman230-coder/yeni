<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🖥️ Hosting Hesabı #<?= (int)$account['id'] ?></h1>
            <p><?= e($account['customer_email'] ?? '') ?></p>
        </div>
        <div class="aho-admin-page__actions">
            <a href="/admin/musteriler/<?= (int)$account['customer_id'] ?>#hosting" class="aho-btn aho-btn--ghost">← Müşteri Profili</a>
        </div>
    </div>

    <?php if ($error = flash('error')): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <form method="post" action="/admin/hosting-hesaplari/<?= (int)$account['id'] ?>/guncelle" class="aho-form">
        <?= csrf() ?>
        <div class="aho-card">
            <h3>Hesap Bilgileri</h3>
            <div class="aho-admin-form-row aho-admin-form-row--2">
                <div><label>Domain</label><input type="text" name="domain" class="aho-form-input" value="<?= e($account['domain'] ?? '') ?>"></div>
                <div><label>Kullanıcı Adı</label><input type="text" name="username" class="aho-form-input" value="<?= e($account['username'] ?? '') ?>"></div>
                <div>
                    <label>Ürün / Paket</label>
                    <select name="product_id" class="aho-form-select">
                        <option value="">— seçilmedi —</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= (int)($account['product_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?> (<?= e($p['type']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Sunucu</label>
                    <select name="server_id" class="aho-form-select">
                        <option value="">— seçilmedi —</option>
                        <?php foreach ($servers as $s): ?>
                            <option value="<?= (int)$s['id'] ?>" <?= (int)($account['server_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?> (<?= e($s['hostname']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Durum</label>
                    <select name="status" class="aho-form-select">
                        <?php foreach (['pending'=>'Kuruluyor','active'=>'Aktif','suspended'=>'Askıda','terminated'=>'Kapatıldı'] as $k=>$v): ?>
                            <option value="<?= $k ?>" <?= ($account['status'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label>Sonraki Ödeme Tarihi</label><input type="date" name="next_due_date" class="aho-form-input" value="<?= e($account['next_due_date'] ?? '') ?>"></div>
                <div style="grid-column:1/-1"><label>Notlar</label><textarea name="notes" rows="3" class="aho-form-textarea"><?= e($account['notes'] ?? '') ?></textarea></div>
            </div>
            <button type="submit" class="aho-btn aho-btn--primary" style="margin-top:14px">💾 Kaydet</button>
        </div>
    </form>
</div>
<?php $view->endSection(); ?>
