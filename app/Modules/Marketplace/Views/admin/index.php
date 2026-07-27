<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🛍️ Marketplace</h1>
            <p>İlan onayları, aktifler, satılanlar.</p>
        </div>
    </div>

    <?php if (!empty($success)): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
        <?php foreach (['pending'=>['⏳ Bekleyen', $metrics['pending']], 'active'=>['✅ Aktif', $metrics['active']], 'sold'=>['💰 Satılan', $metrics['sold']], 'rejected'=>['❌ Reddedilen', $metrics['rejected']]] as $k=>$v):
            $active = $status === $k;
        ?>
            <a href="/admin/marketplace?status=<?= $k ?>" style="padding:8px 14px;border:1px solid <?= $active?'var(--aho-color-primary-600)':'var(--aho-color-border)' ?>;color:<?= $active?'#fff':'inherit' ?>;background:<?= $active?'var(--aho-color-primary-600, #0ea5e9)':'#fff' ?>;text-decoration:none;border-radius:20px;font-size:13px;font-weight:600">
                <?= e($v[0]) ?> <span style="opacity:.7">(<?= $v[1] ?>)</span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="aho-card" style="padding:0;overflow:auto">
        <?php if (empty($listings)): ?>
            <div style="padding:40px;text-align:center;color:var(--aho-color-ink-500);font-size:14px">
                Bu durumda ilan yok.
            </div>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:14px">
            <thead style="background:var(--aho-color-ink-50);text-align:left">
                <tr>
                    <th style="padding:12px 16px">İlan</th>
                    <th style="padding:12px 16px">Satıcı</th>
                    <th style="padding:12px 16px">Kategori</th>
                    <th style="padding:12px 16px;text-align:right">Fiyat</th>
                    <th style="padding:12px 16px;text-align:right">Komisyon</th>
                    <th style="padding:12px 16px;text-align:center">Görüntülenme</th>
                    <th style="padding:12px 16px;text-align:right">İşlem</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($listings as $l): ?>
                <tr style="border-top:1px solid var(--aho-color-border)">
                    <td style="padding:12px 16px">
                        <div style="font-weight:600"><?= e($l['title']) ?></div>
                        <div style="font-size:11px;color:var(--aho-color-ink-500)"><?= e(mb_substr((string)($l['description'] ?? ''), 0, 60)) ?><?= mb_strlen((string)($l['description'] ?? '')) > 60 ? '…' : '' ?></div>
                    </td>
                    <td style="padding:12px 16px;font-size:13px"><?= e($l['seller_email'] ?? '—') ?></td>
                    <td style="padding:12px 16px;font-size:12px;color:var(--aho-color-ink-600)"><?= e($l['category_name'] ?? '—') ?></td>
                    <td style="padding:12px 16px;text-align:right;font-weight:600"><?= number_format((float)$l['price'], 2, ',', '.') ?> <?= e($l['currency']) ?></td>
                    <td style="padding:12px 16px;text-align:right;font-size:12px;color:var(--aho-color-ink-500)">%<?= number_format((float)$l['commission_rate'], 1, ',', '.') ?></td>
                    <td style="padding:12px 16px;text-align:center;font-size:12px"><?= (int)$l['views'] ?></td>
                    <td style="padding:12px 16px;text-align:right;white-space:nowrap">
                        <?php if ($l['status'] === 'pending'): ?>
                            <form method="post" action="/admin/marketplace/<?= (int)$l['id'] ?>/onayla" style="display:inline">
                                <?= csrf() ?>
                                <button type="submit" style="padding:5px 10px;background:#059669;color:#fff;border:0;border-radius:6px;cursor:pointer;font-size:11px">✓ Onayla</button>
                            </form>
                            <form method="post" action="/admin/marketplace/<?= (int)$l['id'] ?>/reddet" style="display:inline">
                                <?= csrf() ?>
                                <button type="submit" style="padding:5px 10px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:6px;cursor:pointer;font-size:11px">✗ Red</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="/admin/marketplace/<?= (int)$l['id'] ?>/sil" style="display:inline" onsubmit="return confirm('İlan silinsin mi?')">
                            <?= csrf() ?>
                            <button type="submit" style="background:none;border:0;cursor:pointer;color:#dc2626;font-size:14px">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php $view->endSection(); ?>
