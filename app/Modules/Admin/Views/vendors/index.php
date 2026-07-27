<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div><h1>🏪 Vendorlar (Marketplace Satıcıları)</h1><p>3. parti satıcıları yönet, onayla, komisyonları izle.</p></div>
    </div>
    <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>

    <form method="get" class="aho-card" style="padding:12px;margin-bottom:16px;display:flex;gap:8px">
        <select name="status">
            <option value="">Tüm durumlar</option>
            <?php foreach (['pending'=>'⏳ Onay Bekleyen','approved'=>'✅ Onaylı','suspended'=>'⏸ Askıda','rejected'=>'❌ Reddedildi'] as $k=>$v): ?>
                <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
        <button class="aho-btn aho-btn--primary">Filtre</button>
    </form>

    <div class="aho-card">
        <table class="aho-table">
            <thead>
                <tr><th>#</th><th>Mağaza</th><th>Müşteri</th><th>Komisyon</th><th>Puan</th><th>Satış</th><th>Durum</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($vendors as $v):
                    $bg = match($v['status']) {
                        'approved' => ['#059669','#d1fae5'],
                        'suspended'=> ['#d97706','#fef3c7'],
                        'rejected' => ['#dc2626','#fee2e2'],
                        default    => ['#6b7280','#f3f4f6'],
                    };
                ?>
                    <tr>
                        <td>#<?= (int)$v['id'] ?></td>
                        <td><strong><?= e($v['shop_name']) ?></strong><br><small style="color:#6b7280"><?= e($v['shop_slug']) ?></small></td>
                        <td><?= e($v['customer_email']) ?></td>
                        <td>%<?= number_format((float)$v['commission_rate'], 1) ?></td>
                        <td>⭐ <?= number_format((float)$v['rating_avg'], 1) ?> (<?= (int)$v['rating_count'] ?>)</td>
                        <td><?= number_format((float)$v['total_sales'], 0) ?> TL</td>
                        <td><span style="padding:3px 10px;border-radius:10px;font-size:12px;color:<?= $bg[0] ?>;background:<?= $bg[1] ?>"><?= e($v['status']) ?></span></td>
                        <td><a href="/admin/vendorlar/<?= (int)$v['id'] ?>" class="aho-btn aho-btn--sm aho-btn--outline">Detay</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$vendors): ?><tr><td colspan="8" style="text-align:center;padding:24px;color:#6b7280">Vendor yok.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $view->endSection(); ?>
