<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div><h1>💳 Ödemeler</h1><p>Tüm gateway + havale + manuel ödemeler.</p></div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px">
        <div class="aho-card" style="padding:16px"><div style="font-size:11px;color:#6b7280">30 GÜN TOPLAM</div><div style="font-size:24px;font-weight:700"><?= (int)($summary['total'] ?? 0) ?></div></div>
        <div class="aho-card" style="padding:16px"><div style="font-size:11px;color:#6b7280">BAŞARILI TAHSİL</div><div style="font-size:20px;font-weight:700;color:#059669"><?= number_format((float)($summary['total_ok'] ?? 0), 2) ?> TL</div></div>
        <div class="aho-card" style="padding:16px"><div style="font-size:11px;color:#6b7280">HAVALE / EFT</div><div style="font-size:24px;font-weight:700"><?= (int)($summary['bank_transfers'] ?? 0) ?></div></div>
        <div class="aho-card" style="padding:16px"><div style="font-size:11px;color:#6b7280">PAYTR</div><div style="font-size:24px;font-weight:700"><?= (int)($summary['paytr'] ?? 0) ?></div></div>
    </div>

    <form method="get" class="aho-card" style="padding:12px;margin-bottom:16px;display:flex;gap:8px">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="TX ID, e-posta, fatura no..." style="flex:1">
        <select name="method">
            <option value="">Tüm yöntemler</option>
            <?php foreach (['paytr','iyzico','papara','shopier','bank_transfer','balance','cash','check'] as $m): ?>
                <option value="<?= $m ?>" <?= $method === $m ? 'selected' : '' ?>><?= $m ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">Tüm durumlar</option>
            <?php foreach (['pending'=>'Bekliyor','success'=>'Başarılı','failed'=>'Başarısız','refunded'=>'İade'] as $k=>$v): ?>
                <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
        <button class="aho-btn aho-btn--primary">Filtre</button>
    </form>

    <div class="aho-card">
        <table class="aho-table">
            <thead>
                <tr><th>#</th><th>Müşteri</th><th>Fatura</th><th>Yöntem</th><th>Tutar</th><th>Durum</th><th>TX ID</th><th>Tarih</th></tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $p):
                    $bg = match($p['status']) {
                        'success' => ['#059669','#d1fae5'],
                        'failed'  => ['#dc2626','#fee2e2'],
                        'refunded'=> ['#6b7280','#f3f4f6'],
                        default   => ['#d97706','#fef3c7'],
                    };
                ?>
                    <tr>
                        <td>#<?= (int)$p['id'] ?></td>
                        <td><?= e($p['customer_name'] ?: $p['customer_email']) ?></td>
                        <td>
                            <?php if ($p['invoice_id']): ?>
                                <a href="/admin/faturalar/<?= (int)$p['invoice_id'] ?>">#<?= e($p['invoice_number']) ?></a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td><?= e($p['method']) ?></td>
                        <td><strong><?= number_format((float)$p['amount'], 2) ?></strong> <?= e($p['currency']) ?></td>
                        <td><span style="padding:3px 10px;border-radius:10px;font-size:12px;color:<?= $bg[0] ?>;background:<?= $bg[1] ?>"><?= e($p['status']) ?></span></td>
                        <td><code style="font-size:11px"><?= e($p['gateway_transaction_id'] ?: '—') ?></code></td>
                        <td><?= e(date('d.m.Y H:i', strtotime((string)$p['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$payments): ?><tr><td colspan="8" style="text-align:center;padding:24px;color:#6b7280">Ödeme yok.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $view->endSection(); ?>
