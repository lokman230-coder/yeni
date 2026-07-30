<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>🖥️ Hosting & Sunucu</h1>
            <p>Kayıtlı hosting sunucularınız (cPanel, DirectAdmin, Plesk, Manuel).</p>
        </div>
        <a href="/admin/hosting-sunucu/yeni" class="aho-btn aho-btn--primary">+ Yeni Sunucu</a>
    </div>

    <?php if (!empty($success)): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <div class="aho-card" style="padding:0;overflow:auto">
        <?php if (empty($servers)): ?>
            <div style="padding:60px;text-align:center">
                <div style="font-size:48px">🖥️</div>
                <h3 style="margin:12px 0 8px">Henüz sunucu tanımlı değil</h3>
                <p style="color:var(--aho-color-ink-600);margin:0 0 20px">Otomatik hesap açılışı için en az bir sunucu tanımlayın.</p>
                <a href="/admin/hosting-sunucu/yeni" class="aho-btn aho-btn--primary">İlk sunucuyu ekle →</a>
            </div>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:14px">
            <thead style="background:var(--aho-color-ink-50);text-align:left">
                <tr>
                    <th style="padding:12px 16px">Ad</th>
                    <th style="padding:12px 16px">Hostname</th>
                    <th style="padding:12px 16px">Panel</th>
                    <th style="padding:12px 16px">Port</th>
                    <th style="padding:12px 16px;text-align:right">Hesap</th>
                    <th style="padding:12px 16px;text-align:center">Yük</th>
                    <th style="padding:12px 16px;text-align:center">Durum</th>
                    <th style="padding:12px 16px;text-align:right">İşlem</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($servers as $s):
                $max = (int) ($s['max_accounts'] ?? 0);
                $cur = (int) $s['active_accounts'];
                $pct = $max > 0 ? min(100, (int) round($cur / $max * 100)) : 0;
                $panelBadge = match ($s['panel']) {
                    'cpanel' => ['cPanel',       '#f97316'],
                    'da'     => ['DirectAdmin',  '#0891b2'],
                    'plesk'  => ['Plesk',        '#0ea5e9'],
                    default  => ['Manuel',       '#6b7280'],
                };
            ?>
                <tr style="border-top:1px solid var(--aho-color-border)">
                    <td style="padding:12px 16px;font-weight:600"><?= e($s['name']) ?></td>
                    <td style="padding:12px 16px;font-family:monospace;font-size:13px">
                        <?= e($s['hostname']) ?>
                        <?php if ($s['ip']): ?><div style="font-size:11px;color:var(--aho-color-ink-500)"><?= e($s['ip']) ?></div><?php endif; ?>
                    </td>
                    <td style="padding:12px 16px">
                        <span style="padding:3px 10px;font-size:11px;border-radius:10px;background:<?= $panelBadge[1] ?>;color:#fff;font-weight:600"><?= e($panelBadge[0]) ?></span>
                    </td>
                    <td style="padding:12px 16px;font-family:monospace;font-size:13px"><?= (int)$s['port'] ?><?= $s['use_ssl'] ? ' 🔒' : '' ?></td>
                    <td style="padding:12px 16px;text-align:right;font-weight:600"><?= $cur ?> / <?= $max > 0 ? $max : '∞' ?></td>
                    <td style="padding:12px 16px;text-align:center;min-width:120px">
                        <?php if ($max > 0): ?>
                            <div style="background:#e5e7eb;border-radius:10px;height:8px;overflow:hidden">
                                <div style="height:100%;width:<?= $pct ?>%;background:<?= $pct > 90 ? '#dc2626' : ($pct > 70 ? '#d97706' : '#059669') ?>"></div>
                            </div>
                            <div style="font-size:11px;color:var(--aho-color-ink-500);margin-top:2px"><?= $pct ?>%</div>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px 16px;text-align:center">
                        <?php if ((int)$s['is_active'] === 1): ?>
                            <span style="padding:3px 10px;font-size:11px;border-radius:10px;color:#065f46;background:#d1fae5">✓ Aktif</span>
                        <?php else: ?>
                            <span style="padding:3px 10px;font-size:11px;border-radius:10px;color:#6b7280;background:#f3f4f6">◌ Pasif</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px 16px;text-align:right">
                        <a href="/admin/hosting-sunucu/<?= (int)$s['id'] ?>" style="color:var(--aho-color-primary-600);text-decoration:none;font-size:13px;font-weight:600">Düzenle →</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php $view->endSection(); ?>
