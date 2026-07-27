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
                <div style="margin-bottom:24px">
                    <h1 style="margin:0 0 6px;font-size:24px">💳 Ödemelerim</h1>
                    <p style="color:#6b7280;margin:0">Tüm ödeme geçmişin.</p>
                </div>

                <div class="aho-card" style="padding:0;overflow:hidden">
                    <table style="width:100%;font-size:14px">
                        <thead style="background:#f9fafb;text-align:left">
                            <tr><th style="padding:12px 20px">Tarih</th><th style="padding:12px">Fatura</th><th style="padding:12px">Yöntem</th><th style="padding:12px">Tutar</th><th style="padding:12px">Durum</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $p):
                                $badge = match($p['status']) {
                                    'success' => ['#059669','#d1fae5','Başarılı'],
                                    'failed'  => ['#dc2626','#fee2e2','Başarısız'],
                                    'refunded'=> ['#6b7280','#f3f4f6','İade'],
                                    default   => ['#d97706','#fef3c7','Bekliyor'],
                                };
                            ?>
                                <tr style="border-top:1px solid #f3f4f6">
                                    <td style="padding:14px 20px"><?= e(date('d.m.Y H:i', strtotime((string)$p['created_at']))) ?></td>
                                    <td style="padding:14px">
                                        <?php if ($p['invoice_number']): ?>
                                            <a href="/panel/fatura/<?= (int)$p['invoice_id'] ?>">#<?= e($p['invoice_number']) ?></a>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td style="padding:14px"><?= e($p['method']) ?></td>
                                    <td style="padding:14px;font-weight:600"><?= number_format((float)$p['amount'], 2) ?> <?= e($p['currency']) ?></td>
                                    <td style="padding:14px"><span style="padding:3px 10px;border-radius:10px;font-size:12px;color:<?= $badge[0] ?>;background:<?= $badge[1] ?>"><?= $badge[2] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$payments): ?><tr><td colspan="5" style="text-align:center;padding:60px;color:#6b7280">Henüz ödeme yok.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
