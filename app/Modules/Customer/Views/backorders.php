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
                <h1 style="margin:0 0 6px;font-size:24px">🎯 Backorder Listem</h1>
                <p style="color:#6b7280;margin:0 0 20px">Alınmış domainleri takip et, boşalırsa haberdar ol ya da otomatik yakala.</p>

                <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
                <?php if ($info = flash('info')): ?><div class="aho-alert aho-alert--info"><?= e($info) ?></div><?php endif; ?>
                <?php if ($error = flash('error')): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

                <div class="aho-card" style="padding:20px;margin-bottom:16px">
                    <h3 style="margin:0 0 12px">+ Yeni Backorder Ekle</h3>
                    <form method="post" action="/panel/backorder/ekle">
                        <?= csrf() ?>
                        <div style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:8px;align-items:end">
                            <div>
                                <label>Domain</label>
                                <input type="text" name="domain_name" required placeholder="ornek.com" style="width:100%">
                            </div>
                            <div>
                                <label>Mod</label>
                                <select name="mode">
                                    <option value="notify_only">🔔 Sadece Haberdar Et</option>
                                    <option value="auto_catch">🎯 Otomatik Yakala</option>
                                </select>
                            </div>
                            <div>
                                <label>Max Bid (TL)</label>
                                <input type="number" name="max_bid" step="0.01" placeholder="500" style="width:100%">
                            </div>
                            <button type="submit" class="aho-btn aho-btn--primary">Ekle</button>
                        </div>
                    </form>
                </div>

                <div class="aho-card" style="padding:0;overflow:hidden">
                    <table style="width:100%;font-size:14px">
                        <thead style="background:#f9fafb;text-align:left">
                            <tr><th style="padding:12px 20px">Domain</th><th style="padding:12px">Mod</th><th style="padding:12px">Max Bid</th><th style="padding:12px">Durum</th><th style="padding:12px">Eklenme</th><th style="padding:12px"></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $b):
                                $badge = match($b['status']) {
                                    'watching'  => ['👁 İzleniyor', '#0891b2', '#cffafe'],
                                    'triggered' => ['🎯 Tetiklendi', '#d97706', '#fef3c7'],
                                    'caught'    => ['✓ Yakalandı', '#059669', '#d1fae5'],
                                    'failed'    => ['✗ Başarısız', '#dc2626', '#fee2e2'],
                                    'cancelled' => ['⊘ İptal', '#6b7280', '#f3f4f6'],
                                    default     => [$b['status'], '#6b7280', '#f3f4f6'],
                                };
                            ?>
                                <tr style="border-top:1px solid #f3f4f6">
                                    <td style="padding:14px 20px;font-family:monospace;font-weight:600"><?= e($b['domain_name']) ?></td>
                                    <td style="padding:14px"><?= $b['mode'] === 'auto_catch' ? '🎯 Otomatik' : '🔔 Bildirim' ?></td>
                                    <td style="padding:14px"><?= $b['max_bid'] ? number_format((float)$b['max_bid'], 2) . ' TL' : '—' ?></td>
                                    <td style="padding:14px"><span style="padding:3px 10px;border-radius:10px;font-size:12px;color:<?= $badge[1] ?>;background:<?= $badge[2] ?>"><?= e($badge[0]) ?></span></td>
                                    <td style="padding:14px;font-size:12px;color:#6b7280"><?= e(date('d.m.Y', strtotime((string)$b['created_at']))) ?></td>
                                    <td style="padding:14px">
                                        <?php if ($b['status'] === 'watching'): ?>
                                            <form method="post" action="/panel/backorder/<?= (int)$b['id'] ?>/iptal" onsubmit="return confirm('İptal edilsin mi?')">
                                                <?= csrf() ?>
                                                <button class="aho-btn aho-btn--sm aho-btn--ghost">İptal</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$items): ?><tr><td colspan="6" style="text-align:center;padding:40px;color:#6b7280">Henüz takip listenizde domain yok.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
