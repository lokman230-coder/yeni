<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div><h1>🌐 TLD Yönetimi</h1><p>Domain uzantı fiyatları, kar marjı, belge gereksinimi.</p></div>
        <div class="aho-admin-page__actions">
            <form method="post" action="/admin/tld-yonetimi/sync" style="display:inline">
                <?= csrf() ?>
                <button class="aho-btn aho-btn--outline">🔄 Registrar'dan Fiyat Çek</button>
            </form>
        </div>
    </div>
    <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash('error')): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <div class="aho-card">
        <table class="aho-table">
            <thead>
                <tr>
                    <th>TLD</th><th>Maliyet</th><th>Markup</th><th>Satış (Register)</th>
                    <th>Belge</th><th>Popüler</th><th>Durum</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tlds as $t):
                    $cost = $t['cost_price']['register_price'] ?? 0;
                    $sale = $t['sale_price']['register'];
                    $margin = $cost > 0 ? round(($sale - $cost) / $cost * 100, 1) : 0;
                ?>
                    <tr>
                        <td><strong>.<?= e($t['tld']) ?></strong> <?php if ($t['is_popular']): ?><span style="background:#f59e0b;color:#fff;padding:1px 6px;border-radius:8px;font-size:10px;margin-left:4px">POPÜLER</span><?php endif; ?></td>
                        <td><?= number_format($cost, 2) ?> ₺</td>
                        <td><?= e($t['sale_price']['markup']) ?></td>
                        <td style="font-weight:700;color:#059669"><?= number_format($sale, 2) ?> ₺ <small style="color:#6b7280">(+%<?= $margin ?>)</small></td>
                        <td><?= (int)$t['requires_documents'] ? '📄 <span style="color:#dc2626">Belge</span>' : '—' ?></td>
                        <td><?= (int)$t['is_popular'] ? '⭐' : '—' ?></td>
                        <td>
                            <?php if ((int)$t['is_active']): ?>
                                <span style="color:#059669">✓ Aktif</span>
                            <?php else: ?>
                                <span style="color:#dc2626">✗ Pasif</span>
                            <?php endif; ?>
                        </td>
                        <td><a href="/admin/tld-yonetimi/<?= (int)$t['id'] ?>/duzenle" class="aho-btn aho-btn--sm aho-btn--outline">Düzenle</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $view->endSection(); ?>
