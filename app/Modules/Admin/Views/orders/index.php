<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div><h1>📦 Siparişler</h1><p>Son 200 sipariş — filtre uygulayabilirsin.</p></div>
    </div>
    <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>

    <!-- Özet kartları -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px">
        <div class="aho-card" style="padding:16px"><div style="font-size:11px;color:#6b7280">30 GÜN TOPLAM</div><div style="font-size:24px;font-weight:700"><?= (int)($summary['total'] ?? 0) ?></div></div>
        <div class="aho-card" style="padding:16px"><div style="font-size:11px;color:#6b7280">BEKLİYOR</div><div style="font-size:24px;font-weight:700;color:#d97706"><?= (int)($summary['pending'] ?? 0) ?></div></div>
        <div class="aho-card" style="padding:16px"><div style="font-size:11px;color:#6b7280">ÖDENDİ + AKTİF</div><div style="font-size:24px;font-weight:700;color:#059669"><?= (int)($summary['paid'] ?? 0) + (int)($summary['active'] ?? 0) ?></div></div>
        <div class="aho-card" style="padding:16px"><div style="font-size:11px;color:#6b7280">30 GÜN GELİR</div><div style="font-size:20px;font-weight:700"><?= number_format((float)($summary['revenue'] ?? 0), 2) ?> TL</div></div>
    </div>

    <form method="get" class="aho-card" style="padding:12px;margin-bottom:16px;display:flex;gap:8px">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Sipariş no veya e-posta..." style="flex:1">
        <select name="status">
            <option value="">Tümü</option>
            <?php foreach (['pending'=>'Bekliyor','paid'=>'Ödendi','processing'=>'İşleniyor','active'=>'Aktif','failed'=>'Başarısız','cancelled'=>'İptal','refunded'=>'İade'] as $k=>$v): ?>
                <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
        <button class="aho-btn aho-btn--primary">Filtre</button>
    </form>

    <div class="aho-card">
        <table class="aho-table">
            <thead>
                <tr><th>#</th><th>Müşteri</th><th>Ödeme</th><th>Toplam</th><th>Durum</th><th>Tarih</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o):
                    $badge = match ($o['status']) {
                        'paid','active' => ['✓ ' . $o['status'], '#059669', '#d1fae5'],
                        'pending','processing' => ['⏳ ' . $o['status'], '#d97706', '#fef3c7'],
                        'failed','cancelled' => ['✗ ' . $o['status'], '#dc2626', '#fee2e2'],
                        'refunded' => ['↩ iade', '#6b7280', '#f3f4f6'],
                        default => [$o['status'], '#6b7280', '#f3f4f6'],
                    };
                ?>
                    <tr>
                        <td><strong><?= e($o['order_number']) ?></strong></td>
                        <td><?= e($o['customer_name'] ?: '—') ?><br><small style="color:#6b7280"><?= e($o['customer_email']) ?></small></td>
                        <td><?= e($o['payment_method'] ?: '—') ?></td>
                        <td><strong><?= number_format((float)$o['total'], 2) ?></strong> <?= e($o['currency']) ?></td>
                        <td><span style="padding:3px 10px;border-radius:10px;font-size:12px;color:<?= $badge[1] ?>;background:<?= $badge[2] ?>"><?= e($badge[0]) ?></span></td>
                        <td><?= e(date('d.m.Y H:i', strtotime((string)$o['created_at']))) ?></td>
                        <td><a href="/admin/siparisler/<?= (int)$o['id'] ?>" class="aho-btn aho-btn--sm aho-btn--outline">Detay</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$orders): ?><tr><td colspan="7" style="text-align:center;padding:24px;color:#6b7280">Sipariş yok.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $view->endSection(); ?>
