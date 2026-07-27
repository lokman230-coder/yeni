<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div><h1>🔑 Lisanslar</h1><p>Script/uygulama satış lisansları — CodeCanyon uyumlu.</p></div>
        <div class="aho-admin-page__actions">
            <a href="/admin/lisanslar/yeni" class="aho-btn aho-btn--primary">+ Yeni Lisans</a>
        </div>
    </div>
    <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>

    <form method="get" class="aho-card" style="padding:12px;margin-bottom:16px;display:flex;gap:8px">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Anahtar, e-posta, ürün..." style="flex:1">
        <select name="status">
            <option value="">Tüm durumlar</option>
            <?php foreach (['active'=>'Aktif','suspended'=>'Askıda','expired'=>'Süresi doldu','revoked'=>'İptal','pending'=>'Beklemede'] as $k=>$v): ?>
                <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
        <button class="aho-btn aho-btn--primary">Filtre</button>
    </form>

    <div class="aho-card">
        <table class="aho-table">
            <thead>
                <tr><th>#</th><th>Anahtar</th><th>Ürün</th><th>Müşteri</th><th>Tip</th><th>Kullanım</th><th>Süre</th><th>Durum</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($licenses as $l):
                    $bg = match($l['status']) {
                        'active'  => ['#059669','#d1fae5'],
                        'expired' => ['#dc2626','#fee2e2'],
                        'revoked' => ['#6b7280','#f3f4f6'],
                        'suspended' => ['#d97706','#fef3c7'],
                        default   => ['#6b7280','#f3f4f6'],
                    };
                    $expiryLabel = $l['expires_at'] ? date('d.m.Y', strtotime((string)$l['expires_at'])) : 'Süresiz';
                    $usage = (int)$l['active_count'] . ($l['license_type'] === 'unlimited' ? ' / ∞' : ' / ' . (int)$l['max_domains']);
                ?>
                    <tr>
                        <td>#<?= (int)$l['id'] ?></td>
                        <td><code style="font-size:12px"><?= e($l['license_key']) ?></code></td>
                        <td><?= e($l['product_name']) ?></td>
                        <td><?= e($l['customer_email'] ?: '—') ?></td>
                        <td><span class="aho-badge"><?= e($l['license_type']) ?></span></td>
                        <td><?= $usage ?></td>
                        <td><?= $expiryLabel ?></td>
                        <td><span style="padding:3px 10px;border-radius:10px;font-size:12px;color:<?= $bg[0] ?>;background:<?= $bg[1] ?>"><?= e($l['status']) ?></span></td>
                        <td><a href="/admin/lisanslar/<?= (int)$l['id'] ?>" class="aho-btn aho-btn--sm aho-btn--outline">Detay</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$licenses): ?><tr><td colspan="9" style="text-align:center;padding:24px;color:#6b7280">Lisans yok.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $view->endSection(); ?>
