<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero">
    <div class="aho-container">
        <h1>Site Araçları</h1>
        <p style="color:var(--aho-color-ink-500);margin-top:var(--aho-space-2);font-size:var(--aho-text-lg)">
            <?= count($tools) ?>+ ücretsiz profesyonel araç — hepsi gerçek veri, kayıt gerektirmez.
        </p>
    </div>
</section>

<section class="aho-pages-body">
    <div class="aho-container">
        <div class="aho-feature-grid">
            <?php foreach ($tools as $tool): ?>
                <a href="/site-araclari/<?= e($tool->slug()) ?>" class="aho-card aho-card--hover" style="text-decoration:none;color:inherit">
                    <div class="aho-feature-card__icon"><?= $tool->icon() ?></div>
                    <h3 style="font-size:var(--aho-text-lg);margin-bottom:var(--aho-space-2)"><?= e($tool->label()) ?></h3>
                    <p style="color:var(--aho-color-ink-500);font-size:var(--aho-text-sm)"><?= e($tool->description()) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
