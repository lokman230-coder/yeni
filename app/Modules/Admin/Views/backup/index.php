<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');

function humanSize(int $b): string {
    if ($b < 1024) return $b . ' B';
    if ($b < 1048576) return round($b / 1024, 1) . ' KB';
    if ($b < 1073741824) return round($b / 1048576, 1) . ' MB';
    return round($b / 1073741824, 2) . ' GB';
}
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>💾 Yedekleme</h1>
            <p>Veritabanı ve dosya sistemi yedekleri. 30 günden eski yedekler otomatik silinir.</p>
        </div>
        <div style="display:flex;gap:8px">
            <form method="post" action="/admin/yedekleme/db" style="margin:0">
                <?= csrf() ?>
                <button type="submit" class="aho-btn aho-btn--primary">🗄️ DB Yedeği Al</button>
            </form>
            <form method="post" action="/admin/yedekleme/storage" style="margin:0">
                <?= csrf() ?>
                <button type="submit" style="padding:10px 16px;background:#0891b2;color:#fff;border:0;border-radius:8px;font-weight:600;cursor:pointer">📁 Storage Yedeği Al</button>
            </form>
        </div>
    </div>

    <?php if (!empty($success)): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <div class="aho-card" style="padding:0;overflow:auto">
        <?php if (empty($backups)): ?>
            <div style="padding:60px;text-align:center">
                <div style="font-size:48px">💾</div>
                <h3 style="margin:12px 0 8px">Henüz yedek yok</h3>
                <p style="color:var(--aho-color-ink-600);margin:0">Yukarıdaki butonlarla ilk yedeğinizi alın.</p>
            </div>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:14px">
            <thead style="background:var(--aho-color-ink-50);text-align:left">
                <tr>
                    <th style="padding:12px 16px">Dosya</th>
                    <th style="padding:12px 16px">Tip</th>
                    <th style="padding:12px 16px">Tarih</th>
                    <th style="padding:12px 16px;text-align:right">Boyut</th>
                    <th style="padding:12px 16px;text-align:right">İşlem</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($backups as $b):
                $badge = match ($b['type']) {
                    'db'      => ['🗄️ DB',    '#0891b2', '#e0f2fe'],
                    'storage' => ['📁 Storage','#059669', '#d1fae5'],
                    default   => ['📄 Dosya', '#6b7280', '#f3f4f6'],
                };
            ?>
                <tr style="border-top:1px solid var(--aho-color-border)">
                    <td style="padding:12px 16px;font-family:monospace;font-size:13px"><?= e($b['name']) ?></td>
                    <td style="padding:12px 16px">
                        <span style="padding:3px 10px;font-size:11px;border-radius:10px;color:<?= $badge[1] ?>;background:<?= $badge[2] ?>">
                            <?= e($badge[0]) ?>
                        </span>
                    </td>
                    <td style="padding:12px 16px;font-size:13px;color:var(--aho-color-ink-600)"><?= date('d.m.Y H:i', $b['mtime']) ?></td>
                    <td style="padding:12px 16px;text-align:right;font-weight:600"><?= humanSize($b['size']) ?></td>
                    <td style="padding:12px 16px;text-align:right;white-space:nowrap">
                        <a href="/admin/yedekleme/indir/<?= e($b['name']) ?>" style="padding:5px 10px;background:#0ea5e9;color:#fff;text-decoration:none;border-radius:6px;font-size:12px">⬇️ İndir</a>
                        <form method="post" action="/admin/yedekleme/sil/<?= e($b['name']) ?>" style="display:inline" onsubmit="return confirm('<?= e($b['name']) ?> silinsin mi?')">
                            <?= csrf() ?>
                            <button type="submit" style="background:none;border:0;color:#dc2626;cursor:pointer;font-size:14px">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="aho-card" style="padding:16px;margin-top:16px;background:#fef3c7;border-left:4px solid #d97706;font-size:13px;color:#78350f;line-height:1.6">
        ⚠️ <strong>Önemli:</strong> Yedekleri düzenli olarak <strong>uzak bir sunucuya kopyalayın</strong> (S3, Backblaze, kendi laptop'un). Sunucun çökerse yedekler de gider. Yedek almak bir şey değildir, <em>yedeği başka yerde tutmak</em> asıl güvenliktir.
    </div>
</div>
<?php $view->endSection(); ?>
