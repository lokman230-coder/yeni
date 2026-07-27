<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-customer-panel" style="padding:32px 0;background:#f9fafb;min-height:calc(100vh - 200px)">
    <div class="aho-container">
        <div style="display:grid;grid-template-columns:220px 1fr;gap:24px" class="aho-customer-layout">
            <?= $view->include('customer::_sidebar') ?>
            <div>
                <h1 style="margin:0 0 4px;font-size:24px">📦 Siparişlerim</h1>
                <p style="color:var(--aho-color-ink-600);margin:0 0 20px">Verdiğiniz tüm siparişler ve durumları.</p>

                <?php if (empty($orders)): ?>
                    <div class="aho-card" style="padding:60px;text-align:center">
                        <div style="font-size:56px">🛒</div>
                        <h3 style="margin:12px 0 8px">Henüz siparişiniz yok</h3>
                        <p style="color:var(--aho-color-ink-600);margin:0 0 20px">Hosting, domain veya diğer hizmetlerimizi keşfedin.</p>
                        <a href="/hosting" class="aho-btn aho-btn--primary">Hizmetleri Gör</a>
                    </div>
                <?php else: ?>
                    <div class="aho-card" style="padding:0;overflow:auto">
                        <table style="width:100%;border-collapse:collapse;font-size:14px">
                            <thead style="background:var(--aho-color-ink-50);text-align:left">
                                <tr>
                                    <th style="padding:12px 16px">Sipariş No</th>
                                    <th style="padding:12px 16px">Tarih</th>
                                    <th style="padding:12px 16px">Ödeme Yöntemi</th>
                                    <th style="padding:12px 16px;text-align:right">Tutar</th>
                                    <th style="padding:12px 16px;text-align:center">Durum</th>
                                    <th style="padding:12px 16px;text-align:right">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($orders as $o): ?>
                                <?php
                                $badge = match ($o['status']) {
                                    'pending' => ['⏳ Ödeme Bekliyor', '#d97706', '#fef3c7'],
                                    'paid'    => ['✅ Ödendi',         '#059669', '#d1fae5'],
                                    'active'  => ['✅ Aktif',          '#059669', '#d1fae5'],
                                    'failed'  => ['❌ Başarısız',       '#dc2626', '#fee2e2'],
                                    'cancelled'=> ['❌ İptal',          '#6b7280', '#f3f4f6'],
                                    'refunded'=> ['↩ İade',            '#6b7280', '#f3f4f6'],
                                    default   => [$o['status'],         '#6b7280', '#f3f4f6'],
                                };
                                ?>
                                <tr style="border-top:1px solid var(--aho-color-border)">
                                    <td style="padding:12px 16px;font-family:monospace;font-size:13px;font-weight:600">
                                        <?= e($o['order_number']) ?>
                                        <?php if (!empty($o['coupon_code'])): ?>
                                            <div style="font-size:11px;color:#059669;font-family:inherit;font-weight:600;margin-top:2px">🎟️ <?= e($o['coupon_code']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:12px 16px"><?= e(date('d.m.Y H:i', strtotime((string)$o['created_at']))) ?></td>
                                    <td style="padding:12px 16px;font-size:13px;color:var(--aho-color-ink-600)">
                                        <?= e(match ($o['payment_method']) {
                                            'paytr'         => '💳 PayTR',
                                            'iyzico'        => '💠 iyzico',
                                            'papara'        => '🟨 Papara',
                                            'bank_transfer' => '🏦 Havale',
                                            'balance'       => '💰 Bakiye',
                                            'manual'        => '✍️ Manuel',
                                            default         => $o['payment_method'],
                                        }) ?>
                                    </td>
                                    <td style="padding:12px 16px;text-align:right;font-weight:600"><?= number_format((float)$o['total'], 2, ',', '.') ?> <?= e($o['currency']) ?></td>
                                    <td style="padding:12px 16px;text-align:center">
                                        <span style="padding:3px 10px;font-size:12px;border-radius:12px;color:<?= $badge[1] ?>;background:<?= $badge[2] ?>">
                                            <?= e($badge[0]) ?>
                                        </span>
                                    </td>
                                    <td style="padding:12px 16px;text-align:right">
                                        <?php if ($o['status'] === 'pending'): ?>
                                            <a href="/odeme/paytr/<?= (int)$o['id'] ?>" class="aho-btn aho-btn--primary" style="padding:6px 12px;font-size:12px">Öde</a>
                                        <?php else: ?>
                                            <a href="/panel/siparis/<?= (int)$o['id'] ?>" style="color:var(--aho-color-primary-600);text-decoration:none;font-size:13px;font-weight:600">Detay →</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
