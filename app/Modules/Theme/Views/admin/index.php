<?php
/** @var \App\Core\View $view */
$view->extend('layouts.admin');
$view->section('content');
?>
<div class="aho-admin-page">
    <div class="aho-admin-page__header">
        <div>
            <h1>Temalar</h1>
            <p>Site ve admin panel gorunumunde kullanilacak tema paketini secin.</p>
        </div>
        <a href="/admin/tema-bloklari" class="aho-btn aho-btn--outline">Tema Bloklari</a>
    </div>

    <?php if (!empty($success)): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px">
        <?php foreach ($themes as $slug => $theme): ?>
            <?php $isActive = $slug === $active; ?>
            <section class="aho-card" style="padding:18px">
                <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start">
                    <div>
                        <h2 style="margin:0 0 4px;font-size:18px"><?= e($theme['name'] ?? ucfirst($slug)) ?></h2>
                        <p style="margin:0;color:var(--aho-color-ink-500);font-size:13px"><?= e($theme['description'] ?? '') ?></p>
                    </div>
                    <?php if ($isActive): ?>
                        <span style="display:inline-flex;padding:4px 9px;border-radius:999px;background:#dcfce7;color:#166534;font-size:12px;font-weight:700">AKTIF</span>
                    <?php endif; ?>
                </div>

                <div style="display:flex;gap:8px;margin:16px 0">
                    <?php foreach (($theme['colors'] ?? []) as $color): ?>
                        <span style="width:34px;height:34px;border-radius:8px;border:1px solid var(--aho-color-border);background:<?= e((string)$color) ?>"></span>
                    <?php endforeach; ?>
                </div>

                <dl style="display:grid;grid-template-columns:90px 1fr;gap:6px 10px;margin:0 0 16px;font-size:13px">
                    <dt style="color:var(--aho-color-ink-500)">Slug</dt><dd style="margin:0"><code><?= e($slug) ?></code></dd>
                    <dt style="color:var(--aho-color-ink-500)">Versiyon</dt><dd style="margin:0"><?= e($theme['version'] ?? '-') ?></dd>
                    <dt style="color:var(--aho-color-ink-500)">Yazar</dt><dd style="margin:0"><?= e($theme['author'] ?? '-') ?></dd>
                </dl>

                <form method="post" action="/admin/temalar/aktif-et">
                    <?= csrf() ?>
                    <input type="hidden" name="theme" value="<?= e($slug) ?>">
                    <button class="aho-btn <?= $isActive ? 'aho-btn--ghost' : 'aho-btn--primary' ?> aho-btn--block" <?= $isActive ? 'disabled' : '' ?>>
                        <?= $isActive ? 'Aktif Tema' : 'Aktif Et' ?>
                    </button>
                </form>
            </section>
        <?php endforeach; ?>
    </div>

    <?php if (!$themes): ?>
        <div class="aho-card" style="padding:30px;text-align:center">Tema klasoru bos gorunuyor.</div>
    <?php endif; ?>
</div>
<?php $view->endSection(); ?>
