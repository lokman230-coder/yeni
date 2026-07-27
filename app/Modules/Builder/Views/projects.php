<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$isMobile = ($kind ?? 'site') === 'mobile';
$formAction = $isMobile ? '/mobile-builder/olustur' : '/site-builder/olustur';
$success = flash('success');
$error = flash('error');
?>
<section class="aho-pages-hero" style="padding-block:var(--aho-space-8)">
    <div class="aho-container">
        <h1><?= e($title) ?></h1>
        <p style="color:var(--aho-color-ink-500)">
            <?= $isMobile ? 'Mobil uygulama' : 'Web sitesi' ?> projelerinizi buradan yönetin.
        </p>
    </div>
</section>

<section class="aho-pages-body">
    <div class="aho-container">
        <?php if ($success): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

        <!-- Yeni proje formu -->
        <div class="aho-card" style="margin-bottom:var(--aho-space-6)">
            <h3 class="aho-card__title" style="margin-bottom:var(--aho-space-4)">➕ Yeni Proje Oluştur</h3>
            <form method="post" action="<?= e($formAction) ?>">
                <?= csrf() ?>
                <div class="aho-form-group">
                    <label class="aho-form-label aho-form-label--required">Proje Adı</label>
                    <input type="text" name="name" class="aho-form-input"
                           placeholder="<?= $isMobile ? 'Örn: Radyo Uygulamam' : 'Örn: Kişisel Sitem' ?>" required>
                </div>
                <div class="aho-form-group">
                    <label class="aho-form-label">Sektör Seçin</label>
                    <div class="aho-bldr-sector-grid" data-bldr-sector-picker>
                        <?php foreach ($sectors as $slug => $s): ?>
                            <label class="aho-bldr-sector" style="cursor:pointer">
                                <input type="radio" name="sector" value="<?= e($slug) ?>"
                                       style="position:absolute;opacity:0" <?= $slug === array_key_first($sectors) ? 'checked' : '' ?>>
                                <div class="aho-bldr-sector__icon"><?= $s['icon'] ?></div>
                                <div class="aho-bldr-sector__label"><?= e($s['label']) ?></div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" class="aho-btn aho-btn--primary aho-btn--lg">Projeyi Oluştur ve Editörü Aç</button>
            </form>
        </div>

        <!-- Mevcut projeler -->
        <h3 style="margin-bottom:var(--aho-space-3)">📁 Projelerim (<?= count($projects) ?>)</h3>
        <?php if (empty($projects)): ?>
            <div class="aho-card">
                <div class="aho-empty-state" style="padding:var(--aho-space-8)">
                    <div class="aho-empty-state__icon" style="font-size:48px">🎨</div>
                    <div class="aho-empty-state__title">Henüz projeniz yok</div>
                    <div class="aho-empty-state__text">Yukarıdaki formu doldurarak ilk projenizi oluşturun.</div>
                </div>
            </div>
        <?php else: ?>
            <div class="aho-bldr-projects">
                <?php foreach ($projects as $p): ?>
                    <a href="/panel/builder/<?= (int)$p['id'] ?>" class="aho-bldr-project">
                        <div class="aho-bldr-project__thumb">
                            <?= $isMobile ? '📱' : '🌐' ?>
                        </div>
                        <div class="aho-bldr-project__name"><?= e($p['name']) ?></div>
                        <div class="aho-bldr-project__meta">
                            <?= e($sectors[$p['sector']]['label'] ?? $p['sector']) ?>
                            · <span class="aho-bldr-project__status aho-bldr-project__status--<?= e($p['status']) ?>"><?= e($p['status']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.querySelectorAll('[data-bldr-sector-picker] label').forEach(l => {
    l.addEventListener('click', () => {
        document.querySelectorAll('[data-bldr-sector-picker] label').forEach(x => x.classList.remove('is-selected'));
        l.classList.add('is-selected');
    });
    if (l.querySelector('input:checked')) l.classList.add('is-selected');
});
</script>
<?php $view->endSection(); ?>
