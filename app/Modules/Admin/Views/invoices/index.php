<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div><h1>🧾 Faturalar</h1><p>Ödeme durumu, havale onayı, PDF.</p></div>
    </div>
    <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px">
        <div class="aho-card" style="padding:16px"><div style="font-size:11px;color:#6b7280">30 GÜN TOPLAM</div><div style="font-size:24px;font-weight:700"><?= (int)($summary['total'] ?? 0) ?></div></div>
        <div class="aho-card" style="padding:16px"><div style="font-size:11px;color:#6b7280">ÖDENMEMİŞ</div><div style="font-size:24px;font-weight:700;color:#d97706"><?= (int)($summary['unpaid'] ?? 0) + (int)($summary['overdue'] ?? 0) ?></div></div>
        <div class="aho-card" style="padding:16px"><div style="font-size:11px;color:#6b7280">30 GÜN GELİR</div><div style="font-size:20px;font-weight:700;color:#059669"><?= number_format((float)($summary['revenue'] ?? 0), 2) ?> TL</div></div>
        <div class="aho-card" style="padding:16px"><div style="font-size:11px;color:#6b7280">TAHSİL EDİLECEK</div><div style="font-size:20px;font-weight:700;color:#dc2626"><?= number_format((float)($summary['outstanding'] ?? 0), 2) ?> TL</div></div>
    </div>

    <form method="get" class="aho-card" style="padding:12px;margin-bottom:16px;display:flex;gap:8px">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Fatura no veya e-posta..." style="flex:1">
        <select name="status">
            <option value="">Tümü</option>
            <?php foreach (['unpaid'=>'Ödenmemiş','paid'=>'Ödendi','overdue'=>'Vadesi geçti','partially_paid'=>'Kısmi','cancelled'=>'İptal','refunded'=>'İade'] as $k=>$v): ?>
                <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
        <button class="aho-btn aho-btn--primary">Filtre</button>
    </form>

    <div class="aho-card">
        <table class="aho-table">
            <thead>
                <tr><th>#</th><th>Müşteri</th><th>Toplam</th><th>Ödenen</th><th>Bakiye</th><th>Durum</th><th>Vade</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $i):
                    $bg = match($i['status']) {
                        'paid' => ['#059669','#d1fae5'],
                        'overdue' => ['#dc2626','#fee2e2'],
                        'partially_paid' => ['#0891b2','#cffafe'],
                        'cancelled','refunded' => ['#6b7280','#f3f4f6'],
                        default => ['#d97706','#fef3c7'],
                    };
                ?>
                    <tr>
                        <td><strong>#<?= e($i['invoice_number']) ?></strong></td>
                        <td><?= e($i['customer_name'] ?: '—') ?><br><small style="color:#6b7280"><?= e($i['customer_email']) ?></small></td>
                        <td><?= number_format((float)$i['total'], 2) ?> <?= e($i['currency']) ?></td>
                        <td><?= number_format((float)$i['paid_total'], 2) ?></td>
                        <td style="color:<?= (float)$i['balance'] > 0 ? '#dc2626' : '#059669' ?>"><?= number_format((float)$i['balance'], 2) ?></td>
                        <td><span style="padding:3px 10px;border-radius:10px;font-size:12px;color:<?= $bg[0] ?>;background:<?= $bg[1] ?>"><?= e($i['status']) ?></span></td>
                        <td><?= $i['due_date'] ? e(date('d.m.Y', strtotime((string)$i['due_date']))) : '—' ?></td>
                        <td><a href="/admin/faturalar/<?= (int)$i['id'] ?>" class="aho-btn aho-btn--sm aho-btn--outline">Detay</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$invoices): ?><tr><td colspan="8" style="text-align:center;padding:24px;color:#6b7280">Fatura yok.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $view->endSection(); ?>
