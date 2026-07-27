<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div><h1>🏪 <?= e($vendor['shop_name']) ?></h1><p><?= e($vendor['customer_email']) ?></p></div>
        <div class="aho-admin-page__actions">
            <a href="/admin/vendorlar" class="aho-btn aho-btn--ghost">← Liste</a>
            <?php if ($vendor['status'] === 'pending'): ?>
                <form method="post" action="/admin/vendorlar/<?= (int)$vendor['id'] ?>/onayla" style="display:inline">
                    <?= csrf() ?>
                    <button class="aho-btn aho-btn--primary">✅ Onayla</button>
                </form>
            <?php endif; ?>
            <?php if ($vendor['status'] === 'approved'): ?>
                <form method="post" action="/admin/vendorlar/<?= (int)$vendor['id'] ?>/askiya-al" style="display:inline">
                    <?= csrf() ?>
                    <button class="aho-btn aho-btn--warning">⏸ Askıya Al</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:16px">
        <div>
            <div class="aho-card">
                <div class="aho-card__header"><h3>📊 Özet</h3></div>
                <div class="aho-card__body" style="font-size:13px">
                    <p><strong>Toplam satış:</strong> <?= number_format((float)$vendor['total_sales'], 2) ?> TL</p>
                    <p><strong>Ödenmiş komisyon:</strong> <?= number_format((float)$vendor['total_commission_paid'], 2) ?> TL</p>
                    <p><strong>Müsait bakiye:</strong> <strong style="color:#059669;font-size:16px"><?= number_format((float)$availableBalance, 2) ?> TL</strong></p>
                    <p><strong>Komisyon oranı:</strong> %<?= number_format((float)$vendor['commission_rate'], 1) ?></p>
                    <p><strong>Puan:</strong> ⭐ <?= number_format((float)$vendor['rating_avg'], 1) ?> / 5 (<?= (int)$vendor['rating_count'] ?> değerlendirme)</p>
                    <hr>
                    <p><strong>İletişim:</strong> <?= e($vendor['contact_email']) ?></p>
                    <p><strong>Telefon:</strong> <?= e($vendor['contact_phone'] ?: '—') ?></p>
                    <p><strong>Website:</strong> <?= e($vendor['website'] ?: '—') ?></p>
                    <p><strong>IBAN:</strong> <?= e($vendor['iban'] ?: 'Tanımlanmadı') ?></p>
                    <p><strong>Vergi No:</strong> <?= e($vendor['tax_id'] ?: '—') ?></p>
                </div>
            </div>
        </div>

        <div>
            <div class="aho-card">
                <div class="aho-card__header"><h3>🛒 İlanlar (<?= count($listings) ?>)</h3></div>
                <table class="aho-table">
                    <thead><tr><th>#</th><th>Başlık</th><th>Fiyat</th><th>Durum</th></tr></thead>
                    <tbody>
                        <?php foreach ($listings as $l): ?>
                            <tr>
                                <td>#<?= (int)$l['id'] ?></td>
                                <td><?= e($l['title']) ?></td>
                                <td><?= number_format((float)$l['price'], 2) ?> <?= e($l['currency']) ?></td>
                                <td><span class="aho-badge"><?= e($l['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$listings): ?><tr><td colspan="4" style="text-align:center;color:#6b7280;padding:12px">İlan yok.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="aho-card" style="margin-top:16px">
                <div class="aho-card__header"><h3>💰 Kazançlar (<?= count($earnings) ?>)</h3></div>
                <table class="aho-table">
                    <thead><tr><th>Tarih</th><th>Brüt</th><th>Komisyon</th><th>Net</th><th>Durum</th></tr></thead>
                    <tbody>
                        <?php foreach ($earnings as $e): ?>
                            <tr>
                                <td style="font-size:12px"><?= e(date('d.m.Y', strtotime((string)$e['created_at']))) ?></td>
                                <td><?= number_format((float)$e['gross_amount'], 2) ?></td>
                                <td style="color:#dc2626">-<?= number_format((float)$e['commission_amount'], 2) ?></td>
                                <td style="color:#059669;font-weight:600"><?= number_format((float)$e['net_earnings'], 2) ?></td>
                                <td><span class="aho-badge"><?= e($e['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$earnings): ?><tr><td colspan="5" style="text-align:center;color:#6b7280;padding:12px">Henüz satış yok.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="aho-card" style="margin-top:16px">
                <div class="aho-card__header"><h3>🏦 Payout Talepleri (<?= count($payouts) ?>)</h3></div>
                <table class="aho-table">
                    <thead><tr><th>Tarih</th><th>Tutar</th><th>IBAN</th><th>Durum</th></tr></thead>
                    <tbody>
                        <?php foreach ($payouts as $p): ?>
                            <tr>
                                <td><?= e(date('d.m.Y', strtotime((string)$p['created_at']))) ?></td>
                                <td><strong><?= number_format((float)$p['amount'], 2) ?> <?= e($p['currency']) ?></strong></td>
                                <td><code style="font-size:11px"><?= e($p['iban']) ?></code></td>
                                <td><span class="aho-badge"><?= e($p['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$payouts): ?><tr><td colspan="4" style="text-align:center;color:#6b7280;padding:12px">Talep yok.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $view->endSection(); ?>
