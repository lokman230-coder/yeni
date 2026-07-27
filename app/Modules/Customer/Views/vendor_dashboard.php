<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');

$statusBadge = match($vendor['status']) {
    'approved'  => ['✅ Onaylı', '#059669', '#d1fae5'],
    'pending'   => ['⏳ Onay Bekliyor', '#d97706', '#fef3c7'],
    'suspended' => ['⏸ Askıda', '#d97706', '#fef3c7'],
    'rejected'  => ['❌ Reddedildi', '#dc2626', '#fee2e2'],
    default     => [$vendor['status'], '#6b7280', '#f3f4f6'],
};
?>
<section class="aho-customer-panel" style="padding:32px 0;background:#f9fafb;min-height:calc(100vh - 200px)">
    <div class="aho-container">
        <div style="display:grid;grid-template-columns:220px 1fr;gap:24px" class="aho-customer-layout">
            <?= $view->include('customer::_sidebar') ?>
            <div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
                    <div>
                        <h1 style="margin:0;font-size:24px">🏪 <?= e($vendor['shop_name']) ?></h1>
                        <span style="padding:3px 10px;border-radius:10px;font-size:12px;color:<?= $statusBadge[1] ?>;background:<?= $statusBadge[2] ?>"><?= e($statusBadge[0]) ?></span>
                    </div>
                </div>

                <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
                <?php if ($error = flash('error')): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

                <?php if ($vendor['status'] === 'pending'): ?>
                    <div class="aho-card" style="padding:20px;background:#fef3c7;border-left:4px solid #d97706;margin-bottom:20px">
                        ⏳ Başvurun inceleniyor. Onay sonrası ilan ekleyebileceksin. Ortalama 1-3 iş günü.
                    </div>
                <?php elseif ($vendor['status'] === 'suspended' || $vendor['status'] === 'rejected'): ?>
                    <div class="aho-card" style="padding:20px;background:#fee2e2;border-left:4px solid #dc2626;margin-bottom:20px">
                        Mağazan şu an aktif değil. Destek ekibiyle iletişime geç.
                    </div>
                <?php endif; ?>

                <!-- Özet Kartları -->
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px">
                    <div class="aho-card" style="padding:16px">
                        <div style="font-size:11px;color:#6b7280">TOPLAM SATIŞ</div>
                        <div style="font-size:20px;font-weight:700;color:#059669"><?= number_format((float)$vendor['total_sales'], 2) ?> TL</div>
                    </div>
                    <div class="aho-card" style="padding:16px">
                        <div style="font-size:11px;color:#6b7280">MÜSAİT BAKİYE</div>
                        <div style="font-size:20px;font-weight:700;color:#0ea5e9"><?= number_format($available, 2) ?> TL</div>
                    </div>
                    <div class="aho-card" style="padding:16px">
                        <div style="font-size:11px;color:#6b7280">KOMİSYON ORANI</div>
                        <div style="font-size:20px;font-weight:700">%<?= number_format((float)$vendor['commission_rate'], 1) ?></div>
                    </div>
                    <div class="aho-card" style="padding:16px">
                        <div style="font-size:11px;color:#6b7280">PUAN</div>
                        <div style="font-size:20px;font-weight:700">⭐ <?= number_format((float)$vendor['rating_avg'], 1) ?></div>
                        <div style="font-size:11px;color:#6b7280">(<?= (int)$vendor['rating_count'] ?> yorum)</div>
                    </div>
                </div>

                <!-- Payout Talebi -->
                <?php if ($available > 0): ?>
                <div class="aho-card" style="padding:20px;background:#f0f9ff;border-left:4px solid #0ea5e9;margin-bottom:20px">
                    <h3 style="margin:0 0 12px">💸 Kazançlarını Çek</h3>
                    <form method="post" action="/panel/satici/payout-iste">
                        <?= csrf() ?>
                        <div style="display:grid;grid-template-columns:1fr auto;gap:8px">
                            <input type="number" name="amount" step="0.01" max="<?= $available ?>" placeholder="Örn: 500" required style="padding:10px;border:1px solid #d1d5db;border-radius:6px">
                            <button type="submit" class="aho-btn aho-btn--primary">💰 Payout İste</button>
                        </div>
                        <small style="color:#6b7280;margin-top:6px;display:block">IBAN: <?= e($vendor['iban'] ?: 'Ayarlanmadı — profile ekle') ?></small>
                    </form>
                </div>
                <?php endif; ?>

                <!-- İlanlarım -->
                <div class="aho-card" style="padding:0;overflow:hidden;margin-bottom:20px">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center">
                        <h3 style="margin:0;font-size:16px">📦 İlanlarım (<?= count($listings) ?>)</h3>
                        <?php if ($vendor['status'] === 'approved'): ?>
                            <a href="/panel/satici/ilan-ekle" class="aho-btn aho-btn--sm aho-btn--primary">+ Yeni İlan</a>
                        <?php endif; ?>
                    </div>
                    <table style="width:100%;font-size:14px">
                        <thead style="background:#f9fafb;text-align:left">
                            <tr><th style="padding:10px 20px">Başlık</th><th style="padding:10px">Fiyat</th><th style="padding:10px">Görüntülenme</th><th style="padding:10px">Durum</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listings as $l): ?>
                                <tr style="border-top:1px solid #f3f4f6">
                                    <td style="padding:12px 20px"><?= e($l['title']) ?></td>
                                    <td style="padding:12px"><?= number_format((float)$l['price'], 2) ?> <?= e($l['currency']) ?></td>
                                    <td style="padding:12px"><?= (int)$l['views'] ?></td>
                                    <td style="padding:12px"><span class="aho-badge"><?= e($l['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$listings): ?><tr><td colspan="4" style="text-align:center;padding:30px;color:#6b7280">Henüz ilan yok.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Kazançlar -->
                <div class="aho-card" style="padding:0;overflow:hidden;margin-bottom:20px">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb"><h3 style="margin:0;font-size:16px">💰 Kazançlar</h3></div>
                    <table style="width:100%;font-size:14px">
                        <thead style="background:#f9fafb;text-align:left">
                            <tr><th style="padding:10px 20px">Tarih</th><th style="padding:10px">Brüt</th><th style="padding:10px">Komisyon</th><th style="padding:10px">Net</th><th style="padding:10px">Durum</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($earnings as $e): ?>
                                <tr style="border-top:1px solid #f3f4f6">
                                    <td style="padding:12px 20px;font-size:12px"><?= e(date('d.m.Y', strtotime((string)$e['created_at']))) ?></td>
                                    <td style="padding:12px"><?= number_format((float)$e['gross_amount'], 2) ?></td>
                                    <td style="padding:12px;color:#dc2626">-<?= number_format((float)$e['commission_amount'], 2) ?></td>
                                    <td style="padding:12px;color:#059669;font-weight:600"><?= number_format((float)$e['net_earnings'], 2) ?></td>
                                    <td style="padding:12px"><span class="aho-badge"><?= e($e['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$earnings): ?><tr><td colspan="5" style="text-align:center;padding:30px;color:#6b7280">Henüz satış yok.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Payoutlar -->
                <div class="aho-card" style="padding:0;overflow:hidden">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb"><h3 style="margin:0;font-size:16px">🏦 Payout Talepleri</h3></div>
                    <table style="width:100%;font-size:14px">
                        <thead style="background:#f9fafb;text-align:left">
                            <tr><th style="padding:10px 20px">Tarih</th><th style="padding:10px">Tutar</th><th style="padding:10px">IBAN</th><th style="padding:10px">Durum</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payouts as $p): ?>
                                <tr style="border-top:1px solid #f3f4f6">
                                    <td style="padding:12px 20px;font-size:12px"><?= e(date('d.m.Y', strtotime((string)$p['created_at']))) ?></td>
                                    <td style="padding:12px;font-weight:600"><?= number_format((float)$p['amount'], 2) ?> <?= e($p['currency']) ?></td>
                                    <td style="padding:12px;font-family:monospace;font-size:12px"><?= e($p['iban']) ?></td>
                                    <td style="padding:12px"><span class="aho-badge"><?= e($p['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$payouts): ?><tr><td colspan="4" style="text-align:center;padding:30px;color:#6b7280">Talep yok.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
