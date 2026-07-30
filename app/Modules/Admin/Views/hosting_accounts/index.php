<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🖥️ Hosting Hesapları</h1>
            <p>İçe aktarılan / paket-sunucu ataması eksik hesapları burada toplu düzeltebilirsin.</p>
        </div>
    </div>

    <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash('error')): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <div class="aho-card">
        <form method="get" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap">
            <div><label><input type="checkbox" name="unassigned" value="1" <?= $onlyUnassigned ? 'checked' : '' ?> onchange="this.form.submit()"> Sadece paket/sunucusu eksik olanlar</label></div>
            <div><input type="text" name="q" value="<?= e($q) ?>" class="aho-form-input" placeholder="Domain veya müşteri e-postası ara..."></div>
            <button class="aho-btn aho-btn--outline">Ara</button>
        </form>
    </div>

    <form method="post" action="/admin/hosting-hesaplari/toplu-ata" id="ahoBulkForm">
        <?= csrf() ?>
        <div class="aho-card" style="margin-top:16px;position:sticky;top:0;z-index:5">
            <h3>Seçilenlere Toplu Ata</h3>
            <div style="display:flex;gap:12px;align-items:end;flex-wrap:wrap">
                <div>
                    <label>Ürün / Paket</label>
                    <select name="product_id" class="aho-form-select">
                        <option value="">— değiştirme —</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?> (<?= e($p['type']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Sunucu</label>
                    <select name="server_id" class="aho-form-select">
                        <option value="">— değiştirme —</option>
                        <?php foreach ($servers as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?> (<?= e($s['hostname']) ?>)<?= empty($s['is_active']) ? ' — pasif' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="aho-btn aho-btn--primary" onclick="return confirm('Seçilen hesaplara uygulanacak. Emin misin?')">Seçilenlere Uygula</button>
                <span id="ahoSelCount" style="font-size:13px;color:var(--aho-color-ink-500)">0 hesap seçili</span>
            </div>
        </div>

        <div class="aho-card" style="margin-top:16px">
            <div class="aho-table-wrap"><table class="aho-table">
                <thead><tr>
                    <th><input type="checkbox" id="ahoSelAll"></th>
                    <th>#</th><th>Domain</th><th>Müşteri</th><th>Paket (mevcut)</th><th>Sunucu (mevcut)</th><th>Durum</th>
                </tr></thead>
                <tbody>
                <?php foreach ($accounts as $a): ?>
                    <tr>
                        <td><input type="checkbox" name="account_ids[]" value="<?= (int)$a['id'] ?>" class="aho-row-check"></td>
                        <td>#<?= (int)$a['id'] ?></td>
                        <td><?= e($a['domain'] ?: '—') ?></td>
                        <td><?= e(trim(($a['customer_first_name'] ?? '') . ' ' . ($a['customer_last_name'] ?? '')) ?: ($a['customer_email'] ?? '—')) ?></td>
                        <td><?= e($a['product_name'] ?? $a['package'] ?? '—') ?> <?= empty($a['product_id']) ? '<span class="aho-badge" style="background:#fee2e2;color:#dc2626">atanmamış</span>' : '' ?></td>
                        <td><?= e($a['server_name'] ?? '—') ?> <?= empty($a['server_id']) ? '<span class="aho-badge" style="background:#fee2e2;color:#dc2626">atanmamış</span>' : '' ?></td>
                        <td><span class="aho-badge"><?= e($a['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$accounts): ?><tr><td colspan="7" class="aho-empty-cell">Kayıt yok.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div>
    </form>
</div>
<script>
document.getElementById('ahoSelAll').addEventListener('change', function () {
    document.querySelectorAll('.aho-row-check').forEach(cb => cb.checked = this.checked);
    updateCount();
});
document.querySelectorAll('.aho-row-check').forEach(cb => cb.addEventListener('change', updateCount));
function updateCount() {
    document.getElementById('ahoSelCount').textContent = document.querySelectorAll('.aho-row-check:checked').length + ' hesap seçili';
}
</script>
<?php $view->endSection(); ?>
