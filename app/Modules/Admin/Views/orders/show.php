<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div><h1>📦 Sipariş #<?= e($order['order_number']) ?></h1><p><?= e(date('d.m.Y H:i', strtotime((string)$order['created_at']))) ?></p></div>
        <div class="aho-admin-page__actions">
            <a href="/admin/siparisler" class="aho-btn aho-btn--ghost">← Liste</a>
        </div>
    </div>
    <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">
        <div>
            <div class="aho-card">
                <div class="aho-card__header"><h3>🛒 Kalemler</h3></div>
                <table class="aho-table">
                    <thead><tr><th>Ürün</th><th>Periyot</th><th>Adet</th><th>Domain</th><th>Birim</th><th>Toplam</th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td><strong><?= e($it['product_name']) ?></strong></td>
                                <td><?= e($it['period']) ?></td>
                                <td><?= (int)$it['quantity'] ?></td>
                                <td><?= e($it['domain_name'] ?: '—') ?></td>
                                <td><?= number_format((float)$it['unit_price'], 2) ?></td>
                                <td><strong><?= number_format((float)$it['line_total'], 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="aho-card" style="margin-top:16px">
                <div class="aho-card__header"><h3>🧾 Faturalar</h3></div>
                <table class="aho-table">
                    <thead><tr><th>#</th><th>Tutar</th><th>Durum</th><th>Vade</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($invoices as $inv): ?>
                            <tr>
                                <td>#<?= e($inv['invoice_number'] ?? $inv['id']) ?></td>
                                <td><?= number_format((float)$inv['total'], 2) ?></td>
                                <td><span class="aho-badge"><?= e($inv['status']) ?></span></td>
                                <td><?= $inv['due_date'] ? e(date('d.m.Y', strtotime((string)$inv['due_date']))) : '—' ?></td>
                                <td><a href="/admin/faturalar/<?= (int)$inv['id'] ?>/pdf" target="_blank" class="aho-btn aho-btn--sm aho-btn--outline">📄 PDF</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$invoices): ?><tr><td colspan="5" style="text-align:center;color:#6b7280;padding:12px">Fatura yok.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <div class="aho-card">
                <div class="aho-card__header"><h3>👤 Müşteri</h3></div>
                <div class="aho-card__body">
                    <p><strong><?= e(trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')) ?: '—') ?></strong></p>
                    <p><?= e($order['customer_email']) ?></p>
                    <?php if (!empty($order['phone'])): ?><p>📞 <?= e($order['phone']) ?></p><?php endif; ?>
                    <?php if (!empty($order['company'])): ?><p>🏢 <?= e($order['company']) ?></p><?php endif; ?>
                    <a href="/admin/musteriler/<?= (int)$order['customer_id'] ?>" class="aho-btn aho-btn--sm aho-btn--outline" style="margin-top:8px">Profile Git →</a>
                </div>
            </div>

            <div class="aho-card" style="margin-top:16px">
                <div class="aho-card__header"><h3>💰 Toplam</h3></div>
                <div class="aho-card__body" style="font-size:14px">
                    <div style="display:flex;justify-content:space-between"><span>Ara toplam:</span><strong><?= number_format((float)$order['subtotal'], 2) ?></strong></div>
                    <?php if ((float)$order['discount_total'] > 0): ?>
                        <div style="display:flex;justify-content:space-between;color:#059669"><span>İndirim<?= $order['coupon_code'] ? ' (' . e($order['coupon_code']) . ')' : '' ?>:</span><strong>-<?= number_format((float)$order['discount_total'], 2) ?></strong></div>
                    <?php endif; ?>
                    <div style="display:flex;justify-content:space-between"><span>Vergi:</span><strong><?= number_format((float)$order['tax_total'], 2) ?></strong></div>
                    <hr>
                    <div style="display:flex;justify-content:space-between;font-size:18px"><span>Toplam:</span><strong><?= number_format((float)$order['total'], 2) ?> <?= e($order['currency']) ?></strong></div>
                </div>
            </div>

            <div class="aho-card" style="margin-top:16px">
                <div class="aho-card__header"><h3>⚙️ Durum Yönetimi</h3></div>
                <div class="aho-card__body">
                    <p>Mevcut: <strong><?= e($order['status']) ?></strong></p>
                    <form method="post" action="/admin/siparisler/<?= (int)$order['id'] ?>/durum">
                        <?= csrf() ?>
                        <select name="status" style="width:100%;margin-bottom:8px">
                            <?php foreach (['pending'=>'⏳ Bekliyor','paid'=>'💰 Ödendi','processing'=>'⚙ İşleniyor','active'=>'✅ Aktif','failed'=>'✗ Başarısız','cancelled'=>'❌ İptal','refunded'=>'↩ İade'] as $k=>$v): ?>
                                <option value="<?= $k ?>" <?= $order['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="aho-btn aho-btn--primary" style="width:100%">Güncelle</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $view->endSection(); ?>
