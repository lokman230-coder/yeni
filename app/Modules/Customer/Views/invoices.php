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
                <h1 style="margin:0 0 4px;font-size:24px">🧾 Faturalarım</h1>
                <p style="color:var(--aho-color-ink-600);margin:0 0 20px">Tüm faturalarınız ve ödeme durumları.</p>

                <?php if (empty($invoices)): ?>
                    <div class="aho-card" style="padding:60px;text-align:center">
                        <div style="font-size:56px">📋</div>
                        <h3 style="margin:12px 0 8px">Henüz faturanız yok</h3>
                        <p style="color:var(--aho-color-ink-600);margin:0">Sipariş verdiğinizde otomatik olarak burada listelenir.</p>
                    </div>
                <?php else: ?>
                    <div class="aho-card" style="padding:0;overflow:auto">
                        <table style="width:100%;border-collapse:collapse;font-size:14px">
                            <thead style="background:var(--aho-color-ink-50);text-align:left">
                                <tr>
                                    <th style="padding:12px 16px">No</th>
                                    <th style="padding:12px 16px">Tarih</th>
                                    <th style="padding:12px 16px">Vade</th>
                                    <th style="padding:12px 16px;text-align:right">Tutar</th>
                                    <th style="padding:12px 16px;text-align:right">Kalan</th>
                                    <th style="padding:12px 16px;text-align:center">Durum</th>
                                    <th style="padding:12px 16px;text-align:right">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($invoices as $i): ?>
                                <?php
                                $badge = match ($i['status']) {
                                    'paid'           => ['✅ Ödendi',        '#059669', '#d1fae5'],
                                    'unpaid'         => ['⏳ Ödenmemiş',    '#d97706', '#fef3c7'],
                                    'partially_paid' => ['◐ Kısmen',       '#d97706', '#fef3c7'],
                                    'overdue'        => ['⚠️ Vadesi geçti', '#dc2626', '#fee2e2'],
                                    'cancelled'      => ['❌ İptal',         '#6b7280', '#f3f4f6'],
                                    'refunded'       => ['↩ İade',          '#6b7280', '#f3f4f6'],
                                    'draft'          => ['📝 Taslak',       '#6b7280', '#f3f4f6'],
                                    default          => [$i['status'],       '#6b7280', '#f3f4f6'],
                                };
                                $isUnpaid = in_array($i['status'], ['unpaid','partially_paid','overdue'], true);
                                ?>
                                <tr style="border-top:1px solid var(--aho-color-border)">
                                    <td style="padding:12px 16px;font-family:monospace;font-size:13px;font-weight:600"><?= e($i['invoice_number']) ?></td>
                                    <td style="padding:12px 16px"><?= e(date('d.m.Y', strtotime((string)$i['issue_date']))) ?></td>
                                    <td style="padding:12px 16px"><?= e(date('d.m.Y', strtotime((string)$i['due_date']))) ?></td>
                                    <td style="padding:12px 16px;text-align:right;font-weight:600"><?= number_format((float)$i['total'], 2, ',', '.') ?> ₺</td>
                                    <td style="padding:12px 16px;text-align:right;color:<?= $isUnpaid ? '#d97706' : 'var(--aho-color-ink-500)' ?>;font-weight:<?= $isUnpaid ? '600' : '400' ?>">
                                        <?= number_format((float)$i['balance'], 2, ',', '.') ?> ₺
                                    </td>
                                    <td style="padding:12px 16px;text-align:center">
                                        <span style="padding:3px 10px;font-size:12px;border-radius:12px;color:<?= $badge[1] ?>;background:<?= $badge[2] ?>">
                                            <?= e($badge[0]) ?>
                                        </span>
                                    </td>
                                    <td style="padding:12px 16px;text-align:right">
                                        <?php if ($isUnpaid && $i['order_id']): ?>
                                            <a href="/odeme/<?= (int)$i['id'] ?>" class="aho-btn aho-btn--primary" style="padding:6px 12px;font-size:12px">💳 Öde</a>
                                        <?php endif; ?>
                                        <a href="/panel/fatura/<?= (int)$i['id'] ?>/pdf" style="color:var(--aho-color-ink-600);text-decoration:none;font-size:12px;margin-left:8px" title="PDF indir">📄</a>
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
