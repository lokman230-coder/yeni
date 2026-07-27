<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero" style="padding-block:var(--aho-space-8)">
    <div class="aho-container">
        <a href="/marketplace" style="color:var(--aho-color-ink-500);font-size:var(--aho-text-sm)">← Marketplace</a>
        <h1 style="margin-top:var(--aho-space-2)"><?= e($listing['title']) ?></h1>
        <p style="color:var(--aho-color-ink-500);margin-top:var(--aho-space-2)"><?= e($listing['category_name'] ?? 'Genel') ?></p>
    </div>
</section>

<section class="aho-pages-body">
    <div class="aho-container" style="max-width:900px">
        <div class="aho-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--aho-space-4);flex-wrap:wrap;gap:var(--aho-space-3)">
                <div style="font-size:var(--aho-text-3xl);font-weight:700;color:var(--aho-color-primary-700)">
                    <?= number_format((float)$listing['price'], 0, ',', '.') ?> <?= e($listing['currency']) ?>
                </div>
                <div style="display:flex;gap:var(--aho-space-2)">
                    <a href="/giris" class="aho-btn aho-btn--primary aho-btn--lg">Teklif Ver</a>
                    <a href="/iletisim" class="aho-btn aho-btn--outline aho-btn--lg">Satıcıyla İletişim</a>
                </div>
            </div>

            <div class="aho-prose" style="margin-top:var(--aho-space-6)">
                <?= $view->raw(nl2br(e($listing['description'] ?? 'Açıklama bulunmuyor.'))) ?>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
