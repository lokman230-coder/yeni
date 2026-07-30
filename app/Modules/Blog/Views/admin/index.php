<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>✍️ Blog</h1>
            <p>Blog yazıları — AI ile hızlı taslak, SEO destekli yayın.</p>
        </div>
        <a href="/admin/blog/yeni" class="aho-btn aho-btn--primary">+ Yeni Yazı</a>
    </div>

    <?php if (!empty($success)): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px">
        <div class="aho-card" style="padding:14px"><div style="font-size:11px;color:var(--aho-color-ink-500)">TOPLAM</div><div style="font-size:24px;font-weight:700"><?= (int)$metrics['total'] ?></div></div>
        <div class="aho-card" style="padding:14px"><div style="font-size:11px;color:var(--aho-color-ink-500)">YAYINDA</div><div style="font-size:24px;font-weight:700;color:#059669"><?= (int)$metrics['published'] ?></div></div>
        <div class="aho-card" style="padding:14px"><div style="font-size:11px;color:var(--aho-color-ink-500)">TASLAK</div><div style="font-size:24px;font-weight:700;color:#d97706"><?= (int)$metrics['draft'] ?></div></div>
        <div class="aho-card" style="padding:14px"><div style="font-size:11px;color:var(--aho-color-ink-500)">GÖRÜNTÜLENME</div><div style="font-size:24px;font-weight:700"><?= (int)$metrics['views'] ?></div></div>
    </div>

    <div class="aho-card" style="padding:0;overflow:auto">
        <?php if (empty($posts)): ?>
            <div style="padding:60px;text-align:center">
                <div style="font-size:48px">📝</div>
                <h3 style="margin:12px 0 8px">Henüz blog yazısı yok</h3>
                <p style="color:var(--aho-color-ink-600);margin:0 0 16px">AI ile ilk yazınızı 30 saniyede üretebilirsiniz.</p>
                <a href="/admin/blog/yeni" class="aho-btn aho-btn--primary">İlk Yazıyı Oluştur →</a>
            </div>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:14px">
            <thead style="background:var(--aho-color-ink-50);text-align:left">
                <tr>
                    <th style="padding:12px 16px">Başlık</th>
                    <th style="padding:12px 16px">Kategori</th>
                    <th style="padding:12px 16px;text-align:center">Görüntülenme</th>
                    <th style="padding:12px 16px;text-align:center">Durum</th>
                    <th style="padding:12px 16px">Tarih</th>
                    <th style="padding:12px 16px;text-align:right">İşlem</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($posts as $p):
                $badge = match ($p['status']) {
                    'published' => ['✅ Yayında', '#059669', '#d1fae5'],
                    'draft'     => ['📝 Taslak', '#d97706', '#fef3c7'],
                    'archived'  => ['📦 Arşiv',   '#6b7280', '#f3f4f6'],
                    default     => [$p['status'], '#6b7280', '#f3f4f6'],
                };
            ?>
                <tr style="border-top:1px solid var(--aho-color-border)">
                    <td style="padding:12px 16px">
                        <div style="font-weight:600"><?= e($p['title']) ?></div>
                        <div style="font-size:11px;color:var(--aho-color-ink-500);font-family:monospace">/<?= e($p['slug']) ?></div>
                    </td>
                    <td style="padding:12px 16px;font-size:13px"><?= e($p['category'] ?? '—') ?></td>
                    <td style="padding:12px 16px;text-align:center"><?= (int)$p['views'] ?></td>
                    <td style="padding:12px 16px;text-align:center">
                        <span style="padding:3px 10px;font-size:11px;border-radius:10px;color:<?= $badge[1] ?>;background:<?= $badge[2] ?>"><?= e($badge[0]) ?></span>
                    </td>
                    <td style="padding:12px 16px;font-size:12px;color:var(--aho-color-ink-500)">
                        <?= e(date('d.m.Y', strtotime((string)$p['created_at']))) ?>
                    </td>
                    <td style="padding:12px 16px;text-align:right">
                        <a href="/admin/blog/<?= (int)$p['id'] ?>" style="color:var(--aho-color-primary-600);text-decoration:none;font-size:13px;font-weight:600">Düzenle →</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php $view->endSection(); ?>
