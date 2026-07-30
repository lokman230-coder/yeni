<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$categories = $categories ?? [];
$articles = $articles ?? [];
$query = $query ?? '';
$activeCategory = $activeCategory ?? null;
?>
<section class="aho-pages-hero"><div class="aho-container"><h1>Bilgi Bankasi</h1><p style="color:var(--aho-color-ink-500);margin-top:var(--aho-space-2)">Sorulariniza hizli yanitlar.</p></div></section>
<section class="aho-pages-body"><div class="aho-container">
    <form method="get" action="/bilgi-bankasi" style="max-width:600px;margin:0 auto var(--aho-space-8);display:flex;gap:var(--aho-space-2)">
        <input type="text" name="q" value="<?= e($query) ?>" class="aho-form-input" placeholder="Nasil yapabilirim..." style="flex:1">
        <button class="aho-btn aho-btn--primary">Ara</button>
    </form>

    <div class="aho-feature-grid">
        <?php foreach ($categories as $slug => $cat): ?>
        <a href="/bilgi-bankasi/kategori/<?= e($slug) ?>" class="aho-card aho-card--hover" style="text-decoration:none;color:inherit;<?= $activeCategory === $slug ? 'border-color:var(--aho-color-primary);' : '' ?>">
            <div class="aho-feature-card__icon"><?= $cat['icon'] ?></div>
            <h3><?= e($cat['title']) ?></h3>
            <p style="color:var(--aho-color-ink-500);font-size:var(--aho-text-sm)"><?= e($cat['summary']) ?></p>
        </a>
        <?php endforeach; ?>
    </div>

    <div style="margin-top:var(--aho-space-10)">
        <h2 style="font-size:24px;margin-bottom:var(--aho-space-4)"><?= $query !== '' ? 'Arama Sonuclari' : ($activeCategory ? e($categories[$activeCategory]['title']) : 'Populer Bilgiler') ?></h2>
        <div style="display:grid;gap:12px">
            <?php foreach ($articles as $article): ?>
                <a href="/bilgi-bankasi/<?= e($article['slug']) ?>" class="aho-card aho-card--hover" style="display:block;text-decoration:none;color:inherit;padding:18px">
                    <h3 style="margin:0 0 6px"><?= e($article['title']) ?></h3>
                    <p style="margin:0;color:var(--aho-color-ink-500);font-size:var(--aho-text-sm)"><?= e($article['summary']) ?></p>
                </a>
            <?php endforeach; ?>
            <?php if (!$articles): ?>
                <div class="aho-card" style="padding:24px;text-align:center;color:var(--aho-color-ink-500)">Bu arama icin sonuc bulunamadi.</div>
            <?php endif; ?>
        </div>
    </div>
</div></section>
<?php $view->endSection(); ?>
