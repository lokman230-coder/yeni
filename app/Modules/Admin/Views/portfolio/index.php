<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div><h1>🎨 Portfolio / Referanslar</h1><p>Yaptığınız site, uygulama, tasarım işleri.</p></div>
        <div class="aho-admin-page__actions">
            <a href="/admin/portfolio/yeni" class="aho-btn aho-btn--primary">+ Yeni Proje</a>
        </div>
    </div>
    <?php if ($success = flash('success')): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>

    <div class="aho-card">
        <table class="aho-table">
            <thead><tr><th>#</th><th>Başlık</th><th>Müşteri</th><th>Kategori</th><th>Sektör</th><th>Öne Çıkan</th><th>Yayında</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($projects as $p): ?>
                    <tr>
                        <td>#<?= (int)$p['id'] ?></td>
                        <td><strong><?= e($p['title']) ?></strong></td>
                        <td><?= e($p['client_name'] ?: '—') ?></td>
                        <td><span class="aho-badge"><?= e($p['category']) ?></span></td>
                        <td><?= e($p['sector'] ?: '—') ?></td>
                        <td><?= (int)$p['is_featured'] ? '⭐' : '—' ?></td>
                        <td><?= (int)$p['is_published'] ? '✓' : '—' ?></td>
                        <td>
                            <a href="/admin/portfolio/<?= (int)$p['id'] ?>/duzenle" class="aho-btn aho-btn--sm aho-btn--outline">Düzenle</a>
                            <form method="post" action="/admin/portfolio/<?= (int)$p['id'] ?>/sil" style="display:inline" onsubmit="return confirm('Silinsin mi?')">
                                <?= csrf() ?>
                                <button class="aho-btn aho-btn--sm aho-btn--danger">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$projects): ?><tr><td colspan="8" style="text-align:center;color:#6b7280;padding:24px">Henüz proje yok.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $view->endSection(); ?>
