<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$success = flash('success');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🎛 Paket Opsiyonları</h1>
            <p>Lokasyon, panel, OS, PHP sürümü, tema, lisans süresi gibi çoktan seçmeli opsiyonlar.</p>
        </div>
        <div class="aho-admin-page__actions">
            <a href="/admin/paket-opsiyonlari/yeni" class="aho-btn aho-btn--primary">+ Yeni Opsiyon</a>
        </div>
    </div>

    <?php if ($success): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>

    <div class="aho-card">
        <form method="get" style="padding:12px 16px;border-bottom:1px solid var(--aho-border);display:flex;gap:8px;align-items:center">
            <label>Ürün filtresi:</label>
            <select name="product_id" onchange="this.form.submit()">
                <option value="0">— Genel (tüm ürünler) + hepsi —</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= (int)$productId === (int)$p['id'] ? 'selected' : '' ?>>
                        <?= e($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <table class="aho-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ad</th>
                    <th>Slug</th>
                    <th>Tip</th>
                    <th>Ürün</th>
                    <th>Zorunlu</th>
                    <th>Durum</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$options): ?>
                    <tr><td colspan="8" style="text-align:center;padding:24px;color:var(--aho-muted)">Henüz opsiyon yok.</td></tr>
                <?php endif; ?>
                <?php foreach ($options as $o): ?>
                    <tr>
                        <td>#<?= (int)$o['id'] ?></td>
                        <td><strong><?= e($o['name']) ?></strong></td>
                        <td><code><?= e($o['slug']) ?></code></td>
                        <td><?= e($o['input_type']) ?></td>
                        <td><?= $o['product_id'] ? '#' . (int)$o['product_id'] : '<span class="aho-badge">Genel</span>' ?></td>
                        <td><?= $o['is_required'] ? '✓' : '—' ?></td>
                        <td><?= $o['is_active'] ? '<span class="aho-badge aho-badge--success">Aktif</span>' : '<span class="aho-badge">Pasif</span>' ?></td>
                        <td>
                            <a href="/admin/paket-opsiyonlari/<?= (int)$o['id'] ?>/duzenle" class="aho-btn aho-btn--sm aho-btn--outline">Düzenle</a>
                            <form method="post" action="/admin/paket-opsiyonlari/<?= (int)$o['id'] ?>/sil" style="display:inline" onsubmit="return confirm('Silinsin mi?')">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <button class="aho-btn aho-btn--sm aho-btn--danger">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $view->endSection(); ?>
