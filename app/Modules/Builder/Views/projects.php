<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$isMobile = ($kind ?? 'site') === 'mobile';
$formAction = $isMobile ? '/mobile-builder/olustur' : '/site-builder/olustur';
$success = flash('success');
$error = flash('error');
$selectedSector = (string)($selected_sector ?? '');
?>
<section class="aho-pages-hero" style="padding-block:var(--aho-space-8)">
    <div class="aho-container">
        <h1><?= e($title) ?></h1>
        <p style="color:var(--aho-color-ink-500)">
            <?= $isMobile ? 'Mobil uygulama' : 'Web sitesi' ?> sablonunu secin, editor hemen acilsin.
        </p>
    </div>
</section>

<section class="aho-pages-body">
    <div class="aho-container">
        <?php if ($success): ?><div class="aho-alert aho-alert--success"><?= e($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="aho-alert aho-alert--danger"><?= e($error) ?></div><?php endif; ?>

        <div class="aho-card" style="margin-bottom:var(--aho-space-6)">
            <h3 class="aho-card__title" style="margin-bottom:var(--aho-space-4)">Yeni Proje Olustur</h3>
            <form method="post" action="<?= e($formAction) ?>" data-bldr-template-form>
                <?= csrf() ?>
                <div class="aho-form-group">
                    <label class="aho-form-label">Proje Adi</label>
                    <input type="text" name="name" class="aho-form-input"
                           placeholder="<?= $isMobile ? 'Orn: Radyo Uygulamam' : 'Orn: Kisisel Sitem' ?>">
                </div>
                <div class="aho-form-group">
                    <label class="aho-form-label">Sablon / Sektor Secin</label>
                    <div class="aho-bldr-sector-grid" data-bldr-sector-picker>
                        <?php foreach ($sectors as $slug => $s): ?>
                            <?php $checked = $selectedSector === $slug || ($selectedSector === '' && $slug === array_key_first($sectors)); ?>
                            <label class="aho-bldr-sector" style="cursor:pointer">
                                <input type="radio" name="sector" value="<?= e($slug) ?>"
                                       style="position:absolute;opacity:0" <?= $checked ? 'checked' : '' ?>>
                                <div class="aho-bldr-sector__icon"><?= $s['icon'] ?></div>
                                <div class="aho-bldr-sector__label"><?= e($s['label']) ?></div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" class="aho-btn aho-btn--primary aho-btn--lg">Projeyi Olustur ve Editoru Ac</button>
            </form>
        </div>

        <?php if (!empty($projects)): ?>
            <h3 style="margin-bottom:var(--aho-space-3)">Projelerim (<?= count($projects) ?>)</h3>
            <div class="aho-bldr-projects">
                <?php foreach ($projects as $p): ?>
                    <a href="/panel/builder/<?= (int)$p['id'] ?>" class="aho-bldr-project">
                        <div class="aho-bldr-project__thumb"><?= $isMobile ? '📱' : '🌐' ?></div>
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
document.querySelectorAll('[data-bldr-sector-picker] label').forEach(label => {
    label.addEventListener('click', () => {
        document.querySelectorAll('[data-bldr-sector-picker] label').forEach(x => x.classList.remove('is-selected'));
        label.classList.add('is-selected');
        const input = label.querySelector('input[type="radio"]');
        if (input) input.checked = true;
        const form = label.closest('form[data-bldr-template-form]');
        if (form) setTimeout(() => form.submit(), 80);
    });
    if (label.querySelector('input:checked')) label.classList.add('is-selected');
});
</script>
<?php $view->endSection(); ?>
