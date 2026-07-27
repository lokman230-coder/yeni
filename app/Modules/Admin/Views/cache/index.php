<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
$success = flash('success');
$fmt = fn($b) => $b < 1024 ? $b . ' B' : ($b < 1048576 ? round($b/1024, 1) . ' KB' : round($b/1048576, 1) . ' MB');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>Cache Center</h1>
            <p>Sistem cache, session ve rate-limit dosyalarını görüntüle / temizle.</p>
        </div>
    </div>

    <?php if ($success): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>

    <div class="aho-stat-grid">
        <?php foreach ($stats as $key => $st): ?>
            <div class="aho-card aho-stat-card">
                <div class="aho-stat-card__label"><?= e($key) ?></div>
                <div class="aho-stat-card__value"><?= (int)$st['count'] ?></div>
                <div class="aho-stat-card__meta"><?= $fmt($st['size']) ?></div>
                <form method="post" action="/admin/cache-center/temizle" style="margin-top:var(--aho-space-3)"
                      onsubmit="return confirm('<?= e($key) ?> dizinini temizlemek istediğinize emin misiniz?');">
                    <?= csrf() ?>
                    <input type="hidden" name="target" value="<?= e($key) ?>">
                    <button type="submit" class="aho-btn aho-btn--outline aho-btn--sm aho-btn--block">Temizle</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="aho-card" style="margin-top:var(--aho-space-4)">
        <div class="aho-card__header">
            <h3 class="aho-card__title">Hepsini Temizle</h3>
        </div>
        <p style="color:var(--aho-color-ink-600);font-size:var(--aho-text-sm);margin-bottom:var(--aho-space-4)">
            Cache + rate-limit dosyaları birlikte silinir. Session'lar korunur.
        </p>
        <form method="post" action="/admin/cache-center/temizle" onsubmit="return confirm('Emin misiniz?');">
            <?= csrf() ?>
            <input type="hidden" name="target" value="all">
            <button type="submit" class="aho-btn aho-btn--danger">🗑 Hepsini Temizle</button>
        </form>
    </div>
</div>
<?php $view->endSection(); ?>
